<?php
/**
 * Diese Datei inkludiert die komplette Funktionalität dieser Library.
 *
 * Systemvoraussetzungen: @see ../doc/project.skel/index.php
 *
 *
 * TODO:
 * -----
 * - Konfiguration auf eine Datei (config.properties) reduzieren, Defaults in Klasse Config integrieren
 * - bin/cvs-update.sh muß je nach Aufruf Apache neustarten
 * - php/locking/SystemV-Lock.php: Bug beheben
 * - Erkennung von APC und APD verbessern (Exception, wenn Module aktiviert sind, aber nicht geladen werden können)
 * - erfolgreiches Laden notwendiger Module überprüfen (siehe APC-/APD-Bug)
 */

// Mehrfach-Includes abfangen
if (defined('PHPLIB_ROOT'))
   return;
define('PHPLIB_ROOT', dirName(__FILE__));


// Anwendungskonfiguration prüfen
if (!defined('APPLICATION_NAME')) exit('The PHP constant APPLICATION_NAME must be defined (see "'.PHPLIB_ROOT.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'project.skel'.DIRECTORY_SEPARATOR.'index.php")');
if (!defined('APPLICATION_ROOT')) exit('The PHP constant APPLICATION_ROOT must be defined (see "'.PHPLIB_ROOT.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'project.skel'.DIRECTORY_SEPARATOR.'index.php")');


// ob wir unter Windows laufen
define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3))==='WIN'));


// phpInfo()-Aufrufe abfangen
// --------------------------
if (subStr($_SERVER['PHP_SELF'], -12) == '/phpinfo.php') {
   require(PHPLIB_ROOT.'/php/phpinfo.php');
   exit(0);
}
if (PHP_VERSION < '5.2.1') echoPre('Warning: You are running a buggy PHP version (a version >= 5.2.1 is recommended).');


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

$__classes['ClickatellSMSMessenger'         ] = PHPLIB_ROOT.'/php/net/messenger/ClickatellSMSMessenger';
$__classes['ICQMessenger'                   ] = PHPLIB_ROOT.'/php/net/messenger/ICQMessenger';
$__classes['IRCMessenger'                   ] = PHPLIB_ROOT.'/php/net/messenger/IRCMessenger';
$__classes['Messenger'                      ] = PHPLIB_ROOT.'/php/net/messenger/Messenger';

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


// Errorlevel
!defined('E_RECOVERABLE_ERROR') && define('E_RECOVERABLE_ERROR',  4096);   // since PHP 5.2.0
!defined('E_DEPRECATED'       ) && define('E_DEPRECATED'       ,  8192);   // since PHP 5.3.0
!defined('E_USER_DEPRECATED'  ) && define('E_USER_DEPRECATED'  , 16384);   // since PHP 5.3.0

// Loglevel
define('L_DEBUG' ,  1);
define('L_INFO'  ,  2);
define('L_NOTICE',  4);
define('L_WARN'  ,  8);
define('L_ERROR' , 16);
define('L_FATAL' , 32);

// Logdestinations für die PHP-Funktion error_log()
define('ERROR_LOG_SYSLOG', 0);                        // message is sent to PHP's system logger
define('ERROR_LOG_MAIL'  , 1);                        // message is sent by email
define('ERROR_LOG_DEBUG' , 2);                        // message is sent through the PHP debugging connection
define('ERROR_LOG_FILE'  , 3);                        // message is appended to a file destination

// Zeitkonstanten
define('SECOND',   1          ); define('SECONDS', SECOND);
define('MINUTE',  60 * SECONDS); define('MINUTES', MINUTE);
define('HOUR'  ,  60 * MINUTES); define('HOURS'  , HOUR  );
define('DAY'   ,  24 * HOURS  ); define('DAYS'   , DAY   );
define('WEEK'  ,   7 * DAYS   ); define('WEEKS'  , WEEK  );
define('YEAR'  , 365 * DAYS   ); define('YEARS'  , YEAR  );

// Wochentage
define('SUNDAY'   , 0);
define('MONDAY'   , 1);
define('TUESDAY'  , 2);
define('WEDNESDAY', 3);
define('THURSDAY' , 4);
define('FRIDAY'   , 5);
define('SATURDAY' , 6);

// Helper
define('NL', PHP_EOL);
if (!defined('PHP_INT_MIN')) define('PHP_INT_MIN', ~PHP_INT_MAX);    // since PHP 7.0.0


// Error-/Exceptionhandler anonym registrieren, damit die Klasse Logger nicht schon hier geladen wird
// --------------------------------------------------------------------------------------------------
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
 * @param  string $className - Klassenname
 * @param  mixed  $throw     - Ob Exceptions geworfen werfen dürfen. Typ und Wert des Parameters sind unwichtig,
 *                             einzig seine Existenz reicht für die Erkennung eines manuellen Aufrufs.
 *
 * NOTE: Prior to 5.3 exceptions thrown in the __autoload() function could not be caught in the catch block and would result in
 *       a fatal error. From 5.3+ exceptions thrown in the __autoload() function can be caught in the catch block with one provision.
 *       If throwing a custom exception then the custom exception class must be available. The __autoload() function may be used
 *       recursively to autoload the custom exception class.
 */
function __autoload($className/*, $throw*/) {
   try {
      if (isSet($GLOBALS['__classes'][$className])) {
         // Fällt der automatische Aufruf der  __autoload()-Funktion aus (z.B. at Compile-Time in PHP 5.3-5.4.20), wird die Funktion
         // u.U. manuell aufgerufen.  Um dabei Mehrfach-Includes derselben Klasse *ohne* Verwendung von include_once() verhindern zu
         // können, setzen wir nach dem ersten Laden ein entsprechendes Flag. Die Verwendung von include_once() würde den Opcode-Cache
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

      Logger ::handleException($ex);   // Aufruf durch den PHP-Kernel: Exception manuell verarbeiten, denn
      exit(1);                         // __autoload() darf in PHP < 5.3 keine Exceptions werfen (siehe NOTE oben)
   }
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
 * @param  string $name - Klassenname
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
 * @param  string $name - Interface-Name
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
 * @param  callable $callback - Funktion oder Methode, die ausgeführt werden soll
 *
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
 * @param  int $length - Länge der ID
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
 * Prüft, ob ein String mit einem Teilstring beginnt.
 *
 * @param  string $string
 * @param  string $prefix
 * @param  bool   $ignoreCase - default: nein
 *
 * @return bool
 */
function strStartsWith($string, $prefix, $ignoreCase=false) {
   if ($string!==null && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if ($prefix!==null && !is_string($prefix)) throw new IllegalTypeException('Illegal type of parameter $prefix: '.getType($prefix));
   if (!is_bool($ignoreCase))                 throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

   $stringLen = strLen($string);
   $prefixLen = strLen($prefix);

   if (!$stringLen || !$prefixLen)
      return false;

   if ($ignoreCase)
      return (striPos($string, $prefix) === 0);
   return (strPos($string, $prefix) === 0);
}


/**
 * Prüft, ob ein String mit einem Teilstring beginnt und ignoriert dabei Groß-/Kleinschreibung.
 *
 * @param  string $string
 * @param  string $prefix
 *
 * @return bool
 */
function strStartsWithI($string, $prefix) {
   return strStartsWith($string, $prefix, true);
}


/**
 * Prüft, ob ein String mit einem Teilstring endet.
 *
 * @param  string $string
 * @param  string $suffix
 * @param  bool   $ignoreCase - default: nein
 *
 * @return bool
 */
function strEndsWith($string, $suffix, $ignoreCase=false) {
   if ($string!==null && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if ($suffix!==null && !is_string($suffix)) throw new IllegalTypeException('Illegal type of parameter $suffix: '.getType($suffix));
   if (!is_bool($ignoreCase))                 throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

   $stringLen = strLen($string);
   $suffixLen = strLen($suffix);

   if (!$stringLen || !$suffixLen)
      return false;
   return (($stringLen-$suffixLen) === strRPos($string, $suffix));
}


/**
 * Prüft, ob ein String mit einem Teilstring endet und ignoriert dabei Groß-/Kleinschreibung.
 *
 * @param  string $string
 * @param  string $prefix
 *
 * @return bool
 */
function strEndsWithI($string, $suffix) {
   return strEndsWith($string, $suffix, true);
}


/**
 * Prüft, ob ein String einen Teilstring enthält.
 *
 * @param  string $haystack
 * @param  string $needle
 * @param  bool   $ignoreCase - default: nein
 *
 * @return bool
 */
function strContains($haystack, $needle, $ignoreCase=false) {
   if ($haystack!==null && !is_string($haystack)) throw new IllegalTypeException('Illegal type of parameter $haystack: '.getType($haystack));
   if ($needle  !==null && !is_string($needle))   throw new IllegalTypeException('Illegal type of parameter $needle: '.getType($needle));
   if (!is_bool($ignoreCase))                     throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

   $haystackLen = strLen($haystack);
   $needleLen   = strLen($needle);

   if (!$haystackLen || !$needleLen)
      return false;

   if ($ignoreCase)
      return (striPos($haystack, $needle) !== false);
   return (strPos($haystack, $needle) !== false);
}


/**
 * Prüft, ob ein String einen Teilstring enthält und ignoriert dabei Groß-/Kleinschreibung.
 *
 * @param  string $haystack
 * @param  string $needle
 *
 * @return bool
 */
function strContainsI($haystack, $needle) {
   return strContains($haystack, $needle, true);
}


/**
 * Dropin-Ersatz für ($stringA === $stringB)
 *
 * @param  string $stringA
 * @param  string $stringB
 * @param  bool   $ignoreCase - default: nein
 *
 * @return bool
 */
function strCompare($stringA, $stringB, $ignoreCase=false) {
   if ($stringA!==null && !is_string($stringA)) throw new IllegalTypeException('Illegal type of parameter $stringA: '.getType($stringA));
   if ($stringB!==null && !is_string($stringB)) throw new IllegalTypeException('Illegal type of parameter $stringB: '.getType($stringB));
   if (!is_bool($ignoreCase))                   throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

   if ($ignoreCase)
      return strCompareI($stringA, $stringB);
   return ($stringA === $stringB);
}


/**
 * Dropin-Ersatz für ($stringA === $stringB) ohne Beachtung von Groß-/Kleinschreibung
 *
 * @param  string $stringA
 * @param  string $stringB
 *
 * @return bool
 */
function strCompareI($stringA, $stringB) {
   if ($stringA!==null && !is_string($stringA)) throw new IllegalTypeException('Illegal type of parameter $stringA: '.getType($stringA));
   if ($stringB!==null && !is_string($stringB)) throw new IllegalTypeException('Illegal type of parameter $stringB: '.getType($stringB));

   if ($stringA===null || $stringB===null)
      return ($stringA === $stringB);
   return (strToLower($stringA) === strToLower($stringB));
}


/**
 * Gibt einen linken Teilstring eines Strings zurück.
 *
 * @param  string $string
 * @param  int    $length - Länge des Teilstrings. Ist der Wert negativ, werden alle außer der angegebenen Anzahl
 *                          rechts stehender Zeichen zurückgegeben.
 *
 * @return string - String
 */
function strLeft($string, $length) {
   if (!is_int($length))    throw new IllegalTypeException('Illegal type of parameter $length: '.getType($length));
   if ($string === null)
      return '';
   if (is_int($string))
      $string = (string)$string;
   if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   return subStr($string, 0, $length);
}


/**
 * Gibt einen rechten Teilstring eines Strings zurück.
 *
 * @param  string $string
 * @param  int    $length - Länge des Teilstrings. Ist der Wert negativ, werden alle außer der angegebenen Anzahl
 *                          links stehender Zeichen zurückgegeben.
 *
 * @return string - String
 */
function strRight($string, $length) {
   if (!is_int($length))    throw new IllegalTypeException('Illegal type of parameter $length: '.getType($length));
   if ($string === null)
      return '';
   if (is_int($string))
      $string = (string)$string;
   if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   if ($length == 0)
      return '';

   $result = subStr($string, -$length);
   return $result===false ? '' : $result;
}


/**
 * Alias für "getType()" zur sprachenübergreifenden Vereinfachung.
 *
 * @param  mixed $var - Variable
 *
 * @return string
 */
function typeOf($var) {
   return getType($var);
}


/**
 * Alias für "echo $var", das ein Language-Construct ist und nicht immer als Funktion verwendet werden kann.
 *
 * @param  mixed $var - die auszugebende Variable
 */
function echos($var) {
   echo $var;
}


/**
 * Alias für printFormatted($var, false).
 *
 * Hilfsfunktion zur formatierten Ausgabe einer Variable. Die Ausgabe wird immer mit einem Zeilenende-Zeichen
 * abgeschlossen.
 *
 * @param  mixed $var - die auszugebende Variable
 */
function echoPre($var) {
   printFormatted($var);
}


/**
 * Hilfsfunktion zur formatierten Ausgabe einer Variable. Die Ausgabe wird immer mit einem Zeilenende-Zeichen
 * abgeschlossen.
 *
 * @param  mixed $var    - die auszugebende Variable
 * @param  bool  $return - ob das Ergebnis zurückgegeben (TRUE) oder auf STDOUT ausgegeben (FALSE) werden soll
 *                         (default: Ausgabe auf STDOUT)
 *
 * @return string - Rückgabewert, wenn der Parameter $return TRUE ist; NULL andererseits
 */
function printFormatted($var, $return=false) {
   if (is_object($var) && method_exists($var, '__toString')) {
      $str = $var->__toString();
   }
   elseif (is_object($var) || is_array($var)) {
      $str = print_r($var, true);
   }
   else if ($var === null) {
      $str = '(NULL)';           // entsprechend typeof(null) = 'NULL';
   }
   else if (is_bool($var)) {
      $str = $var ? 'true':'false';
   }
   else {
      $str = (string) $var;
   }

   if (isSet($_SERVER['REQUEST_METHOD']))
      $str = '<div align="left"><pre style="margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.htmlSpecialChars($str, ENT_QUOTES).'</pre></div>';

   if (!strEndsWith($str, "\n"))
      $str .= "\n";

   if ($return)
      return $str;

   echo $str;
   return null;
}


/**
 * Gibt den Inhalt einer Variable aus.
 *
 * @param  mixed $var   - Variable
 * @param  bool $return - TRUE, wenn das Ergebnis zurückgegeben werden soll;
 *                        FALSE, wenn das Ergebnis auf STDOUT ausgegeben werden soll (default)
 *
 * @return string       - Variableninhalt oder NULL, wenn der Parameter $return FALSE ist
 */
function dump($var, $return=false) {
   if ($return) ob_start();

   var_dump($var);

   if ($return) return ob_get_clean();
   return;
}


/**
 * Dropin-Replacement für TRUE zum schnellen Debugging.
 *
 * @param  string $function - Name der aktuellen Funktion
 * @param  int    $line     - aktuelle Codezeile
 * @param  string $id       - optionaler zusätzlicher Identifier
 *
 * @return TRUE
 */
function _true($function, $line, $id=null) {
   echoPre($function.'('.$id.'), line '.$line);
   return true;
}


/**
 * Dropin-Replacement für FALSE zum schnellen Debugging.
 *
 * @param  string $function - Name der aktuellen Funktion
 * @param  int    $line     - aktuelle Codezeile
 * @param  string $id       - optionaler zusätzlicher Identifier
 *
 * @return FALSE
 */
function _false($function, $line, $id=null) {
   echoPre($function.'('.$id.'), line '.$line);
   return false;
}


/**
 * Gibt einen String als JavaScript aus.
 *
 * @param  string $snippet - Code
 */
function javaScript($snippet) {
   echo '<script language="JavaScript">'.$snippet.'</script>';
}


/**
 * Shortcut-Ersatz für String::htmlSpecialChars()
 *
 * @param  string $string - zu kodierender String
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
 * @param  string $html - der zu dekodierende String
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
 * @param  string $date - Ausgangsdatum (Format: yyyy-mm-dd)
 * @param  int    $days - Tagesanzahl
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
 * @param  string $date - Ausgangsdatum (Format: yyyy-mm-dd)
 * @param  int    $days - Tagesanzahl
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
 * @param  string $format   - Formatstring (siehe PHP Manual zu date())
 * @param  string $datetime - Datum oder Zeit
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
 * @param  mixed  $value            - Zahlenwert (integer oder float)
 * @param  int    $decimals         - Anzahl der Nachkommastellen (default: 2)
 * @param  string $decimalSeparator - Dezimaltrennzeichen: '.' oder ',' (default: '.')
 *
 * @return string
 */
function formatMoney($value, $decimals=2, $decimalSeparator='.') {
   if (!is_int($value) && !is_float($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
   if (!is_int($decimals))                   throw new IllegalTypeException('Illegal type of parameter $decimals: '.getType($decimals));

   if ($decimalSeparator === '.')
      return number_format($value, $decimals, '.', ',');

   if ($decimalSeparator === ',')
      return number_format($value, $decimals, ',', '.');

   throw new plInvalidArgumentException('Invalid argument $decimalSeparator: "'.$decimalSeparator.'"');
}


/**
 * Pretty printer for byte values.
 *
 * @param  int $value - byte value
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
 * Führt ein Shell-Kommando aus und gibt dessen Standardausgabe als String zurück.
 *
 * @param  string $cmd - Shell-Kommando
 *
 * @return string - Standardausgabe
 *
 *
 * NOTE: Workaround für shell_exec()-Bug unter Windows, wo das Lesen der Standardausgabe bei Auftreten
 *       eines DOS-EOF-Characters (0x1A = ASCII 26) abgebrochen wird.
 */
function shell_exec_fix($cmd) {
   if (!WINDOWS)
      return shell_exec($cmd);

   // pOpen() kann ebenfalls nicht verwendet werden, da dort derselbe Bug auftritt.
   // Ursache ist vermutlich die gemeinsame Verwendung von feof().

   $descriptors = array(0 => array('pipe', 'rb'),        // stdin
                        1 => array('pipe', 'wb'),        // stdout
                        2 => array('pipe', 'wb'));       // stderr
   $pipes = array();
   $hProc = proc_open($cmd, $descriptors, $pipes, null, null, array('bypass_shell'=>true));
                                                         // $pipes now looks like this:
   if (is_resource($hProc)) {                            // 0 => writeable handle connected to child stdin
      $stdout = stream_get_contents($pipes[1]);          // 1 => readable handle connected to child stdout
      fClose($pipes[0]);                                 // 2 => readable handle connected to child stderr
      fClose($pipes[1]);
      fClose($pipes[2]);

      $exit_code = proc_close($hProc);                   // close pipes before proc_close() to avoid deadlock
      return $stdout;
   }
   return null;
}


/**
 * Prüft, ob ein Verzeichnis existiert und legt es ggf. an. Prüft dann, ob darin Schreibzugriff möglich ist.
 *
 * @param  string $path - wie bei mkDir(): Name des Verzeichnisses
 * @param  int    $mode - wie bei mkDir(): zu setzende Zugriffsberechtigung, falls das Verzeichnis nicht existiert (default: 0777)
 *
 * @return  bool - Immer TRUE (nach Verlassen der Funktion existiert das Verzeichnis und Schreibzugriff ist möglich),
 *                 anderenfalls wird eine Exception ausgelöst.
 */
function mkDirWritable($path, $mode=0777) {
   if (!is_string($path))                            throw new IllegalTypeException('Illegal type of parameter $path: '.getType($path));
   if (!is_null($mode) && !is_int($mode))            throw new IllegalTypeException('Illegal type of parameter $mode: '.getType($mode));

   if (is_file($path))                               throw new plRuntimeException('Cannot write to directory "'.$path.'" (is file)');
   if (!is_dir($path) && !mkDir($path, $mode, true)) throw new plRuntimeException('Cannot create directory "'.$path.'"');
   if (!is_writable($path))                          throw new plRuntimeException('Cannot write to directory "'.$path.'"');

   return true;
}


/**
 * Ist $value nicht NULL, gibt die Funktion $value zurück, ansonsten die Alternative $altValue.
 *
 * @return  mixed
 */
function ifNull($value, $altValue) {
   return ($value===null) ? $altValue : $value;
}


/**
 * Gibt den angegebenen Errorlevel in lesbarer Form zurück.
 *
 * @param  int $level - Errorlevel, ohne Angabe wird der aktuelle Errorlevel des laufenden Scriptes ausgewertet.
 *
 * @return string
 */
function errorLevelToStr($level=null) {
   if (func_num_args() && !is_int($level)) throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));

   $levels = array();
   if (!$level)
      $level = error_reporting();

   if ($level & E_ERROR            ) $levels[] = 'E_ERROR';                //     1
   if ($level & E_WARNING          ) $levels[] = 'E_WARNING';              //     2
   if ($level & E_PARSE            ) $levels[] = 'E_PARSE';                //     4
   if ($level & E_NOTICE           ) $levels[] = 'E_NOTICE';               //     8
   if ($level & E_CORE_ERROR       ) $levels[] = 'E_CORE_ERROR';           //    16
   if ($level & E_CORE_WARNING     ) $levels[] = 'E_CORE_WARNING';         //    32
   if ($level & E_COMPILE_ERROR    ) $levels[] = 'E_COMPILE_ERROR';        //    64
   if ($level & E_COMPILE_WARNING  ) $levels[] = 'E_COMPILE_WARNING';      //   128
   if ($level & E_USER_ERROR       ) $levels[] = 'E_USER_ERROR';           //   256
   if ($level & E_USER_WARNING     ) $levels[] = 'E_USER_WARNING';         //   512
   if ($level & E_USER_NOTICE      ) $levels[] = 'E_USER_NOTICE';          //  1024
   if ($level & E_STRICT           ) $levels[] = 'E_STRICT';               //  2048: since PHP 5 but not included in E_ALL until PHP 5.4.0
   if ($level & E_RECOVERABLE_ERROR) $levels[] = 'E_RECOVERABLE_ERROR';    //  4096: since PHP 5.2.0
   if ($level & E_DEPRECATED       ) $levels[] = 'E_DEPRECATED';           //  8192: since PHP 5.3.0
   if ($level & E_USER_DEPRECATED  ) $levels[] = 'E_USER_DEPRECATED';      // 16384: since PHP 5.3.0
   if ($level & E_ALL              ) $levels[] = 'E_ALL';                  //      : 32767 in PHP 5.4.x, 30719 in PHP 5.3.x, 6143 in PHP 5.2.x, 2047 previously

   return join('|', $levels).' ('.$level.')';
}
