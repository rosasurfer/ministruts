<?
// Systemvoraussetzung: PHP 5.2+
// -----------------------------


// -----------------------------------------------------------------------------------------------------------------------------------
// ggf. Profiler starten
if (extension_loaded('APD') && isSet($_REQUEST['_PROFILER_'])) {
   $dumpFile = apd_set_pprof_trace(ini_get('apd.dumpdir'));

   if ($dumpFile) {
      // tatsächlichen Aufrufer des Scripts in weiterer Datei hinterlegen
      $fH = fOpen($dumpFile.'.caller', 'wb');
      fWrite($fH, 'caller='.apd_get_url()."\n\nEND_HEADER\n");
      fClose($fH);
      unset($fH);
   }

   register_shutdown_function('apd_shutdown_function', $dumpFile);
   unset($dumpFile);
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

$__classes['ChainableDependency'            ] = $dir.'php/dependency/ChainableDependency';
$__classes['ChainedDependency'              ] = $dir.'php/dependency/ChainedDependency';
$__classes['FileDependency'                 ] = $dir.'php/dependency/FileDependency';
$__classes['IDependency'                    ] = $dir.'php/dependency/IDependency';
$__classes['MaxAgeDependency'               ] = $dir.'php/dependency/MaxAgeDependency';

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

$__classes['FileLock'                       ] = $dir.'php/locking/FileLock';
$__classes['Lock'                           ] = $dir.'php/locking/Lock';
$__classes['SystemFiveLock'                 ] = $dir.'php/locking/SystemFiveLock';

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
$__classes['SMTPMailer'                     ] = $dir.'php/net/mail/SMTPMailer';

$__classes['BasePdfDocument'                ] = $dir.'php/pdf/BasePdfDocument';
$__classes['SimplePdfDocument'              ] = $dir.'php/pdf/SimplePdfDocument';

$__classes['Action'                         ] = $dir.'php/struts/Action';
$__classes['ActionForm'                     ] = $dir.'php/struts/ActionForm';
$__classes['ActionForward'                  ] = $dir.'php/struts/ActionForward';
$__classes['ActionMapping'                  ] = $dir.'php/struts/ActionMapping';
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
$__classes['Logger'                         ] = $dir.'php/util/Logger';
$__classes['String'                         ] = $dir.'php/util/String';

$__classes['ApdProfile'                     ] = $dir.'php/util/apd/ApdProfile';

unset($dir);



// Konstanten
// ----------
// die einzelnen Loglevel
define('L_DEBUG' ,  1);
define('L_INFO'  ,  2);
define('L_NOTICE',  4);
define('L_WARN'  ,  8);
define('L_ERROR' , 16);
define('L_FATAL' , 32);

// Zeitkonstanten
define('MINUTE' ,  60         ); define('MINUTES', MINUTE);
define('HOUR'   ,  60 * MINUTE); define('HOURS'  , HOUR  );
define('DAY'    ,  24 * HOUR  ); define('DAYS'   , DAY   );
define('WEEK'   ,   7 * DAY   ); define('WEEKS'  , WEEK  );
define('MONTH'  ,  30 * DAY   ); define('MONTHS' , MONTH );
define('YEAR'   , 365 * DAY   ); define('YEARS'  , YEAR  );

// ob wir unter Windows laufen
define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3))==='WIN'));



// Errorhandler registrieren (per anonymer Funktion, damit Logger nicht schon hier geladen und included wird)
// ----------------------------------------------------------------------------------------------------------
set_error_handler    (create_function('$level, $message, $file, $line, array $context', 'return Logger::handleError($level, $message, $file, $line, $context);'));
set_exception_handler(create_function('Exception $exception'                          , 'return Logger::handleException($exception);'                          ));



/**
 * Lädt die angegebene Klasse.
 *
 * @param string $className - Klassenname
 * @param mixed  $throw     - Ob Exceptions geworfen werfen dürfen. Typ und Wert des Parameters sind unwichtig,
 *                            seine Existenz allein reicht für die Erkennung eines manuellen Aufrufs.
 */
function __autoload($className /*, $throw */) {
   try {
      if (isSet($GLOBALS['__classes'][$className])) {
         $className = $GLOBALS['__classes'][$className];

         // TODO: autoload(): Warnen bei relativen Klassenpfaden
         /*
         $relative = WINDOWS ? !preg_match('/^[a-zA-Z]:/', $className) : (strPos($className, '/') !== 0);
         if ($relative)
            Logger ::log('Not an absolute class name definition: '.$className, L_WARN, __CLASS__);
         */

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
 * Abfrage der Art "if (class_exist($className, true))" __autoload() aufruft und dabei im Fehlerfall
 * das Script mit einem fatalen Fehler beendet (__autoload darf keine Exceptions werfen).
 * Wird __autoload aus dieser Funktion und nicht aus dem PHP-Kernel aufgerufen, werden Exceptions
 * weitergereicht und der folgende Code kann entsprechend reagieren.
 *
 * @param string $className - Klassenname
 *
 * @return boolean
 *
 * @see __autoload()
 */
function is_class($className) {
   if (class_exists($className, false))
      return true;

   try {
      return (bool) __autoload($className, true);
   }
   catch (ClassNotFoundException $ex) { /* yes, we eat it */ }

   return false;
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
 * Hilfsfunktion zur HTML-formatierten Ausgabe einer Variablen.
 *
 * @param mixed $var    - die auszugebende Variable
 * @param bool  $return - Ob die Ausgabe auf STDOUT erfolgen soll (FALSE) oder als Rückgabewert der Funktion (TRUE),
 *                        Default ist FALSE
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

   //ob_get_level() ? ob_flush() : flush();
   while (ob_get_level()) ob_end_flush();
   flush();

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
   if (!is_int($days))                   throw new InvalidArgumentException('Invalid argument $days: '.$days);

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
         Logger ::log(new RuntimeException('Cannot format early datetime '.$datetime.' with format: '.$format), L_INFO, __CLASS__);
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


/**
 * Nur für APD: Gibt die vollständige URL des Requests zurück.
 *
 * @return string
 */
function apd_get_url() {
   $proto = isSet($_SERVER['HTTPS']) ? 'https' : 'http';
   $host  = $_SERVER['SERVER_NAME'];
   $port  = $_SERVER['SERVER_PORT']=='80' ? '' : ':'.$_SERVER['SERVER_PORT'];
   $url   = "$proto://$host$port".$_SERVER['REQUEST_URI'];
   return $url;
}


/**
 * Nur für APD: Shutdown-Function, fügt nach dem Profiling einen Link zum Report in die Seite ein.
 */
function apd_shutdown_function($dumpFile = null) {
   if (!headers_sent())
      flush();

   // überprüfen, ob der aktuelle Content HTML ist (z.B. nicht bei Downloads)
   $isHTML = false;
   foreach (headers_list() as $header) {
      $parts = explode(':', $header, 2);
      if (strToLower($parts[0]) == 'content-type') {
         $isHTML = (striPos(trim($parts[1]), 'text/html') === 0);
         break;
      }
   }

   // bei HTML-Content Link auf Profiler-Report ausgeben
   if ($isHTML) {
      if ($dumpFile) {
         echo('<p style="clear:both; text-align:left; margin:6px"><a href="/apd/?file='.$dumpFile.'" target="apd">Profiling Report: '.baseName($dumpFile).'</a>');
      }
      else {
         echo('<p style="clear:both; text-align:left; margin:6px">Profiling Report: filename not available (old APD version ?)');
      }
   }
}
?>
