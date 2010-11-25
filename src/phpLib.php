<?
/**
 * Inkludiert die gesamte MiniStruts-Library.
 *
 * Systemvoraussetzung: PHP 5.2+
 */

// Errorhandler registrieren (anonym, damit die Klasse nicht schon hier included wird)
// -----------------------------------------------------------------------------------
set_error_handler    (create_function('$level, $message, $file, $line, array $context', 'return Logger::handleError($level, $message, $file, $line, $context);'));
set_exception_handler(create_function('Exception $exception'                          , 'return Logger::handleException($exception);'                          ));


// Beginn des Shutdowns markieren (um fatale Fehler beim Shutdown zu verhindern; siehe Logger)
// -------------------------------------------------------------------------------------------
register_shutdown_function(create_function(null, '$GLOBALS[\'$__shutting_down\'] = true;'));    // wird als erste Shutdown-Funktion ausgeführt


// -----------------------------------------------------------------------------------------------------------------------------------
// ggf. Profiler starten
if (extension_loaded('APD') && isSet($_REQUEST['_PROFILE_'])) {
   $dumpFile = apd_set_pprof_trace(ini_get('apd.dumpdir'));
   if ($dumpFile) {
      // tatsächlichen Aufrufer des Scripts in weiterer Datei hinterlegen
      $prot = isSet($_SERVER['HTTPS']) ? 'https':'http';
      $host = $_SERVER['SERVER_NAME'];
      $port = $_SERVER['SERVER_PORT']=='80' ? '':':'.$_SERVER['SERVER_PORT'];
      $url  = "$prot://$host$port".$_SERVER['REQUEST_URI'];

      $fH = fOpen($dumpFile.'.caller', 'wb');
      fWrite($fH, "caller=$url\n\nEND_HEADER\n");
      fClose($fH);
   }
   push_shutdown_function('apd_addProfileLink', $dumpFile);    // wird als letzte Shutdown-Funktion ausgeführt
   unset($dumpFile, $fH, $prot, $host, $port, $url);

}


/**
 * Nur für den Profiler: Shutdown-Function, fügt nach dem Profiling einen Link zum Report in die Seite ein.
 */
function apd_addProfileLink($dumpFile = null) {
   if (!headers_sent())
      flush();

   // überprüfen, ob der aktuelle Content HTML ist (z.B. nicht bei Downloads)
   $html = false;
   foreach (headers_list() as $header) {
      $parts = explode(':', $header, 2);
      if (strToLower($parts[0]) == 'content-type') {
         $html = (striPos(trim($parts[1]), 'text/html') === 0);
         break;
      }
   }

   // bei HTML-Content Link auf Profiler-Report ausgeben
   if ($html) {
      if ($dumpFile) echo('<p style="clear:both; text-align:left; margin:6px"><a href="/apd/?file='.$dumpFile.'" target="apd">Profiling Report: '.baseName($dumpFile).'</a>');
      else           echo('<p style="clear:both; text-align:left; margin:6px">Profiling Report: filename not available (old APD version ?)');
   }
}
// -----------------------------------------------------------------------------------------------------------------------------------



// Klassendefinitionen
// -------------------
$dir = dirName(__FILE__).DIRECTORY_SEPARATOR;

$__classes['ApcCache'                       ] = $dir.'php/cache/ApcCache';
$__classes['Cache'                          ] = $dir.'php/cache/Cache';
$__classes['CachePeer'                      ] = $dir.'php/cache/CachePeer';
$__classes['FileSystemCache'                ] = $dir.'php/cache/FileSystemCache';
$__classes['ReferencePool'                  ] = $dir.'php/cache/ReferencePool';

$__classes['Object'                         ] = $dir.'php/core/Object';
$__classes['Singleton'                      ] = $dir.'php/core/Singleton';
$__classes['StaticClass'                    ] = $dir.'php/core/StaticClass';

$__classes['CommonDAO'                      ] = $dir.'php/dao/CommonDAO';
$__classes['DaoWorker'                      ] = $dir.'php/dao/DaoWorker';
$__classes['IDaoConnected'                  ] = $dir.'php/dao/IDaoConnected';
$__classes['PersistableObject'              ] = $dir.'php/dao/PersistableObject';

$__classes['DB'                             ] = $dir.'php/db/DB';
$__classes['DBPool'                         ] = $dir.'php/db/DBPool';
$__classes['MySQLConnector'                 ] = $dir.'php/db/MySQLConnector';

$__classes['Dependency'                     ] = $dir.'php/dependency/Dependency';
$__classes['ChainedDependency'              ] = $dir.'php/dependency/ChainedDependency';
$__classes['FileDependency'                 ] = $dir.'php/dependency/FileDependency';

$__classes['BusinessRuleException'          ] = $dir.'php/exceptions/BusinessRuleException';
$__classes['ClassNotFoundException'         ] = $dir.'php/exceptions/ClassNotFoundException';
$__classes['ConcurrentModificationException'] = $dir.'php/exceptions/ConcurrentModificationException';
$__classes['DatabaseException'              ] = $dir.'php/exceptions/DatabaseException';
$__classes['FileNotFoundException'          ] = $dir.'php/exceptions/FileNotFoundException';
$__classes['IOException'                    ] = $dir.'php/exceptions/IOException';
$__classes['IllegalAccessException'         ] = $dir.'php/exceptions/IllegalAccessException';
$__classes['IllegalStateException'          ] = $dir.'php/exceptions/IllegalStateException';
$__classes['IllegalTypeException'           ] = $dir.'php/exceptions/IllegalTypeException';
$__classes['InfrastructureException'        ] = $dir.'php/exceptions/InfrastructureException';
$__classes['InvalidArgumentException'       ] = $dir.'php/exceptions/InvalidArgumentException';
$__classes['NestableException'              ] = $dir.'php/exceptions/NestableException';
$__classes['NoPermissionException'          ] = $dir.'php/exceptions/NoPermissionException';
$__classes['PHPErrorException'              ] = $dir.'php/exceptions/PHPErrorException';
$__classes['RuntimeException'               ] = $dir.'php/exceptions/RuntimeException';
$__classes['UnimplementedFeatureException'  ] = $dir.'php/exceptions/UnimplementedFeatureException';
$__classes['UnsupportedMethodException'     ] = $dir.'php/exceptions/UnsupportedMethodException';

$__classes['BarCode'                        ] = $dir.'php/file/image/barcode/BarCode';
$__classes['C128ABarCode'                   ] = $dir.'php/file/image/barcode/C128ABarCode';
$__classes['C128BBarCode'                   ] = $dir.'php/file/image/barcode/C128BBarCode';
$__classes['C128CBarCode'                   ] = $dir.'php/file/image/barcode/C128CBarCode';
$__classes['C39BarCode'                     ] = $dir.'php/file/image/barcode/C39BarCode';
$__classes['I2Of5BarCode'                   ] = $dir.'php/file/image/barcode/I2Of5BarCode';

$__classes['BasePdfDocument'                ] = $dir.'php/file/pdf/BasePdfDocument';
$__classes['SimplePdfDocument'              ] = $dir.'php/file/pdf/SimplePdfDocument';

$__classes['BaseLock'                       ] = $dir.'php/locking/BaseLock';
$__classes['FileLock'                       ] = $dir.'php/locking/FileLock';
$__classes['Lock'                           ] = $dir.'php/locking/Lock';
$__classes['SystemFiveLock'                 ] = $dir.'php/locking/SystemFiveLock';

$__classes['NetTools'                       ] = $dir.'php/net/NetTools';
$__classes['TorHelper'                      ] = $dir.'php/net/TorHelper';

$__classes['CurlHttpClient'                 ] = $dir.'php/net/http/CurlHttpClient';
$__classes['CurlHttpResponse'               ] = $dir.'php/net/http/CurlHttpResponse';
$__classes['HeaderParser'                   ] = $dir.'php/net/http/HeaderParser';
$__classes['HeaderUtils'                    ] = $dir.'php/net/http/HeaderUtils';
$__classes['HttpClient'                     ] = $dir.'php/net/http/HttpClient';
$__classes['HttpRequest'                    ] = $dir.'php/net/http/HttpRequest';
$__classes['HttpResponse'                   ] = $dir.'php/net/http/HttpResponse';

$__classes['CLIMailer'                      ] = $dir.'php/net/mail/CLIMailer';
$__classes['FileSocketMailer'               ] = $dir.'php/net/mail/FileSocketMailer';
$__classes['Mailer'                         ] = $dir.'php/net/mail/Mailer';
$__classes['PHPMailer'                      ] = $dir.'php/net/mail/PHPMailer';
$__classes['SMTPMailer'                     ] = $dir.'php/net/mail/SMTPMailer';

$__classes['Action'                         ] = $dir.'php/struts/Action';
$__classes['ActionForm'                     ] = $dir.'php/struts/ActionForm';
$__classes['ActionForward'                  ] = $dir.'php/struts/ActionForward';
$__classes['ActionMapping'                  ] = $dir.'php/struts/ActionMapping';
$__classes['BaseRequest'                    ] = $dir.'php/struts/BaseRequest';
$__classes['FrontController'                ] = $dir.'php/struts/FrontController';
$__classes['HttpSession'                    ] = $dir.'php/struts/HttpSession';
$__classes['Module'                         ] = $dir.'php/struts/Module';
$__classes['PageContext'                    ] = $dir.'php/struts/PageContext';
$__classes['Request'                        ] = $dir.'php/struts/Request';
$__classes['RequestProcessor'               ] = $dir.'php/struts/RequestProcessor';
$__classes['Response'                       ] = $dir.'php/struts/Response';
$__classes['RoleProcessor'                  ] = $dir.'php/struts/RoleProcessor';
$__classes['Struts'                         ] = $dir.'php/struts/Struts';
$__classes['Tile'                           ] = $dir.'php/struts/Tile';

$__classes['CommonValidator'                ] = $dir.'php/util/CommonValidator';
$__classes['Config'                         ] = $dir.'php/util/Config';
$__classes['Date'                           ] = $dir.'php/util/Date';
$__classes['Logger'                         ] = $dir.'php/util/Logger';
$__classes['String'                         ] = $dir.'php/util/String';

$__classes['ApdProfile'                     ] = $dir.'php/util/apd/ApdProfile';

unset($dir);


// Loglevel
define('L_DEBUG' ,  1);
define('L_INFO'  ,  2);
define('L_NOTICE',  4);
define('L_WARN'  ,  8);
define('L_ERROR' , 16);
define('L_FATAL' , 32);

// Zeitkonstanten
define('SECOND' ,  1          ); define('SECONDS', SECOND);
define('MINUTE' , 60 * SECONDS); define('MINUTES', MINUTE);
define('HOUR'   , 60 * MINUTES); define('HOURS'  , HOUR  );
define('DAY'    , 24 * HOURS  ); define('DAYS'   , DAY   );
define('WEEK'   ,  7 * DAYS   ); define('WEEKS'  , WEEK  );

// ob wir unter Windows laufen
define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3))==='WIN'));



/**
 * Class-Loader, lädt die angegebene Klasse.
 *
 * @param string $className - Klassenname
 * @param mixed  $throw     - Ob Exceptions geworfen werfen dürfen. Typ und Wert des Parameters sind unwichtig,
 *                            seine Existenz reicht für die Erkennung eines manuellen Aufrufs.
 */
function __autoload($className /*, $throw */) {
   try {
      if (isSet($GLOBALS['__classes'][$className])) {
         $className = $GLOBALS['__classes'][$className];

         // Warnen bei relativem Pfad (verschlechtert Performance, ganz besonders mit APC-Setting 'apc.stat=0')
         $relative = WINDOWS ? !preg_match('/^[a-z]:/i', $className) : ($className{0} != '/');
         if ($relative)
            Logger ::log('Relative file name for class definition: '.$className, L_WARN, __CLASS__);

         // clean up the local scope, then include the file
         unset($relative);
         include($className.'.php');
         return true;
      }
      throw new ClassNotFoundException("Undefined class '$className'");
   }
   catch (Exception $ex) {
      if (func_num_args() > 1)         // Exceptions nur bei manuellem Aufruf werfen
         throw $ex;
      Logger ::handleException($ex);   // Aufruf durch den PHP-Kernel: Exception manuell verarbeiten
   }                                   // (__autoload darf keine Exceptions werfen)
   return false;
}


/**
 * Ob der angegebene Klassenname definiert ist.  Diese Funktion ist notwendig, weil eine einfache
 * Abfrage der Art "if (class_exist($className, true))" __autoload() aufruft und bei Nichtexistenz der
 * Klasse das Script mit einem fatalen Fehler beendet (Exceptions statt eines Fehlers sind in __autoload()
 * nicht möglich).
 * Wird __autoload() direkt aus dieser Funktion und nicht von PHP aufgerufen, werden Exceptions weitergereicht
 * und der folgende Code kann entsprechend reagieren.
 *
 * @param string $name - Klassenname
 *
 * @return boolean
 *
 * @see __autoload()
 */
function is_class($name) {
   if (class_exists($name, false))
      return true;

   try {
      return (bool) __autoload($name, true);
   }
   catch (ClassNotFoundException $ex) {
      /* Ja, die Exception wird absichtlich verschluckt. */
   }
   return false;
}


/**
 * Ob der angegebene Interface-Name definiert ist.  Diese Funktion ist notwendig, weil eine einfache
 * Abfrage der Art "if (interface_exist($interfaceName, true))" __autoload() aufruft und bei Nichtexistenz
 * des Interfaces das Script mit einem fatalen Fehler beendet (Exceptions statt eines Fehlers sind in
 * __autoload() nicht möglich).
 * Wird __autoload() direkt aus dieser Funktion und nicht von PHP aufgerufen, werden Exceptions weitergereicht
 * und der folgende Code kann entsprechend reagieren.
 *
 * @param string $name - Interface-Name
 *
 * @return boolean
 *
 * @see __autoload()
 */
function is_interface($name) {
   if (interface_exists($name, false))
      return true;

   try {
      return (bool) __autoload($name, true);
   }
   catch (ClassNotFoundException $ex) {
      /* Ja, die Exception wird absichtlich verschluckt. */
   }
   return false;
}


/**
 * Registriert wie register_shutdown_function() Funktionen zur Ausführung während des Shutdowns.  Die
 * Funktionen werden jedoch nicht in der Reihenfolge der Registrierung aufgerufen, sondern auf einen Stack
 * gelegt und während des Shutdowns von dort abgerufen (stacktypisch Last-In-First-Out).  Alle zusätzlich
 * übergebenen Argumente werden beim Aufruf an die Funktion weitergereicht.
 *
 * @param callable $callback - Funktion oder Methode, die ausgeführt werden soll
 *
 * @see register_shutdown_function()
 */
function push_shutdown_function(/*callable*/ $callback = null /*, $args1, $args2, ...*/) {
   static $stack = array();
   if (!$stack)
      register_shutdown_function(__FUNCTION__);    // beim 1. Aufruf wird die Funktion selbst als Shutdown-Handler registriert

   if ($callback === null) {
      $trace = debug_backTrace();
      $frame = array_pop($trace);

      if (!isSet($frame['file']) && !isSet($frame['line'])) {     // wenn Aufruf aus PHP-Core (also während Shutdown) ...
         try {
            for ($i=sizeOf($stack); $i; ) {                       // ... alle Funktionen auf dem Stack abarbeiten
               $f = $stack[--$i];
               call_user_func_array($f['name'], $f['args']);
            }
         }
         catch (Exception $ex) {
            Logger::log($ex, L_FATAL, __CLASS__);
         }
         return;
      }
   }

   $name = null;
   if (!is_string($callback) && !is_array($callback)) throw new IllegalTypeException('Illegal type of parameter $callback: '.getType($callback));
   if (!is_callable($callback, false, $name))         throw new InvalidArgumentException('Invalid callback "'.$name.'" passed');

   $args = func_get_args();
   array_shift($args);

   $stack[] = array('name' => $callback,
                    'args' => $args);
}


/**
 * Erzeugt eine zufällige ID (wegen Verwechselungsgefahr ohne die Zeichen 0 O 1 l I).
 *
 * @param int $length - Länge der ID
 *
 * @return string - ID
 */
function getRandomID($length) {
   if (!isSet($length) || ($length!==(int)$length) || $length < 1)
      throw new RuntimeException('Invalid argument length: '.$length);

   $id = crypt(uniqId(rand(), true));              // zufällige ID erzeugen
   $id = strip_tags(stripSlashes($id));            // Sonder- und leicht zu verwechselnde Zeichen entfernen
   $id = strRev(str_replace('/', '', str_replace('.', '', str_replace('$', '', str_replace('0', '', str_replace('O', '', str_replace('1', '', str_replace('l', '', str_replace('I', '', $id)))))))));
   $len = strLen($id);
   if ($len < $length) {
      $id .= getRandomID($length-$len);            // bis zur gewünschten Länge vergrößern ...
   }
   else {
      $id = subStr($id, 0, $length);               // oder auf die gewünschte Länge kürzen
   }
   return $id;
}


/**
 * Hilfsfunktion zur formatierten Ausgabe einer Variable.
 *
 * @param mixed $var    - die auszugebende Variable
 * @param bool  $return - Ob die Ausgabe auf STDOUT erfolgen soll (FALSE) oder als Rückgabewert der Funktion (TRUE).
 *                        (default: FALSE)
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

   if (isSet($_SERVER['REQUEST_METHOD']))
      $str = '<div align="left"><pre style="margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.htmlSpecialChars($str, ENT_QUOTES).'</pre></div>';
   $str .= "\n";

   if ($return)
      return $str;

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
 * Gibt den Inhalt einer Variable aus.
 *
 * @param mixed $var    - Variable
 * @param bool  $return - Ob die Ausgabe auf STDOUT erfolgen soll (FALSE) oder als Rückgabewert der Funktion (TRUE).
 *                        (default: FALSE)
 */
function dump($var, $return = false) {
   if ($return) ob_start();

   var_dump($var);

   if ($return) return ob_get_clean();
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
 * Shortcut-Ersatz für String::htmlSpecialChars()
 *
 * @param string $string - zu kodierender String
 *
 * @return string - kodierter String
 *
 * Note:
 * -----
 * The translations performed are:
 *    '&' (ampersand) becomes '&amp;'
 *    '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set
 *    ''' (single quote) becomes '&#039;' only when ENT_QUOTES is set
 *    '<' (less than) becomes '&lt;'
 *    '>' (greater than) becomes '&gt;'
 *
 * @see String::htmlSpecialChars()
 */
function htmlEncode($string) {
   if (PHP_VERSION < '5.2.3')
      return htmlSpecialChars($string, ENT_QUOTES, 'ISO-8859-1');

   return htmlSpecialChars($string, ENT_QUOTES, 'ISO-8859-1', true);
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
 * Addiert zu einem Datum eine Anzahl von Tagen.
 *
 * @param string $date - Ausgangsdatum (Format: yyyy-mm-dd)
 * @param int    $days - Tagesanzahl
 *
 * @return string - resultierendes Datum
 */
function addDate($date, $days) {
   if (!CommonValidator ::isDate($date)) throw new InvalidArgumentException('Invalid argument $date: '.$date);
   if ($days!==(int)$days)               throw new InvalidArgumentException('Invalid argument $days: '.$days);

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
   if ($days!==(int)$days) throw new InvalidArgumentException('Invalid argument $days: '.$days);
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
         Logger ::log('Cannot format early datetime "'.$datetime.'" with format "'.$format.'"', L_INFO, __CLASS__);
         return preg_replace('/[1-9]/', '0', date($format, time()));
      }

      $parts = explode('-', substr($datetime, 0, 10));
      return $parts[2].'.'.$parts[1].'.'.$parts[0];
   }

   $timestamp = strToTime($datetime);
   if ($timestamp!==(int)$timestamp)
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
   if ($value!==(int)$value && $value!==(float)$value) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
   if ($decimals!==(int)$decimals)                     throw new IllegalTypeException('Illegal type of parameter $decimals: '.getType($decimals));

   if ($decimalSeparator == '.')
      return number_format($value, $decimals, '.', ',');

   if ($decimalSeparator == ',')
      return number_format($value, $decimals, ',', '.');

   throw new InvalidArgumentException('Invalid argument $decimalSeparator: '.$decimalSeparator);
}


/**
 * Pretty printer for byte values.
 *
 * @param int $value - byte value
 *
 * @return string
 */
function byteSize($value) {
   foreach (array('', 'K', 'M', 'G') as $unit) {
      if ($value < 1024)
         break;
      $value /= 1024;
   }
   return sPrintF('%5.1f %sB', $value, $unit);
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
 * Ist $value nicht NULL, gibt die Funktion $value zurück, ansonsten die Alternative $altValue.
 *
 * @return mixed
 */
function ifNull($value, $altValue) {
   return ($value === null) ? $altValue : $value;
}
?>
