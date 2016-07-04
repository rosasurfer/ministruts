<?php
use rosasurfer\ministruts\exceptions\ClassNotFoundException;
use rosasurfer\ministruts\exceptions\IllegalTypeException;
use rosasurfer\ministruts\exceptions\InvalidArgumentException;
use rosasurfer\ministruts\exceptions\RuntimeException;

use const rosasurfer\ministruts\CLI       as CLI;
use const rosasurfer\ministruts\LOCALHOST as LOCALHOST;
use const rosasurfer\ministruts\ROOT      as MINISTRUTS_ROOT;
use const rosasurfer\ministruts\WINDOWS   as WINDOWS;


/**
 * Program flow:
 *
 * (1) block framework re-includes
 * (2) check/adjust PHP environment
 * (3) execute phpInfo() if applicable
 * (4) check/adjust application requirements
 * (5) register class loader
 * (6) setup error and exception handling
 * (7) define helper constants
 * (8) define helper functions
 * (9) define all helpers globally if applicable
 */


// (1) block framework re-includes
if (defined('rosasurfer\ministruts\ROOT'))
   return;
define('rosasurfer\ministruts\ROOT'     ,  dirName(__DIR__));
define('rosasurfer\ministruts\CLI'      , !isSet($_SERVER['REQUEST_METHOD']));               // whether or not we run on command line interface
define('rosasurfer\ministruts\LOCALHOST', !CLI && @$_SERVER['REMOTE_ADDR']=='127.0.0.1');    // whether or not we run on localhost
define('rosasurfer\ministruts\WINDOWS'  , (strToUpper(subStr(PHP_OS, 0, 3))=='WIN'));        // whether or not we run on Windows


// (2) check/adjust PHP environment
(PHP_VERSION < '5.6')      && exit(1|echoPre('Error: A PHP version >= 5.6 is required (found version '.PHP_VERSION.').'));
!ini_get('short_open_tag') && exit(1|echoPre('Error: The PHP configuration value "short_open_tag" must be enabled.'    ));

ini_set('arg_separator.output'    , '&amp;'                );
ini_set('auto_detect_line_endings',  1                     );
ini_set('default_mimetype'        , 'text/html'            );
ini_set('default_charset'         , 'UTF-8'                );
ini_set('ignore_repeated_errors'  ,  0                     );
ini_set('ignore_repeated_source'  ,  0                     );
ini_set('ignore_user_abort'       ,  1                     );
ini_set('display_errors'          , (int)(CLI || LOCALHOST));
ini_set('display_startup_errors'  , (int)(CLI || LOCALHOST));
ini_set('log_errors'              ,  1                     );
ini_set('log_errors_max_len'      ,  0                     );
ini_set('track_errors'            ,  1                     );
ini_set('html_errors'             ,  0                     );
ini_set('session.use_cookies'     ,  1                     );
ini_set('session.use_trans_sid'   ,  0                     );
ini_set('session.cookie_httponly' ,  1                     );
ini_set('session.referer_check'   , ''                     );
ini_set('zend.detect_unicode'     ,  1                     );     // automatic BOM header recognition


// (3) execute phpInfo() if applicable (authorization must be handled by the server)
if (false) {
   if (!CLI && strEndsWith(strLeftTo($_SERVER['REQUEST_URI'], '?'), '/phpinfo.php'))
      include(MINISTRUTS_ROOT.'/src/phpinfo.php') | exit(0);
}


// (4) check/adjust application requirements
!defined('\APPLICATION_ROOT') && exit(1|echoPre('Error: The global constant APPLICATION_ROOT must be defined.'));
!defined('\APPLICATION_ID'  ) && define('APPLICATION_ID', md5(\APPLICATION_ROOT));


/**
 * (5) register case-insensitive class loader
 *
 * @param  string $class - name of class to load
 */
spl_autoload_register(function($class) {
   // block re-declarations
   if (class_exists($class, false) || interface_exists($class, false))
      return;

   // initialize class map
   static $classMap = null;
   if (!$classMap) {
      $classMap = include(__DIR__.'/classmap.php');
      $classMap = array_change_key_case($classMap, CASE_LOWER);
   }

   $classToLower = strtolower($class);
   try {
      if (isSet($classMap[$classToLower])) {
         $fileName = $classMap[$classToLower];

         // warn if relative path found: decreases performance, especially with APC setting 'apc.stat=0'
         $relative = WINDOWS ? !preg_match('/^[a-z]:/i', $fileName) : ($fileName{0} != '/');
         $relative && Logger::log('Relative file name for class '.$class.': "'.$fileName.'"', L_WARN, __CLASS__);

         // load class file
         include($fileName.'.php');
      }
   }
   catch (\Exception $ex) {
      if (!$ex instanceof ClassNotFoundException)
         $ex = new ClassNotFoundException('Cannot load class '.$class, null, $ex);
      throw $ex;
   }
});


// (6) setup error and exception handling
System::setupErrorHandling();




// (7) define local helper constants
// (8) define local helper functions
// (9) define helpers globally if applicable




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
define('ERROR_LOG_SAPI'  , 4);                        // message is sent directly to the SAPI logging handler

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




// Beginn des Shutdowns markieren, um fatale Fehler beim Shutdown zu verhindern (siehe Logger)
// -------------------------------------------------------------------------------------------
register_shutdown_function(create_function(null, '$GLOBALS[\'$__shutting_down\'] = true;'));    // allererste Funktion auf dem Shutdown-Funktion-Stack




/**
 * Ob die angegebene Klasse definiert (und kein Interface) ist. Diese Funktion bricht im Gegensatz zum Aufruf von
 *
 *    "class_exist($className, true)"
 *
 * bei Nichtexistenz der Klasse das Script nicht mit einem fatalen Fehler ab. Die Klasse wird ggf. geladen.
 *
 * @param  string $name - Klassenname
 *
 * @return bool
 */
function is_class($name) {
   if (class_exists    ($name, false)) return true;
   if (interface_exists($name, false)) return false;

   try {
      $functions = spl_autoload_functions();
      if (!$functions) {               // no loader nor __autoload() exist: spl_autoload_call() will call spl_autoload()
         spl_autoload_call($name);     // onError: Uncaught LogicException: Class $name could not be loaded
      }
      else if (sizeOf($functions)==1 && is_string($functions[0]) && $functions[0]=='__autoload') {
         __autoload($name);            // __autoload() exists and is explicit or implicit defined
      }
      else {
         spl_autoload_call($name);     // a regular loader queue is defined
      }
   }
   catch (ClassNotFoundException $ex) {}

   return class_exists($name, false);
}


/**
 * Ob das angegebene Interface definiert (und keine Klasse) ist. Diese Funktion bricht im Gegensatz zum Aufruf von
 *
 *    "interface_exist($interfaceName, true)"
 *
 * bei Nichtexistenz des Interface das Script nicht mit einem fatalen Fehler ab. Das Interface wird ggf. geladen.
 *
 * @param  string $name - Interface-Name
 *
 * @return bool
 */
function is_interface($name) {
   if (interface_exists($name, false)) return true;
   if (class_exists    ($name, false)) return false;

   try {
      $functions = spl_autoload_functions();
      if (!$functions) {               // no loader nor __autoload() exist: spl_autoload_call() will call spl_autoload()
         spl_autoload_call($name);     // onError: Uncaught LogicException: Class $name could not be loaded
      }
      else if (sizeOf($functions)==1 && is_string($functions[0]) && $functions[0]=='__autoload') {
         __autoload($name);            // __autoload() exists and is explicit or implicit defined
      }
      else {
         spl_autoload_call($name);     // a regular loader queue is defined
      }
   }
   catch (ClassNotFoundException $ex) {}

   return interface_exists($name, false);
}


/**
 * Prozedural-Ersatz für CommonValidator::isDateTime()
 *
 * Ob der übergebene String einen gültigen Date/DateTime-Wert darstellt.
 *
 * @param  string    $string - der zu überprüfende String
 * @param  string|[] $format - Format, dem der String entsprechen soll. Sind mehrere angegeben, muß der String
 *                             mindestens einem davon entsprechen.
 *
 * @return int|bool - Timestamp oder FALSE, wenn der übergebene Wert nicht gültig ist
 *
 * Unterstützte Formate: 'Y-m-d [H:i[:s]]'
 *                       'Y.m.d [H:i[:s]]'
 *                       'd.m.Y [H:i[:s]]'
 *                       'd/m/Y [H:i[:s]]'
 */
function is_datetime($string, $format) {
   return CommonValidator::isDateTime($string, $format);
}


/**
 * Ob die Byte-Order der Maschine Little-Endian ist oder nicht (dann Big-Endian).
 *
 * @return bool
 */
function isLittleEndian() {
   return (pack('S', 1) == "\x01\x00");
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
         catch (\Exception $ex) {
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
 * @param  int $length - Länge der ID
 *
 * @return string - ID
 */
function getRandomID($length) {
   if (!isSet($length) || !is_int($length) || $length < 1) throw new RuntimeException('Invalid argument length: '.$length);

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
 * @param  string    $string
 * @param  string|[] $prefix     - Teilstring. Sind mehrere angegeben, prüft die Funktion, ob der String mit einem von ihnen beginnt.
 * @param  bool      $ignoreCase - default: nein
 *
 * @return bool
 */
function strStartsWith($string, $prefix, $ignoreCase=false) {
   if (is_array($prefix)) {
      $self = __FUNCTION__;
      foreach ($prefix as $p) if ($self($string, $p, $ignoreCase))
         return true;
      return false;
   }
   if (!is_null($string) && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if (!is_string($prefix))                      throw new IllegalTypeException('Illegal type of parameter $prefix: '.$prefix.' ('.getType($prefix).')');
   if (!is_bool($ignoreCase))                    throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

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
 * @param  string    $string
 * @param  string|[] $suffix     - Teilstring. Sind mehrere angegeben, prüft die Funktion, ob der String mit einem von ihnen endet.
 * @param  bool      $ignoreCase - default: nein
 *
 * @return bool
 */
function strEndsWith($string, $suffix, $ignoreCase=false) {
   if (is_array($suffix)) {
      $self = __FUNCTION__;
      foreach ($suffix as $s) if ($self($string, $s, $ignoreCase))
         return true;
      return false;
   }
   if (!is_null($string) && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if (!is_string($suffix))                      throw new IllegalTypeException('Illegal type of parameter $suffix: '.$suffix.' ('.getType($suffix).')');
   if (!is_bool($ignoreCase))                    throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

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
   if (!is_null($haystack) && !is_string($haystack)) throw new IllegalTypeException('Illegal type of parameter $haystack: '.getType($haystack));
   if (!is_string($needle))                          throw new IllegalTypeException('Illegal type of parameter $needle: '.getType($needle));
   if (!is_bool($ignoreCase))                        throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

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
   if (!is_null($stringA) && !is_string($stringA)) throw new IllegalTypeException('Illegal type of parameter $stringA: '.getType($stringA));
   if (!is_null($stringB) && !is_string($stringB)) throw new IllegalTypeException('Illegal type of parameter $stringB: '.getType($stringB));
   if (!is_bool($ignoreCase))          throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

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
   if (!is_null($stringA) && !is_string($stringA)) throw new IllegalTypeException('Illegal type of parameter $stringA: '.getType($stringA));
   if (!is_null($stringB) && !is_string($stringB)) throw new IllegalTypeException('Illegal type of parameter $stringB: '.getType($stringB));

   if (is_null($stringA) || is_null($stringB))
      return ($stringA === $stringB);
   return (strToLower($stringA) === strToLower($stringB));
}


/**
 * Gibt einen linken Teilstring eines Strings zurück.
 *
 * @param  string $string - Ausgangsstring
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
   else if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   return subStr($string, 0, $length);
}


/**
 * Gibt den linken Teil eines Strings bis zum Auftreten eines Teilstrings zurück. Das Ergebnis schließt den
 * zu suchenden Teilstring nicht mit ein.
 *
 * @param  string $string    - Ausgangsstring
 * @param  string $substring - der das Ergebnis begrenzende Teilstring
 *
 * @return string - Teilstring oder der Ausgangsstring, wenn der zu begrenzende Teilstring nicht gefunden wurde
 */
function strLeftTo($string, $substring) {
   if (!is_string($string))    throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if (!is_string($substring)) throw new IllegalTypeException('Illegal type of parameter $substring: '.getType($substring));

   $pos = strPos($string, $substring);
   if ($pos === false)
      return $string;
   return subStr($string, 0, $pos);
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
   else if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   if ($length == 0)
      return '';

   $result = subStr($string, -$length);
   return $result===false ? '' : $result;
}


/**
 * Returns the right part of a string from the specified occurrence of a limiting substring. The result doesn't
 * include the limiting substring.
 *
 * @param  string $string     - initial string
 * @param  string $substring  - limiting substring (can be multiple characters)
 * @param  int    $count      - if positive, the specified occurrence of the limiting substring counted from the
 *                              start of the string (default: the first occurrence)
 *                              if negative, the specified occurrence of the limiting substring counted from the
 *                              end of the string;
 *                              if zero, an empty string is returned
 * @param  mixed  $onNotFound - value of any type to return if the limiting substring is not found;
 *                              default: the full string
 *
 * @return string - right part or the specified $onNotFound value
 *
 * @example
 * <pre>
 * strRightFrom('abc_abc', 'c')     => '_abc'
 * strRightFrom('abcabc',  'x')     => 'abcabc'       // limiter not found
 * strRightFrom('abc_abc', 'a',  2) => 'bc'
 * strRightFrom('abc_abc', 'b', -2) => 'c_abc'
 * </pre>
 */
function strRightFrom($string, $substring, $count=1, $onNotFound=null) {
   if (!is_string($string))    throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if (!is_string($substring)) throw new IllegalTypeException('Illegal type of parameter $substring: '.getType($substring));
   if (!is_int($count))        throw new IllegalTypeException('Illegal type of parameter $count: '.getType($count));

   if ($count > 0) {
      $pos = -1;
      while ($count) {
         $offset = $pos + 1;
         $pos = strPos($string, $substring, $offset);
         if ($pos === false)                                      // not found
            return func_num_args() > 3 ? $onNotFound : $string;
         $count--;
      }
      return subStr($string, $pos + strLen($substring));
   }

   if ($count < 0) {
      $len = strLen($string);
      $pos = $len;
      while ($count) {
         $offset = $pos - $len - 1;
         if ($offset < -$len)                                     // not found
            return func_num_args() > 3 ? $onNotFound : $string;
         $pos = strRPos($string, $substring, $offset);
         if ($pos === false)                                      // not found
            return func_num_args() > 3 ? $onNotFound : $string;
         $count++;
      }
      return subStr($string, $pos + strLen($substring));
   }

   // $count == 0
   return '';
}


/**
 * Prüft, ob ein String in einfache Anführungszeichen eingefaßt ist.
 *
 * @param  string $string
 *
 * @return bool
 */
function strIsSingleQuoted($string) {
   if (is_null($string)) return false;
   if (is_int($string))
      $string = (string)$string;
   else if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   return (strLen($string)>1 && $string{0}=="'" && strRight($string, 1)=="'");
}


/**
 * Prüft, ob ein String in doppelte Anführungszeichen eingefaßt ist.
 *
 * @param  string $string
 *
 * @return bool
 */
function strIsDoubleQuoted($string) {
   if (is_null($string)) return false;
   if (is_int($string))
      $string = (string)$string;
   else if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   return (strLen($string)>1 && $string{0}=='"' && strRight($string, 1)=='"');
}


/**
 * Prüft, ob ein String in einfache oder doppelte Anführungszeichen eingefaßt ist.
 *
 * @param  string $string
 *
 * @return bool
 */
function strIsQuoted($string) {
   if (is_null($string)) return false;
   if (is_int($string))
      $string = (string)$string;
   else if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   return (strLen($string)>1 && (strIsSingleQuoted($string) || strIsDoubleQuoted($string)));
}


/**
 * Prüft, ob ein String vollständig aus numerischen Zeichen besteht.
 *
 * @param  string $string
 *
 * @return bool
 */
function strIsDigit($string) {
   if (is_null($string)) return false;
   if (is_int($string))
      $string = (string)$string;
   else if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   return ctype_digit($string);
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
   ob_get_level() && ob_flush();       // flush output buffer if active
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
      $str = (string) $var;
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

   if (!CLI)
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

   ob_get_level() && ob_flush();       // flush output buffer if active
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
   if (CommonValidator ::isDateTime($date) === false) throw new InvalidArgumentException('Invalid argument $date: '.$date);
   if (!is_int($days))                                throw new InvalidArgumentException('Invalid argument $days: '.$days);

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
   if (!is_int($days)) throw new InvalidArgumentException('Invalid argument $days: '.$days);
   return addDate($date, -$days);
}


/**
 * Formatiert die String-Repräsentation eines lokalen Zeitpunktes mit dem angegebenen Format. Haupteinsatzgebiet
 * dieser Funktion ist das schnelle Reformatieren von Zeitangaben, die aus Datenbankabfragen stammen.
 *
 * @param  string $datetime - String-Repräsentation eines Datums oder Zeitpunkts
 * @param  string $format   - String mit Format-Codes entsprechend der PHP-Funktion date()
 *
 * @return string - formatierter String unter Berücksichtigung der lokalen Zeitzone
 */
function formatDateStr($datetime, $format) {
   if (!is_string($datetime)) throw new IllegalTypeException('Illegal type of parameter $datetime: '.getType($datetime));
   if (!is_string($format))   throw new IllegalTypeException('Illegal type of parameter $format: '.getType($format));

   if ($datetime < '1970-01-01 00:00:00') {
      if ($format != 'd.m.Y') {
         Logger ::log('Cannot format early datetime "'.$datetime.'" with format "'.$format.'"', L_INFO, __CLASS__);
         return preg_replace('/[1-9]/', '0', date($format, time()));
      }

      $parts = explode('-', substr($datetime, 0, 10));
      return $parts[2].'.'.$parts[1].'.'.$parts[0];
   }

   $timestamp = strToTime($datetime);
   if (!is_int($timestamp)) throw new InvalidArgumentException('Invalid argument $datetime: '.$datetime);

   return date($format, $timestamp);
}


/**
 * Formatiert einen Zahlenwert im Währungsformat.
 *
 * @param  mixed  $value            - Zahlenwert (int oder double)
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

   throw new InvalidArgumentException('Invalid argument $decimalSeparator: "'.$decimalSeparator.'"');
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

   if (is_file($path))                               throw new RuntimeException('Cannot write to directory "'.$path.'" (is file)');
   if (!is_dir($path) && !mkDir($path, $mode, true)) throw new RuntimeException('Cannot create directory "'.$path.'"');
   if (!is_writable($path))                          throw new RuntimeException('Cannot write to directory "'.$path.'"');

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
 * Returns a human-readable form of the specified error reporting level.
 *
 * @param  int $level - reporting level (default: the currently active reporting level)
 *
 * @return string
 */
function errorLevelToStr($level=null) {
   if (func_num_args() && !is_int($level)) throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));

   $levels = array();

   if (!$level                     ) $levels[] = '0';                      //  zero
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
   if ($level & E_STRICT           ) $levels[] = 'E_STRICT';               //  2048: since PHP 5, not included in E_ALL until PHP 5.4.0
   if ($level & E_RECOVERABLE_ERROR) $levels[] = 'E_RECOVERABLE_ERROR';    //  4096: since PHP 5.2.0
   if ($level & E_DEPRECATED       ) $levels[] = 'E_DEPRECATED';           //  8192: since PHP 5.3.0
   if ($level & E_USER_DEPRECATED  ) $levels[] = 'E_USER_DEPRECATED';      // 16384: since PHP 5.3.0
 //if ($level & E_ALL == E_ALL     ) $levels[] = 'E_ALL';                  // 32767 since PHP 5.4.x, 30719 in PHP 5.3.x, 6143 in PHP 5.2.x, 2047 previously

   return join('|', $levels);
}
