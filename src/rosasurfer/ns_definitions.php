<?php
/**
 * Utility constants and functions. Mama's little helpers.
 */
namespace rosasurfer;

use rosasurfer\exception\ClassNotFoundException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use rosasurfer\ministruts\url\Url;
use rosasurfer\ministruts\url\VersionedUrl;

use rosasurfer\util\Validator;


// block re-includes
if (function_exists(__NAMESPACE__.'\dump'))
   return;


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
 * Whether or not the specified class exists (defined or undefined) and is not an interface or a trait. The function calls
 * all registered class loaders. Opposite to a call of <pre>class_exist($name, true)</pre> it does not terminate the script
 * if the class can't be loaded.
 *
 * @param  string $name - class name
 *
 * @return bool
 */
function is_class($name) {
   if (class_exists    ($name, false)) return true;
   if (interface_exists($name, false)) return false;
   if (trait_exists    ($name, false)) return false;

   try {
      $functions = spl_autoload_functions();
      if (!$functions) {               // no loader nor __autoload() exist: spl_autoload_call() will call spl_autoload()
         spl_autoload_call($name);     // onError: Uncaught LogicException: Class $name could not be loaded
      }
      else if (sizeOf($functions)==1 && $functions[0]==='__autoload') {
         __autoload($name);            // __autoload() exists and is explicitly or implicitly registered
      }
      else {
         spl_autoload_call($name);     // a regular SPL loader queue is defined
      }
   }
   catch (ClassNotFoundException $ex) {
      // TODO: loaders might trigger any kind of error/throw any kind of exception
   }

   return class_exists($name, false);
}


/**
 * Whether or not the specified interface exists (defined or undefined) and is not a class or a trait. The function calls
 * all registered class loaders. Opposite to a call of <pre>interface_exist($name, true)</pre> it does not terminate the
 * script if the interface can't be loaded.
 *
 * @param  string $name - interface name
 *
 * @return bool
 */
function is_interface($name) {
   if (interface_exists($name, false)) return true;
   if (class_exists    ($name, false)) return false;
   if (trait_exists    ($name, false)) return false;

   try {
      $functions = spl_autoload_functions();
      if (!$functions) {               // no loader nor __autoload() exist: spl_autoload_call() will call spl_autoload()
         spl_autoload_call($name);     // onError: Uncaught LogicException: Class $name could not be loaded
      }
      else if (sizeOf($functions)==1 && $functions[0]==='__autoload') {
         __autoload($name);            // __autoload() exists and is explicitly or implicitly registered
      }
      else {
         spl_autoload_call($name);     // a regular SPL loader queue is defined
      }
   }
   catch (ClassNotFoundException $ex) {
      // TODO: loaders might trigger any kind of error/throw any kind of exception
   }

   return interface_exists($name, false);
}


/**
 * Whether or not the specified trait exists (defined or undefined) and is not a class or an interface. The function calls
 * all registered class loaders. Opposite to a call of <pre>trait_exist($name, true)</pre> it does not terminate the script
 * if the trait can't be loaded.
 *
 * @param  string $name - trait name
 *
 * @return bool
 */
function is_trait($name) {
   if (trait_exists    ($name, false)) return true;
   if (class_exists    ($name, false)) return false;
   if (interface_exists($name, false)) return false;

   try {
      $functions = spl_autoload_functions();
      if (!$functions) {               // no loader nor __autoload() exist: spl_autoload_call() will call spl_autoload()
         spl_autoload_call($name);     // onError: Uncaught LogicException: Class $name could not be loaded
      }
      else if (sizeOf($functions)==1 && $functions[0]==='__autoload') {
         __autoload($name);            // __autoload() exists and is explicitly or implicitly registered
      }
      else {
         spl_autoload_call($name);     // a regular SPL loader queue is defined
      }
   }
   catch (ClassNotFoundException $ex) {
      // TODO: loaders might trigger any kind of error/throw any kind of exception
   }

   return trait_exists($name, false);
}


/**
 * Return one of the metatypes "class", "interface" or "trait" for an object type identifier (a name).
 *
 * @param  string $name - name
 *
 * @return string metatype
 */
function metaTypeToStr($name) {
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
 * @param  string    $string - string value
 * @param  string|[] $format - A valid date/datetime format. If multiple values are supplied whether or not the specified
 *                             string fits at least one of them.
 *                             Supported format strings: 'Y-m-d [H:i[:s]]'
 *                                                       'Y.m.d [H:i[:s]]'
 *                                                       'd.m.Y [H:i[:s]]'
 *                                                       'd/m/Y [H:i[:s]]'
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
 * @return TRUE
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
 * @return FALSE
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
 * <tt>new \rosasurfer\ministruts\url\Url(...)</tt>.
 *
 * @param  string $uri - URI part of the URL to generate
 *
 * @return Url
 */
function url($uri) {
   return new Url(...func_get_args());
}


/**
 * Return a new version-aware URL helper instance. Procedural replacement for
 * <tt>new \rosasurfer\ministruts\url\VersionedUrl(...)</tt>.
 *
 * @param  string $uri - URI part of the URL to generate
 *
 * @return VersionedUrl
 *
 * @see    rosasurfer\ministruts\url\Url
 */
function vUrl($uri) {
   return new VersionedUrl(...func_get_args());
}
