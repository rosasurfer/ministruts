<?php
/**
 * Load the Ministruts framework.
 */
namespace rosasurfer;

use rosasurfer\ministruts\exceptions\ClassNotFoundException;
use rosasurfer\ministruts\exceptions\IllegalTypeException;


/**
 * Program flow
 * ------------
 * (1) block framework re-includes
 * (2) define helper constants
 * (3) check/adjust PHP environment
 * (4) execute phpinfo() if applicable
 * (5) check/adjust application requirements
 * (6) register class loader
 * (7) setup error and exception handling
 * (8) define helper functions
 */


/**
 * (1) block framework re-includes
 */
if (defined('rosasurfer\MINISTRUTS_ROOT')) return;
define('rosasurfer\MINISTRUTS_ROOT', dirName(__DIR__));


/**
 * (2) define namespaced helper constants
 */
define('rosasurfer\CLI'      , !isSet($_SERVER['REQUEST_METHOD']));                 // whether or not we run on command line interface
define('rosasurfer\LOCALHOST', !CLI && @$_SERVER['REMOTE_ADDR']=='127.0.0.1');      // whether or not we run on localhost
define('rosasurfer\WINDOWS'  , (strToUpper(subStr(PHP_OS, 0, 3))=='WIN'));          // whether or not we run on Windows

// custom log level
define('rosasurfer\L_DEBUG' ,  1);
define('rosasurfer\L_INFO'  ,  2);
define('rosasurfer\L_NOTICE',  4);
define('rosasurfer\L_WARN'  ,  8);
define('rosasurfer\L_ERROR' , 16);
define('rosasurfer\L_FATAL' , 32);

// log destinations for the built-in function error_log()
define('rosasurfer\ERROR_LOG_SYSLOG', 0);                                           // message is sent to PHP's system logger
define('rosasurfer\ERROR_LOG_MAIL'  , 1);                                           // message is sent by email
define('rosasurfer\ERROR_LOG_DEBUG' , 2);                                           // message is sent through the PHP debugging connection
define('rosasurfer\ERROR_LOG_FILE'  , 3);                                           // message is appended to a file destination
define('rosasurfer\ERROR_LOG_SAPI'  , 4);                                           // message is sent directly to the SAPI logging handler

// time periods
define('rosasurfer\SECOND',   1          ); define('rosasurfer\SECONDS', SECOND);
define('rosasurfer\MINUTE',  60 * SECONDS); define('rosasurfer\MINUTES', MINUTE);
define('rosasurfer\HOUR'  ,  60 * MINUTES); define('rosasurfer\HOURS'  , HOUR  );
define('rosasurfer\DAY'   ,  24 * HOURS  ); define('rosasurfer\DAYS'   , DAY   );
define('rosasurfer\WEEK'  ,   7 * DAYS   ); define('rosasurfer\WEEKS'  , WEEK  );
define('rosasurfer\MONTH' ,  31 * DAYS   ); define('rosasurfer\MONTHS' , MONTH );   // fuzzy but garantied to cover any month
define('rosasurfer\YEAR'  , 366 * DAYS   ); define('rosasurfer\YEARS'  , YEAR  );   // fuzzy but garantied to cover any year

// weekdays
define('rosasurfer\SUNDAY'   , 0);
define('rosasurfer\MONDAY'   , 1);
define('rosasurfer\TUESDAY'  , 2);
define('rosasurfer\WEDNESDAY', 3);
define('rosasurfer\THURSDAY' , 4);
define('rosasurfer\FRIDAY'   , 5);
define('rosasurfer\SATURDAY' , 6);

// miscellaneous
define('rosasurfer\EOL', PHP_EOL);
define('rosasurfer\NL' , "\n"   );
!defined('PHP_INT_MIN') && define('PHP_INT_MIN', ~PHP_INT_MAX);                     // global definition (built-in since PHP 7.0)


/**
 * (3) check/adjust PHP environment
 */
(PHP_VERSION < '5.6')      && echoPre('application error') && exit(255|error_log('Error: A PHP version >= 5.6 is required (found version '.PHP_VERSION.').'));
!ini_get('short_open_tag') && echoPre('application error') && exit(255|error_log('Error: The PHP configuration value "short_open_tag" must be enabled.'));

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
ini_set('zend.detect_unicode'     ,  1                     );     // BOM header recognition


/**
 * (4) execute phpInfo() if applicable (if enabled authorization must be handled by the server)
 */
if (false && !CLI && strEndsWith(strLeftTo($_SERVER['REQUEST_URI'], '?'), '/phpinfo.php')) {
   include(MINISTRUTS_ROOT.'/src/phpinfo.php');
   exit(0);
}


/**
 * (5) check/adjust application requirements
 */
!defined('\APPLICATION_ROOT') && echoPre('application error') && exit(255|error_log('Error: The global constant APPLICATION_ROOT must be defined.'));
!defined('\APPLICATION_ID'  ) && define('APPLICATION_ID', md5(\APPLICATION_ROOT));


/**
 * (6) register class loader
 *
 * @param  string $class
 */
spl_autoload_register(function($class) {
   // block re-declarations
   if (class_exists($class, false) || interface_exists($class, false))
      return;

   // initialize class map: the loader is case-insensitive
   static $classMap = null;
   !$classMap && $classMap=array_change_key_case(include(__DIR__.'/classmap.php'), CASE_LOWER);

   $classToLower = strToLower($class);
   try {
      if (isSet($classMap[$classToLower])) {
         $fileName = $classMap[$classToLower];

         // warn if relative path found: decreases performance especially with APC setting 'apc.stat=0'
         $relative = WINDOWS ? !preg_match('/^[a-z]:/i', $fileName) : ($fileName{0} != '/');
         $relative && trigger_error('Found relative file name for class '.$class.': "'.$fileName.'"', E_USER_WARNING);

         // load file
         include($fileName.'.php');
      }
   }
   catch (\Exception $ex) {
      throw ($ex instanceof ClassNotFoundException) ? $ex : new ClassNotFoundException('Cannot load class '.$class, null, $ex);
   }
});


/**
 * (7) setup error and exception handling
 */
\System::setupErrorHandling();


/**
 * (8) define namespaced helper functions
 */

/**
 * Dumps a variable to STDOUT or into a string.
 *
 * @param  mixed $var    - variable
 * @param  bool  $return - TRUE,  if the variable is to be dumped into a string;
 *                         FALSE, if the variable is to be dumped to STDOUT (default)
 *
 * @return string - string if the result is to be returned, NULL otherwise
 */
function dump($var, $return=false) {
   if ($return) ob_start();
   var_dump($var);
   if ($return) return ob_get_clean();

   ob_get_level() && ob_flush();          // flush output buffer if active
   return null;
}


/**
 * Functional replacement for "echo($var)" which is a language construct and can't be used as a regular function.
 *
 * @param  mixed $var
 */
function echof($var) {
   echo $var;
   ob_get_level() && ob_flush();          // flush output buffer if active
}


/**
 * Alias for printPretty($var, false)
 *
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var
 *
 * @see    printPretty()
 */
function echoPre($var) {
   printPretty($var, false);
}


/**
 * Alias for printPretty()
 *
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var    - variable
 * @param  bool  $return - TRUE,  if the result is to be returned as a string;
 *                         FALSE, if the result is to be printed to STDOUT (default)
 *
 * @return string - string if the result is to be returned, NULL otherwise
 *
 * @see    printPretty()
 */
function pp($var, $return=false) {
   return printPretty($var, $return);
}


/**
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var    - variable
 * @param  bool  $return - TRUE,  if the result is to be returned as a string;
 *                         FALSE, if the result is to be printed to STDOUT (default)
 *
 * @return string - string if the result is to be returned, NULL otherwise
 */
function printPretty($var, $return=false) {
   if (is_object($var) && method_exists($var, '__toString')) {
      $str = (string) $var;
   }
   elseif (is_object($var) || is_array($var)) {
      $str = print_r($var, true);
   }
   else if ($var === null) {
      $str = '(NULL)';                    // analogous to typeof(null) = 'NULL';
   }
   else if (is_bool($var)) {
      $str = $var ? 'true':'false';
   }
   else {
      $str = (string) $var;
   }

   if (!CLI)
      $str = '<div align="left"><pre style="margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.htmlSpecialChars($str, ENT_QUOTES).'</pre></div>';

   if (!strEndsWith($str, NL))
      $str .= NL;

   if ($return)
      return $str;

   echo $str;
   ob_get_level() && ob_flush();          // flush output buffer if active
   return null;
}


/**
 * Pretty printer for byte values.
 *
 * @param  int $value - byte value
 *
 * @return string - formatted byte value
 */
function prettyBytes($value) {
   if ($value < 1024)
      return (string) $value;
   $value = (int) $value;

   foreach (array('K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y') as $unit) {
      $value /= 1024;
      if ($value < 1024)
         break;
   }
   return sPrintF('%.1f%s', $value, $unit);
}


/**
 * Whether or not the byte order of the machine we are running on is "little endian".
 *
 * @return bool
 */
function isLittleEndian() {
   return (pack('S', 1) == "\x01\x00");
}


/**
 * Functional replacement for ($stringA === $stringB).
 *
 * @param  string $stringA
 * @param  string $stringB
 * @param  bool   $ignoreCase - default: no
 *
 * @return bool
 */
function strCompare($stringA, $stringB, $ignoreCase=false) {
   if (!is_null($stringA) && !is_string($stringB)) throw new IllegalTypeException('Illegal type of parameter $stringA: '.getType($stringA));
   if (!is_null($stringB) && !is_string($stringB)) throw new IllegalTypeException('Illegal type of parameter $stringB: '.getType($stringB));
   if (!is_bool($ignoreCase))                      throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

   if ($ignoreCase)
      return strCompareI($stringA, $stringB);
   return ($stringA === $stringB);
}


/**
 * Functional replacement for ($stringA === $stringB) ignoring upper/lower case differences.
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
 * Whether or not a string contains a substring.
 *
 * @param  string $haystack
 * @param  string $needle
 * @param  bool   $ignoreCase - default: no
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
 * Whether or not a string contains a substring ignoring upper/lower case differences.
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
 * Whether or not a string starts with a substring. If multiple prefixes are specified whether or not the string starts
 * with one of them.
 *
 * @param  string    $string
 * @param  string|[] $prefix     - one or more prefixes
 * @param  bool      $ignoreCase - default: no
 *
 * @return bool
 */
function strStartsWith($string, $prefix, $ignoreCase=false) {
   if (is_array($prefix)) {
      $self = __FUNCTION__;
      foreach ($prefix as $p) {
         if ($self($string, $p, $ignoreCase)) return true;
      }
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
 * Whether or not a string starts with a substring ignoring upper/lower case differences. If multiple prefixes are
 * specified whether or not the string starts with one of them.
 *
 * @param  string    $string
 * @param  string|[] $prefix - one or more prefixes
 *
 * @return bool
 */
function strStartsWithI($string, $prefix) {
   return strStartsWith($string, $prefix, true);
}


/**
 * Whether or not a string ends with a substring. If multiple suffixes are specified whether or not the string ends
 * with one of them.
 *
 * @param  string    $string
 * @param  string|[] $suffix     - one or more suffixes
 * @param  bool      $ignoreCase - default: no
 *
 * @return bool
 */
function strEndsWith($string, $suffix, $ignoreCase=false) {
   if (is_array($suffix)) {
      $self = __FUNCTION__;
      foreach ($suffix as $s)
         if ($self($string, $s, $ignoreCase)) return true;
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
 * Whether or not a string ends with a substring ignoring upper/lower case differences. If multiple suffixes are
 * specified whether or not the string ends with one of them.
 *
 * @param  string    $string
 * @param  string|[] $suffix - one or more suffixes
 *
 * @return bool
 */
function strEndsWithI($string, $suffix) {
   return strEndsWith($string, $suffix, true);
}


/**
 * Return a left part of a string.
 *
 * @param  string $string - initial string
 * @param  int    $length - greater than/equal to zero: length of the returned substring
 *                          lower than zero:            all except the specified number of right characters
 *
 * @return string - substring
 *
 * @example
 * <pre>
 * strLeft('abcde',  2) => 'ab'
 * strLeft('abcde', -1) => 'abcd'
 * </pre>
 */
function strLeft($string, $length) {
   if (!is_int($length))         throw new IllegalTypeException('Illegal type of parameter $length: '.getType($length));
   if ($string === null)
      return '';
   if (is_int($string))
      $string = (string)$string;
   else if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   return subStr($string, 0, $length);
}


/**
 * Return the left part of a string up to the specified occurrence of a limiting substring.
 *
 * @param  string $string         - initial string
 * @param  string $limiter        - limiting substring (one or more characters)
 * @param  int    $count          - positive: the specified occurrence of the limiting substring from the start
 *                                            of the string
 *                                  negative: the specified occurrence of the limiting substring from the end of
 *                                            the string
 *                                  zero:     an empty string is returned
 *                                  (default: 1 = the first occurrence)
 * @param  bool   $includeLimiter - whether or not to include the limiting substring in the returned result
 *                                  (default: FALSE)
 * @param  mixed  $onNotFound     - value to return if the specified occurrence of the limiting substring is not found
 *                                  (default: the initial string)
 *
 * @return string - left part of the initial string or the $onNotFound value
 *
 * @example
 * <pre>
 * strLeftTo('abcde', 'd')      => 'abc'
 * strLeftTo('abcde', 'x')      => 'abcde'   // limiter not found
 * strLeftTo('abccc', 'c',   3) => 'abcc'
 * strLeftTo('abccc', 'c',  -3) => 'ab'
 * strLeftTo('abccc', 'c', -99) => 'abccc'   // number of occurrences not found
 * </pre>
 */
function strLeftTo($string, $limiter, $count=1, $includeLimiter=false, $onNotFound=null) {
   if (!is_string($string))       throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if (!is_string($limiter))      throw new IllegalTypeException('Illegal type of parameter $limiter: '.getType($limiter));
   if (!strLen($limiter))         throw new IllegalArgumentException('Illegal limiting substring: "" (empty)');
   if (!is_int($count))           throw new IllegalTypeException('Illegal type of parameter $count: '.getType($count));
   if (!is_bool($includeLimiter)) throw new IllegalTypeException('Illegal type of parameter $includeLimiter: '.getType($includeLimiter));

   if ($count > 0) {
      $pos = -1;
      while ($count) {
         $offset = $pos + 1;
         $pos = strPos($string, $limiter, $offset);
         if ($pos === false)                                      // not found
            return func_num_args() > 4 ? $onNotFound : $string;
         $count--;
      }
      $result = subStr($string, 0, $pos);
      if ($includeLimiter)
         $result .= $limiter;
      return $result;
   }

   if ($count < 0) {
      $len = strLen($string);
      $pos = $len;
      while ($count) {
         $offset = $pos - $len - 1;
         if ($offset < -$len)                                     // not found
            return func_num_args() > 4 ? $onNotFound : $string;
         $pos = strRPos($string, $limiter, $offset);
         if ($pos === false)                                      // not found
            return func_num_args() > 4 ? $onNotFound : $string;
         $count++;
      }
      $result = subStr($string, 0, $pos);
      if ($includeLimiter)
         $result .= $limiter;
      return $result;
   }

   // $count == 0
   return '';
}


/**
 * Return a right part of a string.
 *
 * @param  string $string - initial string
 * @param  int    $length - greater than/equal to zero: length of the returned substring
 *                          lower than zero:            all except the specified number of left characters
 *
 * @return string - substring
 *
 * @example
 * <pre>
 * strRight('abcde',  1) => 'e'
 * strRight('abcde', -2) => 'cde'
 * </pre>
 */
function strRight($string, $length) {
   if (!is_int($length))         throw new IllegalTypeException('Illegal type of parameter $length: '.getType($length));
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
 * Return the right part of a string from the specified occurrence of a limiting substring.
 *
 * @param  string $string         - initial string
 * @param  string $limiter        - limiting substring (one or more characters)
 * @param  int    $count          - positive: the specified occurrence of the limiting substring counted from the
 *                                            start of the string
 *                                  negative: the specified occurrence of the limiting substring counted from the
 *                                            end of the string
 *                                  zero:     the initial string is returned
 *                                  (default: 1 = the first occurrence)
 * @param  bool   $includeLimiter - whether or not to include the limiting substring in the returned result
 *                                  (default: FALSE)
 * @param  mixed  $onNotFound     - value to return if the specified occurrence of the limiting substring is not found
 *                                  (default: empty string)
 *
 * @return string - right part of the initial string or the $onNotFound value
 *
 * @example
 * <pre>
 * strRightFrom('abc_abc', 'c')     => '_abc'
 * strRightFrom('abcabc',  'x')     => ''             // limiter not found
 * strRightFrom('abc_abc', 'a',  2) => 'bc'
 * strRightFrom('abc_abc', 'b', -2) => 'c_abc'
 * </pre>
 */
function strRightFrom($string, $limiter, $count=1, $includeLimiter=false, $onNotFound=null) {
   if (!is_string($string))       throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if (!is_string($limiter))      throw new IllegalTypeException('Illegal type of parameter $limiter: '.getType($limiter));
   if (!strLen($limiter))         throw new IllegalArgumentException('Illegal limiting substring: "" (empty)');
   if (!is_int($count))           throw new IllegalTypeException('Illegal type of parameter $count: '.getType($count));
   if (!is_bool($includeLimiter)) throw new IllegalTypeException('Illegal type of parameter $includeLimiter: '.getType($includeLimiter));

   if ($count > 0) {
      $pos = -1;
      while ($count) {
         $offset = $pos + 1;
         $pos = strPos($string, $limiter, $offset);
         if ($pos === false)                                      // not found
            return func_num_args() > 4 ? $onNotFound : '';
         $count--;
      }
      $result = subStr($string, $pos + strLen($limiter));
      if ($includeLimiter)
         $result = $limiter.$result;
      return $result;
   }

   if ($count < 0) {
      $len = strLen($string);
      $pos = $len;
      while ($count) {
         $offset = $pos - $len - 1;
         if ($offset < -$len)                                     // not found
            return func_num_args() > 4 ? $onNotFound : '';
         $pos = strRPos($string, $limiter, $offset);
         if ($pos === false)                                      // not found
            return func_num_args() > 4 ? $onNotFound : '';
         $count++;
      }
      $result = subStr($string, $pos + strLen($limiter));
      if ($includeLimiter)
         $result = $limiter.$result;
      return $result;
   }

   // $count == 0
   return $string;
}


/**
 * Whether or not a string is wrapped in single or double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsQuoted($value) {
   if (is_null($value) || is_int($value))
      return false;
   if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

   return strLen($value)>1 && (strIsSingleQuoted($value) || strIsDoubleQuoted($value));
}


/**
 * Whether or not a string is wrapped in single quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsSingleQuoted($value) {
   if (is_null($value) || is_int($value))
      return false;
   if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

   return (($len=strLen($value))>1 && $value{0}=="'" && $value{--$len}=="'");
}


/**
 * Whether or not a string is wrapped in double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDoubleQuoted($value) {
   if (is_null($value) || is_int($value))
      return false;
   if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

   return (($len=strLen($value))>1 && $value{0}=='"' && $value{--$len}=='"');
}


/**
 * Whether or not a string consists of numerical characters (0-9).
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDigits($value) {
   if (is_null($value)) return false;
   if (is_int($value))  return ($value >= 0);
   if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

   return ctype_digit($value);
}


/**
 * Alias for getType() for C/C++ enthusiasts.
 *
 * @param  mixed $var
 *
 * @return string
 *
 * @see    getType()
 */
function typeOf($var) {
   return getType($var);
}


/**
 * Check whether a directory exists. If not try to create it. Check further if write permission is granted.
 *
 * @param  string $path - same as mkDir(): directory name
 * @param  int    $mode - same as mkDir(): permission mode to set if the directory is created
 *                                         (default: 0770 = rwxrwx---)
 */
function mkDirWritable($path, $mode=0770) {
   if (!is_string($path))                            throw new IllegalTypeException('Illegal type of parameter $path: '.getType($path));
   if (!is_null($mode) && !is_int($mode))            throw new IllegalTypeException('Illegal type of parameter $mode: '.getType($mode));

   if (is_file($path))                               throw new IOException('Cannot write to directory "'.$path.'" (is a file)');
   if (!is_dir($path) && !mkDir($path, $mode, true)) throw new IOException('Cannot create directory "'.$path.'"');
   if (!is_writable($path))                          throw new IOException('Cannot write to directory "'.$path.'"');
}


/**
 * Whether or not the specified class exists (defined or undefined) and is not an interface. The function calls all
 * registered class loaders. Opposite to a call of
 * <pre>
 *    class_exist($name, true)
 * </pre>
 * it does not terminate the script if the class can't be loaded.
 *
 * @param  string $name - class name
 *
 * @return bool
 */
function is_class($name) {
   if (class_exists    ($name, $autoload=false)) return true;
   if (interface_exists($name, $autoload=false)) return false;

   try {
      $functions = spl_autoload_functions();
      if (!$functions) {               // no loader nor __autoload() exist: spl_autoload_call() will call spl_autoload()
         spl_autoload_call($name);     // onError: Uncaught LogicException: Class $name could not be loaded
      }
      else if (sizeOf($functions)==1 && $functions[0]==='__autoload') {
         __autoload($name);            // __autoload() exists and is explicitely or implicitely registered
      }
      else {
         spl_autoload_call($name);     // a regular SPL loader queue is defined
      }
   }
   catch (ClassNotFoundException $ex) {}

   return class_exists($name, $autoload=false);
}


/**
 * Whether or not the specified interface exists (defined or undefined) and is not a class. The function calls all
 * registered class loaders. Opposite to a call of
 * <pre>
 *    interface_exist($name, true)
 * </pre>
 * it does not terminate the script if the interface can't be loaded.
 *
 * @param  string $name - interface name
 *
 * @return bool
 */
function is_interface($name) {
   if (interface_exists($name, $autoload=false)) return true;
   if (class_exists    ($name, $autoload=false)) return false;

   try {
      $functions = spl_autoload_functions();
      if (!$functions) {               // no loader nor __autoload() exist: spl_autoload_call() will call spl_autoload()
         spl_autoload_call($name);     // onError: Uncaught LogicException: Class $name could not be loaded
      }
      else if (sizeOf($functions)==1 && $functions[0]==='__autoload') {
         __autoload($name);            // __autoload() exists and is explicitely or implicitely registered
      }
      else {
         spl_autoload_call($name);     // a regular SPL loader queue is defined
      }
   }
   catch (ClassNotFoundException $ex) {}

   return interface_exists($name, $autoload=false);
}


/**
 * Procedural replacement for rosasurfer\util\CommonValidator::isDateTime()
 *
 * Whether or not the specified string value represents a valid date or datetime value.
 *
 * @param  string    $string - string value
 * @param  string|[] $format - A valid date/datetime format. If multiple values are supplied whether or not the specified
 *                             string fits at least one of them.
 *                             Supported format string: 'Y-m-d [H:i[:s]]'
 *                                                      'Y.m.d [H:i[:s]]'
 *                                                      'd.m.Y [H:i[:s]]'
 *                                                      'd/m/Y [H:i[:s]]'
 *
 * @return int|bool - timestamp matching the string or FALSE if the string is not a valid date/datetime value
 *
 * @see    rosasurfer\util\CommonValidator::isDateTime()
 */
function is_datetime($string, $format) {
   return \CommonValidator::isDateTime($string, $format);
}


/**
 * Procedural replacement for the value TRUE for quick code analysis.
 *
 * @param  mixed $value - ignored
 *
 * @return TRUE
 */
function _true($value=null) {
   return true;
}


/**
 * Procedural replacement for the value FALSE for quick code analysis.
 *
 * @param  mixed $value - ignored
 *
 * @return FALSE
 */
function _false($value=null) {
   return false;
}


/**
 * Procedural replacement for the value NULL for quick code analysis.
 *
 * @param  mixed $value - ignored
 *
 * @return NULL
 */
function _null($value=null) {
   return null;
}


/**
 * Return $value or $altValue if $value is NULL. Procedural replacement for ternary test for NULL.
 *
 * @param  mixed $value
 * @param  mixed $altValue
 *
 * @return mixed
 */
function ifNull($value, $altValue) {
   return ($value===null) ? $altValue : $value;
}
