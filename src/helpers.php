<?php
/**
 * Helper constants and functions
 */
namespace rosasurfer;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use rosasurfer\ministruts\url\Url;
use rosasurfer\ministruts\url\VersionedUrl;

use rosasurfer\util\Validator;


// prevent multiple includes
if (defined('rosasurfer\CLI')) return;

// whether or not we run on a command line interface, on localhost and/or on Windows
define('rosasurfer\CLI'      , !isSet($_SERVER['REQUEST_METHOD']));
define('rosasurfer\LOCALHOST', !CLI && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', $_SERVER['SERVER_ADDR']]));
define('rosasurfer\WINDOWS'  , (strToUpper(subStr(PHP_OS, 0, 3))=='WIN'));

// custom log level
const L_DEBUG           =  1;
const L_INFO            =  2;
const L_NOTICE          =  4;
const L_WARN            =  8;
const L_ERROR           = 16;
const L_FATAL           = 32;

// log destinations for the built-in function error_log()
const ERROR_LOG_DEFAULT =  0;                                     // message is sent to the configured log or the system logger
const ERROR_LOG_MAIL    =  1;                                     // message is sent by email
const ERROR_LOG_DEBUG   =  2;                                     // message is sent through the PHP debugging connection
const ERROR_LOG_FILE    =  3;                                     // message is appended to a file destination
const ERROR_LOG_SAPI    =  4;                                     // message is sent directly to the SAPI logging handler

// time periods
const SECOND            =   1;           const SECONDS = SECOND;
const MINUTE            =  60 * SECONDS; const MINUTES = MINUTE;
const HOUR              =  60 * MINUTES; const HOURS   = HOUR;
const DAY               =  24 * HOURS;   const DAYS    = DAY;
const WEEK              =   7 * DAYS;    const WEEKS   = WEEK;
const MONTH             =  31 * DAYS;    const MONTHS  = MONTH;   // fuzzy but garantied to cover any month
const YEAR              = 366 * DAYS;    const YEARS   = YEAR;    // fuzzy but garantied to cover any year

// weekdays
const SUNDAY            = 0;
const MONDAY            = 1;
const TUESDAY           = 2;
const WEDNESDAY         = 3;
const THURSDAY          = 4;
const FRIDAY            = 5;
const SATURDAY          = 6;

// byte sizes
const KB                = 1024;
const MB                = 1024 * KB;
const GB                = 1024 * MB;                              // no TB (doesn't fit in 32 bits)

// array indexing types
const ARRAY_ASSOC       = 1;
const ARRAY_NUM         = 2;
const ARRAY_BOTH        = 3;

// php.ini changable modes
const PHP_INI_ALL       = 0;                                      // entry can be set anywhere
const PHP_INI_USER      = 1;                                      // entry can be set in scripts and in .user.ini
const PHP_INI_ONLY      = 2;                                      // entry can be set in php.ini only
const PHP_INI_SYSTEM    = 3;                                      // entry can be set in php.ini and in httpd.conf
const PHP_INI_PERDIR    = 4;                                      // entry can be set in php.ini, httpd.conf, .htaccess and in .user.ini

// PHP types
const PHP_TYPE_BOOL     = 1;
const PHP_TYPE_INT      = 2;
const PHP_TYPE_FLOAT    = 3;
const PHP_TYPE_STRING   = 4;
const PHP_TYPE_ARRAY    = 5;

// miscellaneous
const NL                = "\n";                                   // - ctrl --- hex --- dec ----
const EOL_MAC           = "\r";                                   //   CR       0D      13
const EOL_NETSCAPE      = "\r\r\n";                               //   CRCRLF   0D0D0A  13,13,10
const EOL_UNIX          = "\n";                                   //   LF       0A      10
const EOL_WINDOWS       = "\r\n";                                 //   CRLF     0D0A    13,10

!defined('PHP_INT_MIN') && define('PHP_INT_MIN', ~PHP_INT_MAX);   // built-in since PHP 7.0 (global)


/**
 * Dumps a variable to STDOUT or into a string.
 *
 * @param  mixed $var          - variable
 * @param  bool  $return       - TRUE,  if the variable is to be dumped into a string;
 *                               FALSE, if the variable is to be dumped to STDOUT (default)
 * @param  bool  $flushBuffers - whether or not to flush output buffers on output (default: TRUE)
 *
 * @return string|null - string if the result is to be returned, NULL otherwise
 */
function dump($var, $return=false, $flushBuffers=true) {
   if ($return) ob_start();
   var_dump($var);
   if ($return) return ob_get_clean();

   if ($flushBuffers)
      ob_get_level() && ob_flush();
   return null;
}


/**
 * Functional replacement for "echo($var)" which is a language construct and can't be used as a regular function.
 *
 * @param  mixed $var
 * @param  bool  $flushBuffers - whether or not to flush output buffers (default: TRUE)
 */
function echof($var, $flushBuffers=true) {
   echo $var;
   if ($flushBuffers)
      ob_get_level() && ob_flush();
}


/**
 * Alias for printPretty($var, false, $flushBuffers)
 *
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var
 * @param  bool  $flushBuffers - whether or not to flush output buffers (default: TRUE)
 *
 * @see    printPretty()
 */
function echoPre($var, $flushBuffers=true) {
   printPretty($var, false, $flushBuffers);
}


/**
 * Alias for printPretty()
 *
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var          - variable
 * @param  bool  $return       - TRUE,  if the result is to be returned as a string;
 *                               FALSE, if the result is to be printed to STDOUT (default)
 * @param  bool  $flushBuffers - whether or not to flush output buffers on output (default: TRUE)
 *
 * @return string - string if the result is to be returned, NULL otherwise
 *
 * @see    printPretty()
 */
function pp($var, $return=false, $flushBuffers=true) {
   return printPretty($var, $return, $flushBuffers);
}


/**
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var          - variable
 * @param  bool  $return       - TRUE,  if the result is to be returned as a string;
 *                               FALSE, if the result is to be printed to STDOUT (default)
 * @param  bool  $flushBuffers - whether or not to flush output buffers on output (default: TRUE)
 *
 * @return string - string if the result is to be returned, NULL otherwise
 */
function printPretty($var, $return=false, $flushBuffers=true) {
   if (is_object($var) && method_exists($var, '__toString')) {
      $str = $var->__toString($levels=PHP_INT_MAX);
   }
   elseif (is_object($var) || is_array($var)) {
      $str = print_r($var, true);
   }
   elseif ($var === null) {
      $str = '(NULL)';                    // analogous to typeof(null) = 'NULL';
   }
   elseif (is_bool($var)) {
      $str = $var ? 'true':'false';
   }
   else {
      $str = (string) $var;
   }

   if (!CLI)
      $str = '<div align="left"><pre style="z-index:65535; margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.htmlSpecialChars($str, ENT_QUOTES).'</pre></div>';

   if (!strEndsWith($str, NL))
      $str .= NL;

   if ($return)
      return $str;

   echo $str;

   if ($flushBuffers)
      ob_get_level() && ob_flush();
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
   if ($stringA!==null && !is_string($stringA)) throw new IllegalTypeException('Illegal type of parameter $stringA: '.getType($stringA));
   if ($stringB!==null && !is_string($stringB)) throw new IllegalTypeException('Illegal type of parameter $stringB: '.getType($stringB));
   if (!is_bool($ignoreCase))                   throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

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
   if ($stringA!==null && !is_string($stringA)) throw new IllegalTypeException('Illegal type of parameter $stringA: '.getType($stringA));
   if ($stringB!==null && !is_string($stringB)) throw new IllegalTypeException('Illegal type of parameter $stringB: '.getType($stringB));

   if ($stringA===null || $stringB===null)
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
   if ($haystack!==null && !is_string($haystack)) throw new IllegalTypeException('Illegal type of parameter $haystack: '.getType($haystack));
   if (!is_string($needle))                       throw new IllegalTypeException('Illegal type of parameter $needle: '.getType($needle));
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
 * @param  string          $string
 * @param  string|string[] $prefix     - one or more prefixes
 * @param  bool            $ignoreCase - default: no
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

   if ($string!==null && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if (!is_string($prefix))                   throw new IllegalTypeException('Illegal type of parameter $prefix: '.$prefix.' ('.getType($prefix).')');
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
 * Whether or not a string starts with a substring ignoring upper/lower case differences. If multiple prefixes are
 * specified whether or not the string starts with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $prefix - one or more prefixes
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
 * @param  string          $string
 * @param  string|string[] $suffix     - one or more suffixes
 * @param  bool            $ignoreCase - default: no
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
   if ($string!==null && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
   if (!is_string($suffix))                   throw new IllegalTypeException('Illegal type of parameter $suffix: '.$suffix.' ('.getType($suffix).')');
   if (!is_bool($ignoreCase))                 throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

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
 * @param  string          $string
 * @param  string|string[] $suffix - one or more suffixes
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
   elseif (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

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
   if (!is_int($length))        throw new IllegalTypeException('Illegal type of parameter $length: '.getType($length));
   if ($string === null)
      return '';
   if (is_int($string))
      $string = (string)$string;
   elseif (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

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
      $pos   += strLen($limiter);
      $result = ($pos >= strLen($string)) ? '' : subStr($string, $pos);
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
      $pos   += strLen($limiter);
      $result = ($pos >= strLen($string)) ? '' : subStr($string, $pos);
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
   if (!is_string($value))
      return false;
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
   if (!is_string($value))
      return false;
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
   if (!is_string($value))
      return false;
   return (($len=strLen($value))>1 && $value{0}=='"' && $value{--$len}=='"');
}


/**
 * Whether or not a string consists only of digits (0-9).
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDigits($value) {
   return ctype_digit($value);
}


/**
 * Whether or not a string consists only of numerical characters and represents a valid numerical value.
 * Opposite to the PHP built-in function is_numeric() this function returns FALSE if the string
 * begins with non-numerical characters (e.g. white space).
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsNumeric($value) {
   if (is_int($value) || is_float($value))
      return true;

   if (!is_string($value))
      return false;

   if (!is_numeric($value))
      return false;
   return ctype_graph($value);
}


/**
 * Convert a boolean representation to a boolean.
 *
 * @param  mixed $value - boolean representation
 *
 * @return bool|null - Boolean or NULL if the parameter doesn't represent a boolean. The accepted values of a boolean's
 *                     numerical string representation (integer or float) are 0 (zero) and 1 (one).
 */
function strToBool($value) {
   if (is_bool($value)) return $value;

   if (is_int($value) || is_float($value)) {
      if (!$value)
         return false;
      return ($value==1.) ? true : null;
   }
   if (!is_string($value)) return null;

   switch (strToLower($value)) {
      case 'true' :
      case 'on'   :
      case 'yes'  : return true;
      case 'false':
      case 'off'  :
      case 'no'   : return false;
   }

   if (strIsNumeric($value)) {
      $value = (float) $value;               // skip leading zeros of numeric strings
      if (!$value)      return false;
      if ($value == 1.) return true;
   }
   return null;
}


/**
 * Normalize line endings in a string. If the string contains mixed line endings the number of lines of the original
 * and the resulting string may differ. Netscape line endings are honored only if all line endings are Netscape format
 * (no mixed mode).
 *
 * @param  string $string - string to normalize
 * @param  string $mode   - format of the resulting string, can be one of:
 *                          EOL_MAC:      line endings are converted to Mac format      "\r"
 *                          EOL_NETSCAPE: line endings are converted to Netscape format "\r\r\n"
 *                          EOL_UNIX:     line endings are converted to Unix format     "\n" (default)
 *                          EOL_WINDOWS:  line endings are converted to Windows format  "\r\n"
 * @return string
 */
function normalizeEOL($string, $mode = EOL_UNIX) {
   if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

   $done = false;

   if (strContains($string, EOL_NETSCAPE)) {
      $tmp = str_replace(EOL_NETSCAPE, EOL_UNIX, $string, $count1);
      if (!strContains($tmp, EOL_MAC)) {
         str_replace(EOL_UNIX, '.', $tmp, $count2);
         if ($count1 == $count2) {
            $string = $tmp;            // only Netscape => OK
            $done   = true;
         }
      }
   }
   if (!$done) $string = str_replace([EOL_WINDOWS, EOL_MAC], EOL_UNIX, $string);

   if ($mode===EOL_MAC || $mode===EOL_NETSCAPE || $mode===EOL_WINDOWS) {
      $string = str_replace(EOL_UNIX, $mode, $string);
   }
   else if ($mode !== EOL_UNIX) {
      throw new InvalidArgumentException('Invalid parameter $mode: '.is_array($mode) ? 'array':$mode);
   }
   return $string;
}


/**
 * Alias for getType() for C/C++ enthusiasts.
 *
 * @param  mixed $var
 *
 * @return string
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
   if ($mode!==null && !is_int($mode))               throw new IllegalTypeException('Illegal type of parameter $mode: '.getType($mode));

   if (is_file($path))                               throw new IOException('Cannot write to directory "'.$path.'" (is a file)');
   if (!is_dir($path) && !mkDir($path, $mode, true)) throw new IOException('Cannot create directory "'.$path.'"');
   if (!is_writable($path))                          throw new IOException('Cannot write to directory "'.$path.'"');
}


/**
 * Whether or not the specified class exists (loaded or not) and is not an interface or a trait. Identical to
 * <pre>class_exists($name, true)</pre> except it also returnes FALSE if auto loading triggers an exception.
 *
 * @param  string $name - class name
 *
 * @return bool
 */
function is_class($name) {
   try {
      return class_exists($name, true);
   }
   catch (\Exception $ex) {/* loaders might wrongly throw exceptions blocking us from continuation */}

   return class_exists($name, false);
}


/**
 * Whether or not the specified interface exists (loaded or not) and is not a class or a trait. Identical to
 * <pre>interface_exists($name, true)</pre> except it also returnes FALSE if auto loading triggers an exception.
 *
 *
 * @param  string $name - interface name
 *
 * @return bool
 */
function is_interface($name) {
   try {
      return interface_exists($name, true);
   }
   catch (\Exception $ex) {/* loaders might wrongly throw exceptions blocking us from continuation */}

   return interface_exists($name, false);
}


/**
 * Whether or not the specified trait exists (loaded or not) and is not a class or an interface. Identical to
 * <pre>trait_exists($name, true)</pre> except it also returnes FALSE if auto loading triggers an exception.
 *
 * @param  string $name - trait name
 *
 * @return bool
 */
function is_trait($name) {
   try {
      return trait_exists($name, true);
   }
   catch (\Exception $ex) {/* loaders might wrongly throw exceptions blocking us from continuation */}

   return trait_exists($name, false);
}


/**
 * Return one of the metatypes "class", "interface" or "trait" for an object type identifier.
 *
 * @param  string $name - name
 *
 * @return string metatype
 */
function metatypeOf($name) {
   if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
   if ($name == '')       throw new InvalidArgumentException('Invalid argument $name: ""');

   if (is_class    ($name)) return 'class';
   if (is_interface($name)) return 'interface';
   if (is_trait    ($name)) return 'trait';

   return '(unknown type)';
}


/**
 * Procedural replacement for rosasurfer\util\Validator::isDateTime()
 *
 * Whether or not the specified string value represents a valid date or datetime value.
 *
 * @param  string          $string - string value
 * @param  string|string[] $format - A valid date/datetime format. If multiple values are supplied whether or not the specified
 *                                   string fits at least one of them.
 *                                   Supported format strings: 'Y-m-d [H:i[:s]]'
 *                                                              'Y.m.d [H:i[:s]]'
 *                                                              'd.m.Y [H:i[:s]]'
 *                                                              'd/m/Y [H:i[:s]]'
 *
 * @return int|bool - timestamp matching the string or FALSE if the string is not a valid date/datetime value
 *
 * @see    rosasurfer\util\Validator::isDateTime()
 */
function is_datetime($string, $format='Y-m-d') {
   return Validator::isDateTime($string, $format);
}


/**
 * Functional equivalent of the value TRUE.
 *
 * @param  mixed $value - ignored
 *
 * @return bool - TRUE
 */
function _true($value=null) {
   return true;
}


/**
 * Return $value or $altValue if $value is TRUE. Functional equivalent of ternary test for TRUE.
 *
 * @param  mixed $value
 * @param  mixed $altValue
 *
 * @return mixed
 */
function ifTrue($value, $altValue) {
   return ($value===true) ? $altValue : $value;
}


/**
 * Functional equivalent of the value FALSE.
 *
 * @param  mixed $value - ignored
 *
 * @return bool - FALSE
 */
function _false($value=null) {
   return false;
}


/**
 * Return $value or $altValue if $value is FALSE. Functional equivalent of ternary test for FALSE.
 *
 * @param  mixed $value
 * @param  mixed $altValue
 *
 * @return mixed
 */
function ifFalse($value, $altValue) {
   return ($value===false) ? $altValue : $value;
}


/**
 * Functional equivalent of the value NULL.
 *
 * @param  mixed $value - ignored
 *
 * @return NULL
 */
function _null($value=null) {
   return null;
}


/**
 * Return $value or $altValue if $value is NULL. Functional equivalent of ternary test for NULL.
 *
 * @param  mixed $value
 * @param  mixed $altValue
 *
 * @return mixed
 *
 * @see    is_null()
 */
function ifNull($value, $altValue) {
   return ($value===null) ? $altValue : $value;
}


/**
 * Return $value or $altValue if $value is empty. Functional equivalent of ternary test for empty().
 *
 * @param  mixed $value
 * @param  mixed $altValue
 *
 * @return mixed
 *
 * @see    empty()
 */
function ifEmpty($value, $altValue) {
   return empty($value) ? $altValue : $value;
}


/**
 * Return a sorted copy of the specified array using the algorythm and parameters of ksort().
 *
 * @param  array $values
 * @param  int   $sort_flags
 *
 * @return array
 *
 * @see    ksort()
 */
function ksort_r(array $values, $sort_flags=SORT_REGULAR) {
   ksort($values, $sort_flags);
   return $values;
}


/**
 * Return a pluralized message according to the specified number of items.
 *
 * @param  int   $count     - the number of items to determine the message form from
 * @param  string $singular - singular form of message
 * @param  string $plural   - plural form of message
 *
 * @return string
 */
function pluralize($count, $singular='', $plural='s') {
   if (!is_int($count)) throw new IllegalTypeException('Illegal type of parameter $count: '.getType($count));
   if (abs($count) == 1)
      return $singular;
   return $plural;
}


/**
 * Return a new URL helper instance. Procedural replacement for
 * <br>
 * <tt>new \rosasurfer\ministruts\url\Url(...)</tt>.
 *
 * @param  string $uri - URI part of the URL to generate. If the URI starts with a slash "/" it is interpreted as relative
 *                       to the application's base URI. If the URI doesn't start with a slash "/" it is interpreted as
 *                       relative to the current application <tt>Module</tt>'s base URI (the module the current HTTP request
 *                       belongs to).
 * @return Url
 */
function url($uri) {
   return new Url($uri);
}


/**
 * Return a new version-aware URL helper instance. Procedural replacement for
 * <br>
 * <tt>new \rosasurfer\ministruts\url\VersionedUrl(...)</tt>.
 *
 * @param  string $uri - URI part of the URL to generate. If the URI starts with a slash "/" it is interpreted as relative
 *                       to the application's base URI. If the URI doesn't start with a slash "/" it is interpreted as
 *                       relative to the current application <tt>Module</tt>'s base URI (the module the current HTTP request
 *                       belongs to).
 * @return VersionedUrl
 */
function versionedUrl($uri) {
   return new VersionedUrl($uri);
}
