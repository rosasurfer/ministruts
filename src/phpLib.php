<?php
/**
 * Diese Datei inkludiert die komplette Funktionalität dieser Library.
 *
 * Systemvoraussetzungen: @see ../doc/FAQ
 */
define('PHPLIB_ROOT', dirName(__FILE__));


/**
 * TODO:
 * -----
 * - Konfiguration auf eine Datei (config.properties) reduzieren, Defaults in Klasse Config integrieren
 * - bin/cvs-update.sh muß je nach Aufruf Apache neustarten
 * - php/locking/SystemV-Lock.php: Bug beheben
 * - Erkennung von APC und APD verbessern (Exception, wenn Module aktiviert sind, aber nicht geladen werden können)
 * - erfolgreiches Laden notwendiger Module überprüfen (siehe APC-/APD-Bug)
 */


// phpInfo()-Aufrufe abfangen
// --------------------------
if (subStr($_SERVER['PHP_SELF'], -12) == '/phpinfo.php') {
   require(PHPLIB_ROOT.'/php/phpinfo.php');
   exit();
}
if (PHP_VERSION < '5.2.1') echoPre('Warning: You are running a buggy PHP version (at least version 5.2.1 is recommended).');


// Klassendefinitionen
// -------------------
$__classes['ApcCache'                       ] = PHPLIB_ROOT.'/php/cache/ApcCache';
$__classes['Cache'                          ] = PHPLIB_ROOT.'/php/cache/Cache';
$__classes['CachePeer'                      ] = PHPLIB_ROOT.'/php/cache/CachePeer';
$__classes['FileSystemCache'                ] = PHPLIB_ROOT.'/php/cache/FileSystemCache';
$__classes['ReferencePool'                  ] = PHPLIB_ROOT.'/php/cache/ReferencePool';

$__classes['Object'                         ] = PHPLIB_ROOT.'/php/core/Object';
$__classes['Singleton'                      ] = PHPLIB_ROOT.'/php/core/Singleton';
$__classes['StaticClass'                    ] = PHPLIB_ROOT.'/php/core/StaticClass';

$__classes['CommonDAO'                      ] = PHPLIB_ROOT.'/php/dao/CommonDAO';
$__classes['DaoWorker'                      ] = PHPLIB_ROOT.'/php/dao/DaoWorker';
$__classes['IDaoConnected'                  ] = PHPLIB_ROOT.'/php/dao/IDaoConnected';
$__classes['PersistableObject'              ] = PHPLIB_ROOT.'/php/dao/PersistableObject';

$__classes['DB'                             ] = PHPLIB_ROOT.'/php/db/DB';
$__classes['DBPool'                         ] = PHPLIB_ROOT.'/php/db/DBPool';
$__classes['MySQLConnector'                 ] = PHPLIB_ROOT.'/php/db/MySQLConnector';

$__classes['Dependency'                     ] = PHPLIB_ROOT.'/php/dependency/Dependency';
$__classes['ChainedDependency'              ] = PHPLIB_ROOT.'/php/dependency/ChainedDependency';
$__classes['FileDependency'                 ] = PHPLIB_ROOT.'/php/dependency/FileDependency';

$__classes['BusinessRuleException'          ] = PHPLIB_ROOT.'/php/exceptions/BusinessRuleException';
$__classes['ClassNotFoundException'         ] = PHPLIB_ROOT.'/php/exceptions/ClassNotFoundException';
$__classes['ConcurrentModificationException'] = PHPLIB_ROOT.'/php/exceptions/ConcurrentModificationException';
$__classes['DatabaseException'              ] = PHPLIB_ROOT.'/php/exceptions/DatabaseException';
$__classes['FileNotFoundException'          ] = PHPLIB_ROOT.'/php/exceptions/FileNotFoundException';
$__classes['IllegalAccessException'         ] = PHPLIB_ROOT.'/php/exceptions/IllegalAccessException';
$__classes['IllegalArgumentException'       ] = PHPLIB_ROOT.'/php/exceptions/IllegalArgumentException';
$__classes['IllegalStateException'          ] = PHPLIB_ROOT.'/php/exceptions/IllegalStateException';
$__classes['IllegalTypeException'           ] = PHPLIB_ROOT.'/php/exceptions/IllegalTypeException';
$__classes['InfrastructureException'        ] = PHPLIB_ROOT.'/php/exceptions/InfrastructureException';
$__classes['IOException'                    ] = PHPLIB_ROOT.'/php/exceptions/IOException';
$__classes['NestableException'              ] = PHPLIB_ROOT.'/php/exceptions/NestableException';
$__classes['PermissionDeniedException'      ] = PHPLIB_ROOT.'/php/exceptions/PermissionDeniedException';
$__classes['PHPErrorException'              ] = PHPLIB_ROOT.'/php/exceptions/PHPErrorException';
$__classes['plInvalidArgumentException'     ] = PHPLIB_ROOT.'/php/exceptions/plInvalidArgumentException';
$__classes['plRuntimeException'             ] = PHPLIB_ROOT.'/php/exceptions/plRuntimeException';
$__classes['UnimplementedFeatureException'  ] = PHPLIB_ROOT.'/php/exceptions/UnimplementedFeatureException';
$__classes['UnsupportedMethodException'     ] = PHPLIB_ROOT.'/php/exceptions/UnsupportedMethodException';

$__classes['BarCode'                        ] = PHPLIB_ROOT.'/php/file/image/barcode/BarCode';
$__classes['BaseC128BarCode'                ] = PHPLIB_ROOT.'/php/file/image/barcode/BaseC128BarCode';
$__classes['C128ABarCode'                   ] = PHPLIB_ROOT.'/php/file/image/barcode/C128ABarCode';
$__classes['C128BBarCode'                   ] = PHPLIB_ROOT.'/php/file/image/barcode/C128BBarCode';
$__classes['C128CBarCode'                   ] = PHPLIB_ROOT.'/php/file/image/barcode/C128CBarCode';
$__classes['C39BarCode'                     ] = PHPLIB_ROOT.'/php/file/image/barcode/C39BarCode';
$__classes['I25BarCode'                     ] = PHPLIB_ROOT.'/php/file/image/barcode/I25BarCode';

$__classes['BasePdfDocument'                ] = PHPLIB_ROOT.'/php/file/pdf/BasePdfDocument';
$__classes['SimplePdfDocument'              ] = PHPLIB_ROOT.'/php/file/pdf/SimplePdfDocument';

$__classes['BaseLock'                       ] = PHPLIB_ROOT.'/php/locking/BaseLock';
$__classes['FileLock'                       ] = PHPLIB_ROOT.'/php/locking/FileLock';
$__classes['Lock'                           ] = PHPLIB_ROOT.'/php/locking/Lock';
$__classes['SystemFiveLock'                 ] = PHPLIB_ROOT.'/php/locking/SystemFiveLock';

$__classes['NetTools'                       ] = PHPLIB_ROOT.'/php/net/NetTools';
$__classes['TorHelper'                      ] = PHPLIB_ROOT.'/php/net/TorHelper';

$__classes['CurlHttpClient'                 ] = PHPLIB_ROOT.'/php/net/http/CurlHttpClient';
$__classes['CurlHttpResponse'               ] = PHPLIB_ROOT.'/php/net/http/CurlHttpResponse';
$__classes['HeaderParser'                   ] = PHPLIB_ROOT.'/php/net/http/HeaderParser';
$__classes['HeaderUtils'                    ] = PHPLIB_ROOT.'/php/net/http/HeaderUtils';
$__classes['HttpClient'                     ] = PHPLIB_ROOT.'/php/net/http/HttpClient';
$__classes['HttpRequest'                    ] = PHPLIB_ROOT.'/php/net/http/HttpRequest';
$__classes['HttpResponse'                   ] = PHPLIB_ROOT.'/php/net/http/HttpResponse';

$__classes['CLIMailer'                      ] = PHPLIB_ROOT.'/php/net/mail/CLIMailer';
$__classes['FileSocketMailer'               ] = PHPLIB_ROOT.'/php/net/mail/FileSocketMailer';
$__classes['Mailer'                         ] = PHPLIB_ROOT.'/php/net/mail/Mailer';
$__classes['PHPMailer'                      ] = PHPLIB_ROOT.'/php/net/mail/PHPMailer';
$__classes['SMTPMailer'                     ] = PHPLIB_ROOT.'/php/net/mail/SMTPMailer';

$__classes['Action'                         ] = PHPLIB_ROOT.'/php/struts/Action';
$__classes['ActionForm'                     ] = PHPLIB_ROOT.'/php/struts/ActionForm';
$__classes['ActionForward'                  ] = PHPLIB_ROOT.'/php/struts/ActionForward';
$__classes['ActionMapping'                  ] = PHPLIB_ROOT.'/php/struts/ActionMapping';
$__classes['FrontController'                ] = PHPLIB_ROOT.'/php/struts/FrontController';
$__classes['HttpSession'                    ] = PHPLIB_ROOT.'/php/struts/HttpSession';
$__classes['Module'                         ] = PHPLIB_ROOT.'/php/struts/Module';
$__classes['PageContext'                    ] = PHPLIB_ROOT.'/php/struts/PageContext';
$__classes['Request'                        ] = PHPLIB_ROOT.'/php/struts/Request';
$__classes['RequestBase'                    ] = PHPLIB_ROOT.'/php/struts/RequestBase';
$__classes['RequestProcessor'               ] = PHPLIB_ROOT.'/php/struts/RequestProcessor';
$__classes['Response'                       ] = PHPLIB_ROOT.'/php/struts/Response';
$__classes['RoleProcessor'                  ] = PHPLIB_ROOT.'/php/struts/RoleProcessor';
$__classes['Struts'                         ] = PHPLIB_ROOT.'/php/struts/Struts';
$__classes['Tile'                           ] = PHPLIB_ROOT.'/php/struts/Tile';

$__classes['CommonValidator'                ] = PHPLIB_ROOT.'/php/util/CommonValidator';
$__classes['Config'                         ] = PHPLIB_ROOT.'/php/util/Config';
$__classes['Date'                           ] = PHPLIB_ROOT.'/php/util/Date';
$__classes['Logger'                         ] = PHPLIB_ROOT.'/php/util/Logger';
$__classes['PHP'                            ] = PHPLIB_ROOT.'/php/util/PHP';
$__classes['String'                         ] = PHPLIB_ROOT.'/php/util/String';

$__classes['ApdProfile'                     ] = PHPLIB_ROOT.'/php/util/apd/ApdProfile';


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

// Wochentage
define('SUNDAY'   , 0);
define('MONDAY'   , 1);
define('TUESDAY'  , 2);
define('WEDNESDAY', 3);
define('THURSDAY' , 4);
define('FRIDAY'   , 5);
define('SATURDAY' , 6);

// ob wir unter Windows laufen
define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3))==='WIN'));


// Errorhandler anonym registrieren, damit die Klasse nicht schon hier geladen wird
// --------------------------------------------------------------------------------
set_error_handler    (create_function('$level, $message, $file, $line, array $context', 'return Logger::handleError($level, $message, $file, $line, $context);'));
set_exception_handler(create_function('Exception $exception'                          , 'Logger::handleException($exception); exit(1);'                        ));   // exit code = 1 forcieren


// Beginn des Shutdowns markieren, um fatale Fehler beim Shutdown zu verhindern (siehe Logger)
// -------------------------------------------------------------------------------------------
register_shutdown_function(create_function(null, '$GLOBALS[\'$__shutting_down\'] = true;'));    // allererste Funktion auf dem Shutdown-Funktion-Stack


// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// ggf. Profiler starten
if (extension_loaded('APD') && isSet($_REQUEST['_PROFILE_'])) {
   $dumpFile = apd_set_pprof_trace(ini_get('apd.dumpdir'));
   if ($dumpFile) {
      // tatsächlichen Aufrufer des Scripts in weiterer Datei hinterlegen
      if (!isSet($_SERVER['REQUEST_METHOD'])) {                      // Konsolenaufruf ...
         $caller = $_SERVER['PHP_SELF'];
      }
      else {                                                         // ... oder Webserver-Request
         $protocol = isSet($_SERVER['HTTPS']) ? 'https':'http';
         $host     = $_SERVER['SERVER_NAME'];
         $port     = $_SERVER['SERVER_PORT']=='80' ? '':':'.$_SERVER['SERVER_PORT'];
         $caller = "$protocol://$host$port".$_SERVER['REQUEST_URI'];
      }
      $fH = fOpen($dumpFile.'.caller', 'wb');
      fWrite($fH, "caller=$caller\n\nEND_HEADER\n");
      fClose($fH);
   }
   push_shutdown_function('apd_addProfileLink', $dumpFile);          // wird als letzte Shutdown-Funktion ausgeführt
   unset($dumpFile, $fH, $prot, $host, $port, $caller);
}


/**
 * Nur für Profiler: Shutdown-Function, fügt nach dem Profiling einen Link zum Report in die Seite ein.
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
      else           echo('<p style="clear:both; text-align:left; margin:6px">Profiling Report: filename not available (console or old APD version ?)');
   }
   else if (!isSet($_SERVER['REQUEST_METHOD'])) {  // Konsolenaufruf
      echo("dumpfile = $dumpFile");
   }
}
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------


/**
 * Class-Loader, lädt die angegebene Klasse.
 *
 * @param string $className - Klassenname
 * @param mixed  $throw     - Ob Exceptions geworfen werfen dürfen. Typ und Wert des Parameters sind unwichtig,
 *                            einzig seine Existenz reicht für die Erkennung eines manuellen Aufrufs.
 */
function __autoload($className /*, $throw */) {
   try {
      if (isSet($GLOBALS['__classes'][$className])) {
         // Fällt der automatische Aufruf der  __autoload()-Funktion aus (z.B. in PHP5.3), wird die Funktion ggf. manuell
         // aufgerufen.  Um dabei Mehrfach-Includes derselben Klasse *ohne* Verwendung von include_once() verhindern zu können,
         // setzen wir nach dem ersten Laden ein entsprechendes Flag. Die Verwendung von include_once() würde den Opcode-Cache
         // verlangsamen.
         //
         // @see https://bugs.php.net/bug.php?id=47987

         // Rückkehr, wenn Klasse schon geladen
         if ($GLOBALS['__classes'][$className] === true)
            return true;

         $fileName = $GLOBALS['__classes'][$className];

         // Warnen bei relativem Pfad (relative Pfade verschlechtern Performance, ganz besonders mit APC-Setting 'apc.stat=0')
         $relative = WINDOWS ? !preg_match('/^[a-z]:/i', $fileName) : ($fileName{0} != '/');
         if ($relative)
            Logger ::log('Relative file name for class definition: '.$fileName, L_WARN, __CLASS__);

         unset($relative);
         include($fileName.'.php');

         // Klasse als geladen markieren
         $GLOBALS['__classes'][$className] = true;
         return true;
      }
      throw new ClassNotFoundException("Undefined class '$className'");
   }
   catch (Exception $ex) {
      if (func_num_args() > 1)         // Exceptions nur bei manuellem Aufruf werfen
         throw $ex;

      Logger ::handleException($ex);   // Aufruf durch den PHP-Kernel: Exception manuell verarbeiten
      exit(1);
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
 * @return bool
 *
 *
 * NOTE: Das eine Klasse definiert ist, bedeutet noch nicht, daß sie auch geladen (inkludiert) ist.
 *       @see __autoload()
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
 * @return bool
 *
 * NOTE: Das ein Interface definiert ist, bedeutet noch nicht, daß es auch geladen (inkludiert) ist.
 *       @see __autoload()
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
   if (!is_callable($callback, false, $name))         throw new plInvalidArgumentException('Invalid callback "'.$name.'" passed');

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
      throw new plRuntimeException('Invalid argument length: '.$length);

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
 * @param bool  $return - TRUE, wenn das Ergebnis zurückgegeben werden soll;
 *                        FALSE, wenn das Ergebnis auf STDOUT ausgegeben werden soll (default)
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
   if (!CommonValidator ::isDate($date)) throw new plInvalidArgumentException('Invalid argument $date: '.$date);
   if ($days!==(int)$days)               throw new plInvalidArgumentException('Invalid argument $days: '.$days);

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
   if ($days!==(int)$days) throw new plInvalidArgumentException('Invalid argument $days: '.$days);
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
      throw new plInvalidArgumentException('Invalid argument $datetime: '.$datetime);

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

   throw new plInvalidArgumentException('Invalid argument $decimalSeparator: '.$decimalSeparator);
}


/**
 * Pretty printer for byte values.
 *
 * @param int $value - byte value
 *
 * @return string
 */
function byteSize($value) {
   if ($value < 1024)
      return (string) $value;

   foreach (array('K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y') as $unit) {
      $value /= 1024;
      if ($value < 1024)
         break;
   }
   return sPrintF('%.1f%s', $value, $unit);
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
