<?
define('WINDOWS', (strPos(strToLower(php_uname('s')), 'windows') !== false));

/**
 * @desc Eigener Errorhandler.
 */
function onError($errorLevel, $msg, $file, $line, $vars) {
   static $levels  = null;
   static $console = null;                   // ob PHP als Konsolenapplikation läuft
   static $windows = null;                   // ob PHP unter Windows läuft

   if (!$levels) {
      $levels = Array(E_PARSE           => 'Parse Error',         // All levels for completeness only, in reality
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
      $windows = defined('WINDOWS') ? WINDOWS : (strPos(strToLower(php_uname('s')), 'windows') !== false);
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
         $error = $windows ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $error)) : str_replace("\r\n", "\n", $error);
         error_log(trim($error), 0);
      }

      // Fehler an Webmaster mailen
      if ($mailErrors) {
         if ($trace)
            $error .= "\n".$trace;
         $error .= "\nRequest:\n--------\n".getRequest()."\nIP: ".$_SERVER['REMOTE_ADDR']."\n---\n";
         $error = $windows ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $error)) : str_replace("\r\n", "\n", $error);

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($error, 1, $webmaster, 'Subject: PHP error_log: '.$levels[$errorLevel].' at '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
         }
      }

      if ($errorLevel & (E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_USER_ERROR))
         exit(1);                                                                   // bei kritischen Fehlern Script beenden
   }
}
set_error_handler('onError');


/**
 * @desc Führt in der angegebenen Datenbank eine SQL-Anweisung aus (benutzt eine evt. schon offene Verbindung).
 */
function executeSql($sql, &$db) {                              // Return: array $result['set']    - das Resultset
   $sql = trim($sql);                                          //               $result['rows']   - Anzahl der zurückgegebenen oder bearbeiteten Datensätze
   $result = Array('set'   => null,                            //               $result['error']  - eine eventuelle MySQL-Fehlermeldung
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
         $result['set'] = $resultSet;
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
 * @desc Startet eine neue Datenbank-Transaktion.
 */
function beginTransaction(&$db) {
   if (isSet($db['isTransaction']) && $db['isTransaction']) {
      return false;
   }
   executeSql('begin', $db);
   return ($db['isTransaction'] = true);
}


/**
 * @desc Committed eine Datenbank-Transaktion.
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
 * @desc Rollt eine Datenbank-Transaktion zurück.
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
 * @desc Ermittelt einen evt. gesetzten 'Forwarded-IP'-Value des aktuellen Request.
 */
function getForwardedIP() {                                    // Return: string
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
 * @desc Erzeugt eine zufällige ID der angegebenen Länge (ohne die Zeichen 0 O 1 l I, da Verwechselungsgefahr).
 */
function getRandomId($length) {                                // Return: string
   (!isSet($length) || !is_int($length) || $length < 1) && trigger_error("Invalid argument length: $length", E_USER_ERROR);

   $id = crypt(uniqId(rand(), true));                          // zufällige ID erzeugen
   $id = strip_tags(stripSlashes($id));                        // Sonder- und leicht zu verwechselnde Zeichen entfernen
   $id = strRev(str_replace('/', '', str_replace('.', '', str_replace('$', '', str_replace('0', '', str_replace('O', '', str_replace('1', '', str_replace('l', '', str_replace('I', '', $id)))))))));
   $len = strLen($id);
   if ($len < $length) {
      $id .= getRandomId($length-$len);                        // bis zur gewünschten Länge vergrößern ...
   }
   else {
      $id = subStr($id, 0, $length);                           // oder auf die gewünschte Länge kürzen
   }
   return $id;
}


/**
 * @desc Prüft eine übergebene Session-ID. Die Session könnte verfallen oder die ID ungültig sein.
 */
function checkSessionId() {                                    // Return: void
   $sessionName = session_name();
   if (!isSession() && isSet($_REQUEST[$sessionName])) {
      $valid = (preg_match('/^[a-zA-Z0-9]+$/', $_REQUEST[$sessionName]));

      if ($valid) {                    // !!! prüfen, ob Session mit dieser ID existiert (funktioniert nur mit File-Handler !!!
         $sessionSavePath = session_save_path();
         if (strPos($sessionSavePath, ';') !== false)
            $sessionSavePath = subStr($sessionSavePath, strPos($sessionSavePath, ';') + 1);
         $valid = is_file($sessionSavePath.'/sess_'.$_REQUEST[$sessionName]);
      }
      if (!$valid) {
         //setCookie($sessionName, null, 0, '/');
         setCookie($sessionName, null);
         redirect($_SERVER['PHP_SELF'].($_SERVER['QUERY_STRING'] ? '?'.str_replace($sessionName.'='.$_REQUEST[$sessionName], '', $_SERVER['QUERY_STRING']) : ''));
      }
   }
}


/**
 * @desc Prüft, ob eine aktuelle HttpSession existiert oder nicht.
 */
function isSession() {                                         // Return: boolean
   return defined('SID');
}


/**
 * @desc Prüft, ob die aktuelle HttpSession neu ist oder nicht.
 */
function isSessionNew() {                                      // Return: boolean
   static $result = null;
   if ($result === null) {
      $result = (isSession() && (!isSet($_REQUEST[session_name()]) || $_REQUEST[session_name()]!=session_id()));
   }
   return $result;
}


/**
 * @desc Sendet einen Redirect-Header mit der angegebenen URL.
 */
function redirect($url) {
   if (isSession()) {
      if (isSessionNew() || SID!=='') {                        // bleiben wir innerhalb der Domain und Cookies sind aus, wird eine evt. Session-ID weitergegeben
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
}


/**
 * @desc Hilfsfunktion zur formatierten Anzeige einer Variablen (mittels <pre> Tag).
 */
function printFormatted($var, $return = false) {               // Return: void   - wenn $return = false (default)
   $console = (!isSet($_SERVER['REQUEST_METHOD']));            //         string - wenn $return = true (siehe Dokumentation zu print_r())

   $str = null;
   if (!$console)
      $str .= "<div align='left'><pre>";

   $str .= (is_array($var) ? print_r($var, true) : $var)."\n";
   if (!$console)
      $str .= '</pre></div>';
   if ($return)
      return $str;

   echo $str;
}

/**
 * @desc Alias für printFormatted().
 */
function echoPre($var) {                                       // Return: void
   printFormatted($var);
}


/**
 * @desc Gibt den aktuellen Errorlevel des Scripts in lesbarer Form zurück
 */
function getReadableErrorLevel() {
   $levels = Array();
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
   return $current.": ".implode(' | ', $levels);
}


/**
 * @desc Dekodiert alle HTML-Entities zurück in ihre entsprechenden Zeichen (ISO-8859-15).
 */
function decodeHtmlEntities($html) {                           // Return: string - der dekodierte String
   $table =& get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
   $table =& array_flip($table);
   $table['&nbsp;'] = ' ';
   $table['&euro;'] = '€';
   $string = strTr($html, $table);
   return preg_replace('/&#(\d+);/me', "chr('\\1')", $string);
}


/**
 * @desc Prüft, ob der übergebene Parameter ein gültiges Datum darstellt (Format: yyyy-mm-dd).
 */
function isDate($date) {                                       // Return: boolean
   if (!is_string($date)) return false;
   if (!preg_match_all("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date, $matches, PREG_SET_ORDER)) return false;
   $year  = $matches[0][1];
   $month = $matches[0][2];
   $day   = $matches[0][3];
   return checkDate((int) $month, (int) $day, (int) $year);
}


/**
 * @desc Addiert zu einem Datum die angegebene Anzahl von Tagen (Format: yyyy-mm-dd).
 */
function addDate($date, $days) {                               // Return: string
   if (!isDate($date)) { trigger_error('Invalid argument \$date: '.$date, E_USER_WARNING); return null; }
   if (!is_int($days)) { trigger_error('Invalid argument \$days: '.$days, E_USER_WARNING); return null; }

   $parts = explode('-', $date);
   $year  = (int) $parts[0];
   $month = (int) $parts[1];
   $day   = (int) $parts[2];

   return date('Y-m-d', mkTime(0, 0, 0, $month, $day+$days, $year));
}


/**
 * @desc Subtrahiert von einem Datum die angegebene Anzahl von Tagen (Format: yyyy-mm-dd).
 */
function subDate($date, $days) {                               // Return: string
   if (!is_int($days)) { trigger_error('Invalid argument \$days: '.$days, E_USER_WARNING); return null; }
   return addDate($date, -$days);
}


/**
 * @desc Gibt eine lesbare Representation des HTTP-Requests zurück.
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
         $params = Array();
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
 * @desc Gibt alle Request-Header zurück.
 */
function getRequestHeaders() {                                 // Return: Array
   if (function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      if ($headers === false) {
         trigger_error('Error reading request headers, apache_request_headers(): false', E_USER_WARNING);
         $headers = Array();
      }
      return $headers;
   }

   $headers = Array();
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
?>
