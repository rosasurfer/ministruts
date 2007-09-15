<?
// Systemvoraussetzung: PHP 5.1+
// -----------------------------


// Klassenimporte
// --------------
$__autoloadClasses['AbstractActionForm'             ] = 'php/actions/AbstractActionForm.php';

$__autoloadClasses['ConcurrentModificationException'] = 'php/db/ConcurrentModificationException.php';
$__autoloadClasses['PersistableObject'              ] = 'php/db/PersistableObject.php';

$__autoloadClasses['BusinessRuleException'          ] = 'php/lang/BusinessRuleException.php';
$__autoloadClasses['IllegalTypeException'           ] = 'php/lang/IllegalTypeException.php';
$__autoloadClasses['InfrastructureException'        ] = 'php/lang/InfrastructureException.php';
$__autoloadClasses['InvalidArgumentException'       ] = 'php/lang/InvalidArgumentException.php';
$__autoloadClasses['NestableException'              ] = 'php/lang/NestableException.php';
$__autoloadClasses['NoPermissionException'          ] = 'php/lang/NoPermissionException.php';
$__autoloadClasses['Object'                         ] = 'php/lang/Object.php';
$__autoloadClasses['PHPErrorException'              ] = 'php/lang/PHPErrorException.php';
$__autoloadClasses['RuntimeException'               ] = 'php/lang/RuntimeException.php';

$__autoloadClasses['BasePdfDocument'                ] = 'php/pdf/BasePdfDocument.php';
$__autoloadClasses['SimplePdfDocument'              ] = 'php/pdf/SimplePdfDocument.php';

$__autoloadClasses['BaseValidator'                  ] = 'php/util/BaseValidator.php';
$__autoloadClasses['HttpRequest'                    ] = 'php/util/HttpRequest.php';
$__autoloadClasses['Logger'                         ] = 'php/util/Logger.php';
$__autoloadClasses['Mailer'                         ] = 'php/util/Mailer.php';
$__autoloadClasses['TorHelper'                      ] = 'php/util/TorHelper.php';



// Konstanten
// ----------
define('L_DEBUG' ,  1);                               // die verschiedenen Loglevel
define('L_NOTICE',  2);
define('L_INFO'  ,  4);
define('L_WARN'  ,  8);
define('L_ERROR' , 16);
define('L_FATAL' , 32);

define('LOGLEVEL', L_WARN);                           // der aktive Loglevel

define('LOGLEVEL_DEBUG' , L_DEBUG  >= LOGLEVEL);      // Flags, die anzeigen, ob ein Loglevel aktiv ist oder nicht
define('LOGLEVEL_NOTICE', L_NOTICE >= LOGLEVEL);
define('LOGLEVEL_INFO'  , L_INFO   >= LOGLEVEL);
define('LOGLEVEL_WARN'  , L_WARN   >= LOGLEVEL);
define('LOGLEVEL_ERROR' , L_ERROR  >= LOGLEVEL);
define('LOGLEVEL_FATAL' , L_FATAL  >= LOGLEVEL);


define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3))==='WIN'));    // ob das Script unter Windows läuft



// Errorhandler registrieren
// -------------------------
set_error_handler    (array('Logger', 'handleError'    ));
set_exception_handler(array('Logger', 'handleException'));



/**
 * Lädt die Klassendefinition der angegebenen Klasse.
 *
 * @param string $className - Klassenname
 */
function __autoload($className) {
   try {
      if (isSet($GLOBALS['__autoloadClasses'][$className])) {
         include($GLOBALS['__autoloadClasses'][$className]);
         return true;
      }

      $stackTrace = debug_backtrace();
      throw new PHPErrorException("Undefined class '$className'", $stackTrace[0]['file'], $stackTrace[0]['line'], array());
   }
   catch (Exception $ex) {                   // Auftretende Exceptions manuell an globalen ErrorHandler weiterreichen,
      Logger ::handleException($ex);         // da __autoload() keine Exceptions werfen darf.
   }
}


/**
 * Führt eine SQL-Anweisung aus. Dabei wird eine evt. schon offene Verbindung weiterverwendet.
 *
 * @param string $sql - die auszuführende SQL-Anweisung
 * @param array  $db  - die zu verwendende Datenbankkonfiguration
 *
 * @return array['set']  - das zurückgegebene Resultset (wenn zutreffend)
 *              ['rows'] - Anzahl der zurückgegebenen bzw. bearbeiteten Datensätze (wenn zutreffend)
 */
function &executeSql($sql, array &$db) {
   if (!is_string($sql) || !($sql = trim($sql)))
      throw new InvalidArgumentException('Invalid SQL string: '.$sql);

   if (!isSet($db['server']) || !isSet($db['user']) || !isSet($db['password']) || !$db['database'] || !array_key_exists('connection', $db))
      throw new InvalidArgumentException('Invalid database configuration: '.$db);


   // ohne bestehende Verbindung eine neue aufbauen
   if ($db['connection'] === null) {
      try {
         $db['connection'] = mysql_connect($db['server'], $db['user'], $db['password'], true);
         if ($db['connection'] === null)
            throw new InfrastructureException('Database connection error'.(($errno = mysql_errno()) ? "\nError $errno: ".mysql_error() : '')."\nSQL: ".str_replace("\n", ' ', str_replace("\r\n", "\n", $sql)));
      }
      catch (PHPErrorException $ex) {
         throw new InfrastructureException('Database connection error'.(($errno = mysql_errno()) ? "\nError $errno: ".mysql_error() : '')."\nSQL: ".str_replace("\n", ' ', str_replace("\r\n", "\n", $sql)), $ex);
      }

      // nach Verbindungsaufbau Datenbank selektieren
      if (!mysql_select_db($db['database'], $db['connection']))
         throw new InfrastructureException((($errno = mysql_errno()) ? "Error $errno: ".mysql_error() : 'Database connection error')."\nSQL: ".str_replace("\n", ' ', str_replace("\r\n", "\n", $sql)));
   }


   // Abfrage abschicken
   if (!($resultSet = mysql_query($sql, $db['connection']))) {
      $error = ($errno = mysql_errno()) ? "SQL-Error $errno: ".mysql_error() : 'Database connection error';
      throw new RuntimeException($error."\nSQL: ".str_replace("\n", ' ', str_replace("\r\n", "\n", $sql)));
   }

   // Ergebnis der Abfrage auslesen
   $result = array('set'  => null,
                   'rows' => 0);

   if (is_resource($resultSet)) {
      $result['set']  =& $resultSet;
      $result['rows'] = mysql_num_rows($resultSet);                     // Anzahl der selektierten Zeilen
   }
   else {
      $sql = strToLower($sql);
      if (subStr($sql, 0, 6)=='insert' || subStr($sql, 0, 7)=='replace' || subStr($sql, 0, 6)=='update' || subStr($sql, 0, 6)=='delete') {
         $result['rows'] = mysql_affected_rows($db['connection']);      // Anzahl der aktualisierten Zeilen
      }
   }

   return $result;
}


/**
 * Startet eine neue Datenbank-Transaktion und signalisiert, ob eine neue Transaktion gestartet wurde.
 *
 * @param array $db - die zu verwendende Datenbankkonfiguration
 *
 * @return boolean - TRUE, wenn eine neue Transaktion gestartet wurde
 *                   FALSE, wenn sich an eine bereits aktive Transaktion angeschlossen wurde
 */
function beginTransaction(array &$db) {
   if (isSet($db['transaction']) && $db['transaction']) {
      $db['transaction'] = $db['transaction'] + 1;
      return false;
   }

   executeSql('begin', $db);
   $db['transaction'] = 1;
   return true;
}


/**
 * Schließt eine nicht verschachtelte Datenbank-Transaktion ab.  Ist die Transaktion eine verschachtelte Transaktion,
 * wird der Aufruf still ignoriert, da eine Transaktion immer von der höchsten Ebene aus geschlossen werden muß.
 *
 * @param array $db - die zu verwendende Datenbankkonfiguration
 *
 * @return boolean - TRUE, wenn die Transaktion abgeschlossen wurde
 *                   FALSE, wenn die verschachtelte Transaktion nicht abgeschlossen wurde
 */
function commitTransaction(array &$db) {
   if (!$db['connection']) {
      Logger::log('No database connection', L_ERROR);
      return false;
   }

   if (!isSet($db['transaction']) || !$db['transaction']) {
      LOGLEVEL_WARN && Logger::log('No database transaction to commit', L_WARN);
      return false;
   }

   if ($db['transaction'] > 1) {                      // Transaktionszähler herunterzählen und nichts weiter tun
      $db['transaction'] = $db['transaction'] - 1;
      return false;
   }

   executeSql('commit', $db);                         // committen
   $db['transaction'] = 0;
   return true;
}


/**
 * Rollt eine nicht verschachtelte Datenbank-Transaktion zurück.  Ist die Transaktion eine verschachtelte Transaktion,
 * wird der Aufruf still ignoriert, da eine Transaktion immer von der höchsten Ebene aus zurückgerollt werden muß.
 *
 * @param array $db - die zu verwendende Datenbankkonfiguration
 *
 * @return boolean - TRUE, wenn die Transaktion zurückgerollt wurde
 *                   FALSE, wenn die verschachtelte Transaktion nicht zurückgerollt wurde
 */
function rollbackTransaction(array &$db) {
   if (!$db['connection']) {
      Logger::log('No database connection', L_ERROR);
      return false;
   }

   if (!isSet($db['transaction']) || !$db['transaction']) {
      LOGLEVEL_WARN && Logger::log('No database transaction to roll back', L_WARN);
      return false;
   }

   if ($db['transaction'] > 1) {                      // Transaktionszähler herunterzählen und nichts weiter tun
      $db['transaction'] = $db['transaction'] - 1;
      return false;
   }

   executeSql('rollback', $db);                       // rollback
   $db['transaction'] = 0;
   return true;
}


/**
 * Gibt den Wert des 'Forwarded-IP'-Headers des aktuellen Request zurück.
 *
 * @return string - IP-Adresse oder NULL, wenn der entsprechende Header nicht gesetzt ist
 */
function getForwardedIP() {
   static $ip = false;

   if ($ip === false) {
      if (isSet($_SERVER['HTTP_X_FORWARDED_FOR'])) {
         $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }
      elseif (isSet($_SERVER['HTTP_HTTP_X_FORWARDED_FOR'])) {
         $ip = $_SERVER['HTTP_HTTP_X_FORWARDED_FOR'];
      }
      elseif (isSet($_SERVER['HTTP_X_UP_FORWARDED_FOR'])) {       // mobile device
         $ip = $_SERVER['HTTP_X_UP_FORWARDED_FOR'];
      }
      elseif (isSet($_SERVER['HTTP_HTTP_X_UP_FORWARDED_FOR'])) {  // mobile device
         $ip = $_SERVER['HTTP_HTTP_X_UP_FORWARDED_FOR'];
      }
      elseif (isSet($_SERVER[''])) {
         $ip = $_SERVER[''];
      }
      else {
         $ip = null;
      }
   }
   return $ip;
}


/**
 * Erzeugt eine zufällige ID (wegen Verwechselungsgefahr ohne die Zeichen 0 O 1 l I).
 *
 * @param int $length - Länge der ID
 *
 * @return string - ID
 */
function getRandomID($length) {
   if (!isSet($length) || !is_int($length) || $length < 1)
      throw new RuntimeException('Invalid argument length: '.$length);

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
 * Startet eine neue HTTP-Session oder setzt eine vorhergehende Session fort.
 * Ist die übergebene Session-ID ungültig, wird eine neue ID generiert.
 *
 * @return boolean - ob die resultierende Session neu ist oder nicht
 */
function startSession() {
   if (!isSession()) {
      try {
         session_start();
      }
      catch (PHPErrorException $error) {
         if (preg_match('/The session id contains illegal characters/', $error->getMessage())) {
            session_regenerate_id();
            return true;
         }
         throw $error;
      }
   }
   return isSessionNew();
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
function isSessionNew() {
   static $result = null;           // Ergebnis statisch zwischenspeichern

   if ($result === null) {
      if (isSession()) {                                                                  // eine Session existiert ...
         $sessionName = session_name();
         if (isSet($_REQUEST[$sessionName]) && $_REQUEST[$sessionName]==session_id()) {   // ... sie kommt vom Kunden
            $result = (sizeOf($_SESSION) == 0);                                           // eine leere Session muß neu sein
         }
         else {                                                                           // Session kommt nicht vom Kunden
            $result = true;
         }

         if (sizeOf($_SESSION) == 0) {                                                    // leere Session initialisieren
            $_SESSION['__INITIALIZED__'] = 1;
         }
      }
      else {                        // Session existiert nicht, könnte aber noch erzeugt werden, also Ergebnis (noch) nicht speichern
         return false;
      }
   }
   return $result;
}


/**
 * Entfernt alle gespeicherten Informationen aus der aktuellen Session.
 *
 * @return boolean - TRUE, wenn alle gespeicherten Informationen gelöscht wurden
 *                   FALSE, wenn keine Session existiert
 */
function clearSession() {
   if (isSession()) {
      $keys = array_keys($_SESSION);
      foreach ($keys as $key) {
         if ($key != '__INITIALIZED__')
            unSet($_SESSION[$key]);
      }
      return true;
   }
   return false;
}


/**
 * Sendet einen Redirect-Header mit der angegebenen URL. Danach wird das aktuelle Script beendet.
 *
 * @param string $url - URL
 */
function redirect($url) {
   if (isSession()) {
      if (isSessionNew() || SID !== '') {                      // bleiben wir innerhalb der Domain und Cookies sind aus, wird eine evt. Session-ID weitergegeben
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
 * Hilfsfunktion zur HTML-formatierten Ausgabe einer Variablen.
 *
 * @param mixed $var    - die auszugebende Variable
 * @param bool  $return - Ob die Ausgabe auf STDOUT erfolgen soll (FALSE) oder als Rückgabewert der Funktion (TRUE),
 *                        default ist false
 *
 * @return string - Rückgabewert, wenn $return TRUE ist, NULL andererseits.
 */
function printFormatted($var, $return = false) {
   if (is_object($var) && method_exists($var, '__toString')) {
      $str = $var->__toString();
   }
   elseif (is_object($var) || is_array($var)) {
      $str = print_r($var, true);
   }
   else {
      $str = (string) $var;
   }

   if (isSet($_SERVER['REQUEST_METHOD'])) {
      $str = '<div align="left"><pre style="margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.$str.'</pre></div>';
   }
   $str .= "\n";

   if ($return)
      return $str;

   ob_get_level() ? ob_flush() : flush();
   echo $str;
   return null;
}


/**
 * Alias für printFormatted($var, false).
 *
 * @param mixed $var - die auszugebende Variable
 */
function echoPre($var) {
   printFormatted($var, false);
}


/**
 * Gibt einen String als JavaScript aus.
 *
 * @param string $snippet - Code
 */
function javaScript($snippet) {
   echo '<script language="JavaScript">'.$snippet.'</script>';
}


/**
 * Gibt den aktuellen Errorlevel des Scripts in lesbarer Form zurück.
 *
 * @return string
 */
function getErrorLevelAsString() {
   $levels = array();
   $current = error_reporting();

   if (($current & E_ERROR            ) == E_ERROR            ) $levels[] = 'E_ERROR';
   if (($current & E_WARNING          ) == E_WARNING          ) $levels[] = 'E_WARNING';
   if (($current & E_PARSE            ) == E_PARSE            ) $levels[] = 'E_PARSE';
   if (($current & E_NOTICE           ) == E_NOTICE           ) $levels[] = 'E_NOTICE';
   if (($current & E_CORE_ERROR       ) == E_CORE_ERROR       ) $levels[] = 'E_CORE_ERROR';
   if (($current & E_CORE_WARNING     ) == E_CORE_WARNING     ) $levels[] = 'E_CORE_WARNING';
   if (($current & E_COMPILE_ERROR    ) == E_COMPILE_ERROR    ) $levels[] = 'E_COMPILE_ERROR';
   if (($current & E_COMPILE_WARNING  ) == E_COMPILE_WARNING  ) $levels[] = 'E_COMPILE_WARNING';
   if (($current & E_USER_ERROR       ) == E_USER_ERROR       ) $levels[] = 'E_USER_ERROR';
   if (($current & E_USER_WARNING     ) == E_USER_WARNING     ) $levels[] = 'E_USER_WARNING';
   if (($current & E_USER_NOTICE      ) == E_USER_NOTICE      ) $levels[] = 'E_USER_NOTICE';
   if (($current & E_RECOVERABLE_ERROR) == E_RECOVERABLE_ERROR) $levels[] = 'E_RECOVERABLE_ERROR';
   if (($current & E_ALL              ) == E_ALL              ) $levels[] = 'E_ALL';
   if (($current & E_STRICT           ) == E_STRICT           ) $levels[] = 'E_STRICT';

   return $current.": ".join(' | ', $levels);
}


/**
 * Dekodiert einen HTML-String nach ISO-8859-15.
 *
 * @param string $html - der zu dekodierende String
 *
 * @return string
 */
function decodeHtml($html) {
   if ($html === null || $html === '')
      return $html;

   static $table = null;
   if ($table === null) {
      $table = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES));
      $table['&nbsp;'] = ' ';
      $table['&euro;'] = '€';
   }
   $string = strTr($html, $table);
   return preg_replace('/&#(\d+);/me', "chr('\\1')", $string);
}


/**
 * Dekodiert einen UTF-8-kodierten String nach ISO-8859-1.
 *
 * @param string $string - der zu dekodierende String
 *
 * @return string
 */
function decodeUtf8($string) {
   if ($string === null || $string === '')
      return $string;

   return html_entity_decode(htmlEntities($string, ENT_NOQUOTES, 'UTF-8'));
}


/**
 * Addiert zu einem Datum eine Anzahl von Tagen.
 *
 * @param string $date - Ausgangsdatum (Format: yyyy-mm-dd)
 * @param int    $days - Tagesanzahl
 *
 * @return string - resultierendes Datum
 */
function addDate($date, $days) {
   if (!BaseValidator ::isDate($date)) throw new InvalidArgumentException('Invalid argument $date: '.$date);
   if (!is_int($days))                 throw new InvalidArgumentException('Invalid argument $days: '.$days);

   $parts = explode('-', $date);
   $year  = (int) $parts[0];
   $month = (int) $parts[1];
   $day   = (int) $parts[2];

   return date('Y-m-d', mkTime(0, 0, 0, $month, $day+$days, $year));
}


/**
 * Subtrahiert von einem Datum eine Anzahl von Tagen.
 *
 * @param string $date - Ausgangsdatum (Format: yyyy-mm-dd)
 * @param int    $days - Tagesanzahl
 *
 * @return string
 */
function subDate($date, $days) {
   if (!is_int($days)) throw new InvalidArgumentException('Invalid argument $days: '.$days);
   return addDate($date, -$days);
}


/**
 * Formatiert einen Date- oder DateTime-Wert mit dem angegebenen Format.
 *
 * @param string $format   - Formatstring (siehe PHP Manual zu date())
 * @param string $datetime - Datum oder Zeit
 *
 * @return string
 */
function formatDate($format, $datetime) {
   if ($datetime === null)
      return null;

   if ($datetime < '1970-01-01 00:00:00') {
      if ($format != 'd.m.Y') {
         LOGLEVEL_NOTICE && Logger::log('Cannot format early datetime '.$datetime.' with format: '.$format, L_NOTICE);
         return preg_replace('/[1-9]/', '0', date($format, time()));
      }

      $parts = explode('-', substr($datetime, 0, 10));
      return $parts[2].'.'.$parts[1].'.'.$parts[0];
   }

   $timestamp = strToTime($datetime);
   if (!is_int($timestamp))
      throw new InvalidArgumentException('Invalid argument $datetime: '.$datetime);

   return date($format, $timestamp);
}


/**
 * Formatiert einen Zahlenwert im Währungsformat.
 *
 * @param mixed  $value            - Zahlenwert (integer oder float)
 * @param int    $decimals         - Anzahl der Nachkommastellen
 * @param string $decimalSeparator - Dezimaltrennzeichen (entweder '.' oder ',')
 *
 * @return string
 */
function formatMoney($value, $decimals = 2, $decimalSeparator = ',') {
   if (!is_int($value) && !is_float($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
   if (!is_int($decimals))                   throw new IllegalTypeException('Illegal type of parameter $decimals: '.getType($decimals));

   if ($decimalSeparator == '.')
      return number_format($value, $decimals, '.', ',');

   if ($decimalSeparator == ',')
      return number_format($value, $decimals, ',', '.');

   throw new InvalidArgumentException('Invalid argument $decimalSeparator: '.$decimalSeparator);
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
            $params[] = $name.'='.urlEncode((string) $value);
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
function &getRequestHeaders() {
   if (function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      if ($headers === false) {
         Logger ::log('Error reading request headers, apache_request_headers(): false', L_WARN);
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
 * Ob unter dem angegebenen Schlüssel eine Error-Message existiert.  Ohne Angabe eines Schlüssel wird geprüft,
 * ob irgendeine Error-Message existiert. Existiert eine HttpSession, wird auch dort gesucht.
 *
 * @param string $key - Schlüssel
 *
 * @return boolean - TRUE, wenn eine Error-Message gefunden wurde,
 *                   FALSE andererseits
 */
function isActionError($key = null) {
   if ($key === null) {       // auf irgendeine prüfen
      if (isSet($_REQUEST['__ACTION_ERRORS__']) && sizeOf($_REQUEST['__ACTION_ERRORS__']) > 0)
         return true;

      if (isSession() && isSet($_SESSION['__ACTION_ERRORS__']) && sizeOf($_SESSION['__ACTION_ERRORS__']) > 0)
         return true;
   }
   else {                     // auf eine bestimmte prüfen
      if (isSet($_REQUEST['__ACTION_ERRORS__']) && isSet($_REQUEST['__ACTION_ERRORS__'][$key]))
         return true;

      if (isSession() && isSet($_SESSION['__ACTION_ERRORS__']) && isSet($_SESSION['__ACTION_ERRORS__'][$key]))
         return true;
   }
   return false;
}


/**
 * Gibt die Error-Message für den angegebenen Schlüssel zurück.  Ohne Schlüssel wird die erste vorhandene
 * Error-Message zurückgegeben.
 * Existiert eine HttpSession, wird auch dort gesucht.  Alle Error-Messages in der HttpSession werden nach
 * dem ersten Auslesen irgendeiner Error-Message gelöscht.
 *
 * @param string $key - Schlüssel der Error-Message
 *
 * @return string - Error-Message
 */
function getActionError($key = null) {
   if ($key === null) {       // die erste zurückgeben
      if (isSet($_REQUEST['__ACTION_ERRORS__'])) {
         foreach ($_REQUEST['__ACTION_ERRORS__'] as &$error)
            return $error;
      }

      if (isSession() && isSet($_SESSION['__ACTION_ERRORS__'])) {
         $errors = $_SESSION['__ACTION_ERRORS__'];
         unset($_SESSION['__ACTION_ERRORS__']);

         foreach ($errors as &$error)
            return $error;
      }
   }
   else {                     // eine bestimmte zurückgeben
      if (isSet($_REQUEST['__ACTION_ERRORS__'][$key]))
         return $_REQUEST['__ACTION_ERRORS__'][$key];

      if (isSession() && isSet($_SESSION['__ACTION_ERRORS__'])) {
         $errors = $_SESSION['__ACTION_ERRORS__'];
         unset($_SESSION['__ACTION_ERRORS__']);

         if (isSet($errors[$key]))
            return $errors[$key];
      }
   }
   return null;
}


/**
 * Setzt für den angegebenen Schlüssel eine Error-Message.
 *
 *
 * @param string $key     - Schlüssel der Error-Message
 * @param string $message - Error-Message
 * @param bool   $session - ob die Error-Message in der HttpSession gespeichert werden soll (per default wird sie im Request gesetzt)
 */
function setActionError($key, $message, $session = false) {
   if (!$session) {
      $_REQUEST['__ACTION_ERRORS__'][$key] = $message;
   }
   else {
      startSession();
      $_SESSION['__ACTION_ERRORS__'][$key] = $message;
   }
}


/**
 * Ist <tt>$value</tt> nicht NULL, gibt die Funktion <tt>$value</tt> zurück, andererseits die Alternative <tt>$alt</tt>.
 *
 * @return mixed
 */
function ifNull($value, $alt) {
   return ($value === null) ? $alt : $value;
}


/**
 * Ob ein String einen anderen String enthält.
 *
 * @param string $haystack - der zu prüfende String
 * @param string $needle   - der zu suchende String
 * @param boolean $case    - ob bei der Suche Groß-/Kleinschreibung beachtet werden soll (Default: nein)
 *
 * @return boolean
 */
function stringContains($haystack, $needle, $case = false) {
   if ($case)
      return (strPos($haystack, $needle) !== false);

   return (striPos($haystack, $needle) !== false);
}


/**
 * Ob ein String mit einem anderen String beginnt.
 *
 * @param string $haystack - der zu prüfende String
 * @param string $needle   - der zu suchende String
 * @param boolean $case    - ob bei der Suche Groß-/Kleinschreibung beachtet werden soll (Default: nein)
 *
 * @return boolean
 */
function stringStartsWith($haystack, $needle, $case = false) {
   if ($case)
      return (strPos($haystack, $needle) === 0);

   return (striPos($haystack, $needle) === 0);
}


/**
 * Ob ein String mit einem anderen String endet.
 *
 * @param string $haystack - der zu prüfende String
 * @param string $needle   - der zu suchende String
 * @param boolean $case    - ob bei der Suche Groß-/Kleinschreibung beachtet werden soll (Default: nein)
 *
 * @return boolean
 */
function stringEndsWith($haystack, $needle, $case = false) {
   return strStartsWith(strRev($haystack), strRev($needle), $case);
}


/*
Indicate that script is being called by CLI (vielleicht besser für $console)
----------------------------------------------------------------------------
if (php_sapi_name() == 'cli') {
   $CLI = true ;
}
*/
?>
