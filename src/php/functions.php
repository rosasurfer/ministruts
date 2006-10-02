<?
define('ACTION_ERRORS_KEY', '__ACTION_ERRORS__');
define('SESSION_INIT_KEY' , '__SESSION_INITIALIZED__');
define('WINDOWS', (strPos(strToLower(php_uname('s')), 'windows') !== false));       // ob PHP unter Windows läuft


if (PHP_VERSION >= '5')
   set_exception_handler('onException');
set_error_handler('onError');


/**
 * Eigener Exceptionhandler.
 *
 * @param exception -
 */
function onException($exception) {
   echoPre($exception->getTrace());
   echoPre($exception->getTraceAsString());
   echoPre(debug_backtrace());
}


/**
 * Eigener Errorhandler.
 *
 * @param errorLevel -
 * @param msg        -
 * @param file       -
 * @param line       -
 * @param vars       -
 *
 * @throws <tt>Exception</tt>
 */
function onError($errorLevel, $msg, $file, $line, $vars) {
   static $levels  = null;
   static $console = null;                   // ob PHP als Konsolenapplikation läuft

   if (!$levels) {
      $levels = array(E_PARSE           => 'Parse Error',         // All levels for completeness only, in reality
                      E_COMPILE_ERROR   => 'Compile Error',       // the only entries we should consider are
                      E_COMPILE_WARNING => 'Compile Warning',     // E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE and E_STRICT.
                      E_CORE_ERROR      => 'Core Error',
                      E_CORE_WARNING    => 'Core Warning',
                      E_ERROR           => 'Error',
                      E_WARNING         => 'Warning',
                      E_NOTICE          => 'Notice',
                      E_USER_ERROR      => 'Error',
                      E_USER_WARNING    => 'Warning',
                      E_USER_NOTICE     => 'Notice');
      if (defined('E_STRICT')) {
         $levels[E_STRICT] = 'Runtime Notice';
      }
      $console = (!isSet($_SERVER['REQUEST_METHOD']));
      if ($console) {
         ini_set('html_errors', '0');
      }
   }

   $logLevel      =  error_reporting();
   $logErrors     = (ini_get('log_errors') == '1');
   $mailErrors    = !$console && $_SERVER['REMOTE_ADDR']!='127.0.0.1';           // nur, wenn nicht auf der Konsole und nicht auf localhost
   $displayErrors =  $console || $_SERVER['REMOTE_ADDR']=='127.0.0.1';           // Anzeige im Browser nur, wenn auf localhost
   $htmlErrors    = !$console;

   if (($logLevel & $errorLevel) == $errorLevel) {
      $error = $levels[$errorLevel].': '.trim($msg)."\nin $file on line $line\n";

      // Stacktrace generieren
      $trace = null;
      if ($errorLevel != E_NOTICE || (subStr($msg, 0, 25)!='Use of undefined constant' && subStr($msg, 0, 20)!='Undefined variable: ')) {
         $stackTrace = debug_backtrace();
         array_shift($stackTrace);                       // drop the first element, it's this function itself
         $sizeOfStackTrace = sizeOf($stackTrace);

         if ($sizeOfStackTrace > 1) {
            $trace  = "Stacktrace:\n";
            $trace .= "-----------\n";

            // formatieren (PHP style)
            $callLen = $lineLen = 0;
            for ($i=0; $i < $sizeOfStackTrace; $i++) {
               $frame =& $stackTrace[$i];
               $call = '';
               if (isSet($frame['class']))
                  $call = ucFirst($frame['class']).(isSet($frame['type']) ? $frame['type'] : '.');
               $call .= $frame['function'];
               $frame['call'] = $call.(($frame['function']=='include' || $frame['function']=='include_once' || $frame['function']=='require' || $frame['function']=='require_once') ? ':':'():');
               $callLen = max($callLen, strLen($frame['call']));

               $frame['line'] = isSet($frame['line']) ? " # line $frame[line]," : '';
               $lineLen = max($lineLen, strLen($frame['line']));

               $frame['file'] = isSet($frame['file']) ? " file: $frame[file]" : ' [PHP kernel]';
            }
            for ($i=0; $i < $sizeOfStackTrace; $i++) {
               $trace .= str_pad($stackTrace[$i]['call'], $callLen).str_pad($stackTrace[$i]['line'], $lineLen).$stackTrace[$i]['file']."\n";
            }
         }
      }

      // Fehler anzeigen
      if ($displayErrors) {
         if ($htmlErrors) {
            echo nl2br("<div align='left'><b>$levels[$errorLevel]</b>: $msg\n in <b>$file</b> on line <b>$line</b>");
            if ($trace)
               echo '<br>'.printFormatted($trace, true).'<br>';
            echo "</div>";
         }
         else {
            echo $error;                                 // PHP-Linux gibt den Fehler zusätzlich auf stderr aus,
            if ($trace)                                  // also auf der Konsole ggf. unterdrücken ( 2>/dev/null )
               printFormatted("\n".$trace);
         }
      }

      // Fehler ins Error-Log schreiben
      if ($logErrors) {
         $error = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $error)) : str_replace("\r\n", "\n", $error);
         error_log(trim($error), 0);
      }

      // Fehler an Webmaster mailen
      if ($mailErrors) {
         if ($trace)
            $error .= "\n".$trace;
         $error .= "\nRequest:\n--------\n".getRequest()."\nIP: ".$_SERVER['REMOTE_ADDR']."\n---\n";
         $error = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $error)) : str_replace("\r\n", "\n", $error);

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($error, 1, $webmaster, 'Subject: PHP error_log: '.$levels[$errorLevel].' at '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
         }
      }

      if ($errorLevel & (E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_USER_ERROR))
         exit(1);                                                                   // bei kritischen Fehlern Script beenden
   }
}


/**
 * Führt in der angegebenen Datenbank eine SQL-Anweisung aus (benutzt eine evt. schon offene Verbindung).
 *
 * @return array
 */
function &executeSql($sql, &$db) {                             // Return: array $result['set']    - das Resultset
   $sql = trim($sql);                                          //               $result['rows']   - Anzahl der zurückgegebenen oder bearbeiteten Datensätze
   $result = array('set'   => null,                            //               $result['error']  - eine eventuelle MySQL-Fehlermeldung
                   'rows'  => 0,
                   'error' => null);
   if (!is_array($db)) {
      $result['error'] = 'Ungültige DB-Konfiguration - type of $db: '.getType($db);
   }                                                                                      // ohne Connection neue aufbauen
   elseif (!$db['connection'] && (!isSet($db['server']) || !isSet($db['user']) || !isSet($db['password']) || !$db['database'])) {
      $result['error'] = 'DB-Konfiguration fehlerhaft';
   }                                                                                      // ohne Connection neue aufbauen
   elseif (!$db['connection'] && !($db['connection'] = mysql_connect($db['server'], $db['user'], $db['password']))) {
      $result['error'] = "Keine Datenbankverbindung\n".mysql_errno().': '.mysql_error();
   }
   elseif ($db['database'] && !mysql_select_db($db['database'], $db['connection'])) {     // Datenbank selektieren
      $result['error'] = ($errno = mysql_errno()) ? "Datenbank nicht gefunden\n$errno: ".mysql_error() : 'Netzwerkfehler beim Datenbankzugriff';
   }
   elseif ($resultSet = mysql_query($sql, $db['connection'])) {                           // Abfrage abschicken
      if (is_resource($resultSet)) {
         $result['set'] =& $resultSet;
         $result['rows'] = mysql_num_rows($resultSet);                                    // Anzahl der zurückgegebenen Zeilen auslesen
      }
      else {
         $sql = strToLower($sql);
         if (subStr($sql,0,6)=='insert' || subStr($sql,0,7)=='replace' || subStr($sql,0,6)=='update' || subStr($sql,0,6)=='delete') {
            $result['rows'] = mysql_affected_rows($db['connection']);                     // Anzahl der geänderten Zeilen auslesen
         }
      }
   }
   else {
      if ($errNo = mysql_errno()) {
         $result['error'] = "SQL-Error $errNo: ".mysql_error();
      }
      else $result['error'] = 'Netzwerkfehler beim Datenbankzugriff';
   }
   if ($result['error']) {
      $result['error'] .= "\nSQL: ".str_replace("\n", " ", str_replace("\r\n", "\n", $sql));
      trigger_error($result['error'], E_USER_ERROR);
   }
   return $result;
}


/**
 * Startet eine neue Datenbank-Transaktion.
 *
 * @return boolean
 */
function beginTransaction(&$db) {
   if (isSet($db['isTransaction']) && $db['isTransaction']) {
      return false;
   }
   executeSql('begin', $db);
   return ($db['isTransaction'] = true);
}


/**
 * Committed eine Datenbank-Transaktion.
 *
 * @return boolean
 */
function commitTransaction(&$db) {
   if (!$db['connection']) {
      trigger_error("Warn: No database connection for committing transaction", E_USER_WARNING);
      return false;
   }
   if (!isSet($db['isTransaction']) || !$db['isTransaction']) {
      trigger_error("Warn: No database transaction to commit", E_USER_WARNING);
      return false;
   }
   executeSql('commit', $db);
   $db['isTransaction'] = false;
   return true;
}


/**
 * Rollt eine Datenbank-Transaktion zurück.
 *
 * @return boolean
 */
function rollbackTransaction(&$db) {
   if (!$db['connection']) {
      trigger_error("Warn: No database connection for rolling back transaction", E_USER_WARNING);
      return false;
   }
   if (!isSet($db['isTransaction']) || !$db['isTransaction']) {
      trigger_error("Warn: No database transaction to roll back", E_USER_WARNING);
      return false;
   }
   executeSql('rollback', $db);
   $db['isTransaction'] = false;
   return true;
}


/**
 * Ermittelt einen evt. gesetzten 'Forwarded-IP'-Value des aktuellen Request.
 *
 * @return string
 */
function getForwardedIP() {
   static $address = false;
   if ($address === false) {
      $address = $_SERVER['HTTP_X_FORWARDED_FOR'];
      if ($address == null)
         $address = $_SERVER['HTTP_HTTP_X_FORWARDED_FOR'];
      if ($address == null)
         $address = $_SERVER['HTTP_X_UP_FORWARDED_FOR'];
      if ($address == null)
         $address = $_SERVER['HTTP_HTTP_X_UP_FORWARDED_FOR'];
   }
   return $address;
}


/**
 * Erzeugt eine zufällige ID der angegebenen Länge (ohne die Zeichen 0 O 1 l I, da Verwechselungsgefahr).
 */
function getRandomID($length) {                                // Return: string
   (!isSet($length) || !is_int($length) || $length < 1) && trigger_error("Invalid argument length: $length", E_USER_ERROR);

   $id = crypt(uniqId(rand(), true));                          // zufällige ID erzeugen
   $id = strip_tags(stripSlashes($id));                        // Sonder- und leicht zu verwechselnde Zeichen entfernen
   $id = strRev(str_replace('/', '', str_replace('.', '', str_replace('$', '', str_replace('0', '', str_replace('O', '', str_replace('1', '', str_replace('l', '', str_replace('I', '', $id)))))))));
   $len = strLen($id);
   if ($len < $length) {
      $id .= getRandomID($length-$len);                        // bis zur gewünschten Länge vergrößern ...
   }
   else {
      $id = subStr($id, 0, $length);                           // oder auf die gewünschte Länge kürzen
   }
   return $id;
}


/**
 * Startet eine neue HTTP-Session oder setzt eine vorhergehende Session fort. Ist die übergebene Session-ID ungültig, wird eine neue generiert.
 *
 * @return boolean - ob die resultierende Session neu ist oder nicht
 */
function startSession() {
   if (!isSession()) {
      $php_errormsg = null;
      @session_start();
      if (strPos($php_errormsg, 'The session id contains invalid characters') === 0) {
         session_regenerate_id();
         return true;
      }
   }
   return isNewSession();
}


/**
 * Prüft, ob eine aktuelle HttpSession existiert oder nicht.
 *
 * @return boolean
 */
function isSession() {
   return defined('SID');
}


/**
 * Prüft, ob die aktuelle HttpSession neu ist oder nicht.
 *
 * @return boolean
 */
function isNewSession() {
   static $result = null;  // statisches Zwischenspeichern des Ergebnisses

   if (is_null($result)) {
      if (isSession()) {                                    // eine Session existiert ...
         if (@$_REQUEST[session_name()] == session_id()) {  // ... sie kommt vom Kunden
            $result = (sizeOf($_SESSION) == 0);             // eine leere Session muß neu sein
         }
         else {            // Session kommt nicht vom Kunden
            $result = true;
         }
         if (sizeOf($_SESSION) == 0) {                      // leere Session initialisieren
            $_SESSION[SESSION_INIT_KEY] = 1;
         }
      }
      else {               // Session existiert nicht, könnte aber noch erzeugt werden, also Ergebnis nicht speichern
         return false;
      }
   }
   return $result;
}


/**
 * Entfernt alle gespeicherten Informationen aus der aktuellen Session.
 *
 * @return boolean true  - alle gespeicherten Informationen wurden gelöscht
 *                 false - ein Fehler ist aufgetreten (es existiert keine Session)
 */
function clearSession() {
   if (isSession()) {
      $keys = array_keys($_SESSION);
      foreach ($keys as $key) {
         if ($key != SESSION_INIT_KEY)
            unSet($_SESSION[$key]);
      }
      return true;
   }
   return false;
}


/**
 * Sendet einen Redirect-Header mit der angegebenen URL.
 */
function redirect($url) {
   if (isSession()) {
      if (isNewSession() || SID!=='') {                        // bleiben wir innerhalb der Domain und Cookies sind aus, wird eine evt. Session-ID weitergegeben
         $host = strToLower(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
         $found = preg_match_all('/^https?:\/{2,}([a-z0-9-]+(\.[a-z0-9-]+)*)*.*$/', strToLower(trim($url)), $matches, PREG_SET_ORDER);

         if (!$found || $matches[0][1]==$host) {               // SID anhängen
            $url .= (strPos($url, '?') === false ? '?' : ini_get('arg_separator.output')).SID;
         }
      }
      session_write_close();
   }
   header('Location: '.$url);
   exit();                       // Ausgabe weiteren Contents verhindern

   /* !!!
    * HTTP/1.1 requires an absolute URI as argument to 'Location:' including the scheme, hostname and absolute path,
    * but some clients accept relative URIs. You can usually use $_SERVER['HTTP_HOST'], $_SERVER['PHP_SELF'] and
    * dirname() to make an absolute URI from a relative one yourself.
    */
}


/**
 * Hilfsfunktion zur formatierten Anzeige einer Variablen (mittels <pre> Tag).
 *
 * @return string
 */
function printFormatted($var, $return = false) {                  // Return: void   - wenn $return = false (default)
   $str = (is_array($var) ? print_r($var, true) : $var)."\n";     //         string - wenn $return = true (siehe Dokumentation zu print_r())

   if (isSet($_SERVER['REQUEST_METHOD'])) {
      $str = '<div align="left"><pre>'.$str.'</pre></div>';
   }

   if ($return)
      return $str;

   echo $str;
   return null;
}


/**
 * Alias für printFormatted().
 */
function echoPre($var) {
   printFormatted($var);
}


/**
 * Gibt den übergebenen String als JavaScript aus.
 */
function javaScript($script) {
   echo '<script language="JavaScript">'.$script.'</script>';
}


/**
 * Gibt den aktuellen Errorlevel des Scripts in lesbarer Form zurück.
 *
 * @return string
 */
function getErrorLevelAsString() {
   $levels = array();
   $current = error_reporting();
   if (($current & E_ERROR          ) == E_ERROR          ) $levels[] = 'E_ERROR';
   if (($current & E_WARNING        ) == E_WARNING        ) $levels[] = 'E_WARNING';
   if (($current & E_PARSE          ) == E_PARSE          ) $levels[] = 'E_PARSE';
   if (($current & E_NOTICE         ) == E_NOTICE         ) $levels[] = 'E_NOTICE';
   if (($current & E_CORE_ERROR     ) == E_CORE_ERROR     ) $levels[] = 'E_CORE_ERROR';
   if (($current & E_CORE_WARNING   ) == E_CORE_WARNING   ) $levels[] = 'E_CORE_WARNING';
   if (($current & E_COMPILE_ERROR  ) == E_COMPILE_ERROR  ) $levels[] = 'E_COMPILE_ERROR';
   if (($current & E_COMPILE_WARNING) == E_COMPILE_WARNING) $levels[] = 'E_COMPILE_WARNING';
   if (($current & E_USER_ERROR     ) == E_USER_ERROR     ) $levels[] = 'E_USER_ERROR';
   if (($current & E_USER_WARNING   ) == E_USER_WARNING   ) $levels[] = 'E_USER_WARNING';
   if (($current & E_USER_NOTICE    ) == E_USER_NOTICE    ) $levels[] = 'E_USER_NOTICE';
   if (($current & E_ALL            ) == E_ALL            ) $levels[] = 'E_ALL';
   if (defined('E_STRICT')) {
      if (($current & E_STRICT      ) == E_STRICT         ) $levels[] = 'E_STRICT';
   }
   return $current.": ".join(' | ', $levels);
}


/**
 * Dekodiert alle HTML-Entities zurück in ihre entsprechenden Zeichen (ISO-8859-15).
 *
 * @return string der dekodierte String
 */
function decodeHTML($html) {
   $table =& get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
   $table =& array_flip($table);
   $table['&nbsp;'] = ' ';
   $table['&euro;'] = '€';
   $string = strTr($html, $table);
   return preg_replace('/&#(\d+);/me', "chr('\\1')", $string);
}


/**
 * Prüft, ob der übergebene Parameter ein gültiges Datum darstellt (Format: yyyy-mm-dd).
 *
 * @return boolean
 */
function isDate($date) {
   static $datePattern = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/';

   $matches = array();
   if (!is_string($date) || !preg_match_all($datePattern, $date, $matches, PREG_SET_ORDER))
      return false;

   $year  = $matches[0][1];
   $month = $matches[0][2];
   $day   = $matches[0][3];
   return checkDate((int) $month, (int) $day, (int) $year);
}


/**
 * Addiert zu einem Datum die angegebene Anzahl von Tagen (Format: yyyy-mm-dd).
 *
 * @return string
 */
function addDate($date, $days) {
   if (!isDate($date)) { trigger_error('Invalid argument $date: '.$date, E_USER_WARNING); return null; }
   if (!is_int($days)) { trigger_error('Invalid argument $days: '.$days, E_USER_WARNING); return null; }

   $parts = explode('-', $date);
   $year  = (int) $parts[0];
   $month = (int) $parts[1];
   $day   = (int) $parts[2];

   return date('Y-m-d', mkTime(0, 0, 0, $month, $day+$days, $year));
}


/**
 * Subtrahiert von einem Datum die angegebene Anzahl von Tagen (Format: yyyy-mm-dd).
 *
 * @return string
 */
function subDate($date, $days) {
   if (!is_int($days)) { trigger_error('Invalid argument \$days: '.$days, E_USER_WARNING); return null; }
   return addDate($date, -$days);
}


/**
 * Formatiert einen SQL-Date- oder SQL-DateTime-Wert mit dem angegebenen Format.
 *
 * @return string
 */
function formatSqlDate($format, $sqlDate) {
   if (is_null($sqlDate) || $sqlDate=='0000-00-00 00:00:00')
      return null;

   if ($sqlDate < '1970-01-01 00:00:00') {
      if ($format == 'd.m.Y') {
         $data = explode('-', $sqlDate);
         return "$data[2].$data[1].$data[0]";
      }
      else {
         trigger_error('Cannot format SQL date: '.$sqlDate, E_USER_WARNING);
      }
   }
   else {
      $timestamp = strToTime($sqlDate);
      if (is_int($timestamp)) {
         return date($format, $timestamp);
      }
   }
}


/**
 * date_mysql2german
 * wandelt ein MySQL-DATE (ISO-Date)
 * in ein traditionelles deutsches Datum um.
function date_mysql2german($datum) {
    list($jahr, $monat, $tag) = explode("-", $datum);

    return sprintf("%02d.%02d.%04d", $tag, $monat, $jahr);
}
 */

/**
 * date_german2mysql
 * wandelt ein traditionelles deutsches Datum
 * nach MySQL (ISO-Date).
function date_german2mysql($datum) {
    list($tag, $monat, $jahr) = explode(".", $datum);

    return sprintf("%04d-%02d-%02d", $jahr, $monat, $tag);
}
 */

/**
 * timestamp_mysql2german
 * wandelt ein MySQL-Timestamp
 * in ein traditionelles deutsches Datum um.
function timestamp_mysql2german($t) {
    return sprintf("%02d.%02d.%04d",
                    substr($t, 6, 2),
                    substr($t, 4, 2),
                    substr($t, 0, 4));
}
*/


/**
 * Gibt eine lesbare Representation des HTTP-Requests zurück.
 *
 * @return string
 */
function getRequest() {
   $request = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL']."\n";

   $headers = getRequestHeaders();
   $maxLen = 0;
   foreach ($headers as $key => $value) {
      $maxLen = (strLen($key) > $maxLen) ? strLen($key) : $maxLen;
   }
   $maxLen++;
   foreach ($headers as $key => $value) {
      $request .= str_pad($key.':', $maxLen).' '.$value."\n";
   }

   if ($_SERVER['REQUEST_METHOD']=='POST' && (int)$headers['Content-Length'] > 0) {
      if ($headers['Content-Type'] == 'application/x-www-form-urlencoded') {
         $params = array();
         foreach ($_POST as $name => $value) {
            $params[] = $name.'='.urlEncode($value);
         }
         $request .= "\n".implode('&', $params)."\n";
      }
      else if ($headers['Content-Type'] == 'multipart/form-data') {
         ;                    // !!! to do
      }
      else {
         ;                    // !!! to do
      }
   }
   return $request;
}


/**
 * Gibt alle Request-Header zurück.
 *
 * @return array
 */
function getRequestHeaders() {
   if (function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      if ($headers === false) {
         trigger_error('Error reading request headers, apache_request_headers(): false', E_USER_WARNING);
         $headers = array();
      }
      return $headers;
   }

   $headers = array();
   foreach ($_SERVER as $key => $value) {
      if (ereg('HTTP_(.+)', $key, $matches) > 0) {
         $key = strToLower($matches[1]);
         $key = str_replace(' ', '-', ucWords(str_replace('_', ' ', $key)));
         $headers[$key] = $value;
      }
   }
   if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      if (isSet($_SERVER['CONTENT_TYPE']))
         $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
      if (isSet($_SERVER['CONTENT_LENGTH']))
         $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
   }
   return $headers;
}


/**
 * Ob Error-Messages existieren oder nicht.
 *
 * @return boolean
 */
function isActionErrors() {
   return isSet($_REQUEST[ACTION_ERRORS_KEY]) && sizeOf($_REQUEST[ACTION_ERRORS_KEY]);
}


/**
 * Ob für den angegebenen Schlüssel eine Error-Message existiert oder nicht.
 *
 * @return boolean
 */
function isActionError($key) {
   return isSet($_REQUEST[ACTION_ERRORS_KEY][$key]);
}


/**
 * Gibt die Error-Message für den angegebenen Schlüssel zurück.
 * Ohne Schlüssel wird die erste vorhandene Error-Message zurückgegeben.
 *
 * @return string
 */
function getActionError($key = null) {
   if (is_null($key)) {
      if (isActionErrors()) {
         return current($_REQUEST[ACTION_ERRORS_KEY]);
      }
   }
   elseif (isActionError($key)) {
      return $_REQUEST[ACTION_ERRORS_KEY][$key];
   }
   return null;
}


/**
 * Setzt für den angegebenen Schlüssel eine Error-Message.
 */
function setActionError($key, $message) {
   $_REQUEST[ACTION_ERRORS_KEY][$key] = $message;
}


/**
 * Ist <tt>$value</tt> nicht NULL, gibt die Funktion <tt>$value</tt> zurück, andererseits die Alternative <tt>$alt</tt>.
 *
 * @return mixed
 */
function ifNull(&$value, $alt) {
   return isSet($value) ? $value : $alt;
}



/*
Wie stelle ich Tabellenzeilen abwechselnd farbig dar?
-----------------------------------------------------
In der folgenden Funktion bgcolor() kann man beliebig viele Farben im Array $col definieren,
die bei jedem Aufruf der Reihe nach berücksichtigt werden. Optional kann die Funktion mit
einem Integer-Wert aufgerufen werden (bgcolor(n)), um immer n aufeinander folgende Zeilen
derselben Farbe zu erhalten.

function bgcolor($row = 1) {
    static $i;
    static $col = array('#FFDDDD',
                        '#DDFFDD',
                        '#DDDDFF'
                       ); // etc.
    $bg = $col[(int)($i + .00000001)];
    $i += 1 / $row;
    if ($i >= count($col)) $i = 0;
    return $bg;
}
printf("<tr bgcolor='%s'><td>...</td></tr>\n", bgcolor(2));

oder mit CSS
------------
row0 {background-color:#FFDDDD}
row1 {background-color:#DDFFDD}

printf("<tr class='row%s'><td>...</td></tr>\n", $line % 2);



----------------------------------------------------------------------------
Indicate that script is being called by CLI (vielleicht besser für $console)
----------------------------------------------------------------------------
if ( php_sapi_name() == 'cli' ) {
   $CLI = true ;
}

*/
?>
