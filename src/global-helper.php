<?php
/**
 * Map the helper constants and functions in namespace \rosasurfer to the global namespace.
 */
use rosasurfer\ministruts\ActionMapping;
use rosasurfer\ministruts\Module;
use rosasurfer\ministruts\url\Url;
use rosasurfer\ministruts\url\VersionedUrl;


// runtime environment
const CLI               = \rosasurfer\CLI;
const LOCALHOST         = \rosasurfer\LOCALHOST;
const MACOS             = \rosasurfer\MACOS;
const WINDOWS           = \rosasurfer\WINDOWS;
const NUL               = \rosasurfer\NUL;                      // the system's NUL device name

// custom log level
const L_DEBUG           = \rosasurfer\L_DEBUG;
const L_INFO            = \rosasurfer\L_INFO;
const L_NOTICE          = \rosasurfer\L_NOTICE;
const L_WARN            = \rosasurfer\L_WARN;
const L_ERROR           = \rosasurfer\L_ERROR;
const L_FATAL           = \rosasurfer\L_FATAL;

// log destinations for the built-in function error_log()
const ERROR_LOG_DEFAULT = \rosasurfer\ERROR_LOG_DEFAULT;
const ERROR_LOG_MAIL    = \rosasurfer\ERROR_LOG_MAIL;
const ERROR_LOG_DEBUG   = \rosasurfer\ERROR_LOG_DEBUG;
const ERROR_LOG_FILE    = \rosasurfer\ERROR_LOG_FILE;
const ERROR_LOG_SAPI    = \rosasurfer\ERROR_LOG_SAPI;

// time periods
const SECOND            = \rosasurfer\SECOND; const SECONDS = SECOND;
const MINUTE            = \rosasurfer\MINUTE; const MINUTES = MINUTE;
const HOUR              = \rosasurfer\HOUR;   const HOURS   = HOUR;
const DAY               = \rosasurfer\DAY;    const DAYS    = DAY;
const WEEK              = \rosasurfer\WEEK;   const WEEKS   = WEEK;
const MONTH             = \rosasurfer\MONTH;  const MONTHS  = MONTH;
const YEAR              = \rosasurfer\YEAR;   const YEARS   = YEAR;

// weekdays
const SUNDAY            = \rosasurfer\SUNDAY;
const MONDAY            = \rosasurfer\MONDAY;
const TUESDAY           = \rosasurfer\TUESDAY;
const WEDNESDAY         = \rosasurfer\WEDNESDAY;
const THURSDAY          = \rosasurfer\THURSDAY;
const FRIDAY            = \rosasurfer\FRIDAY;
const SATURDAY          = \rosasurfer\SATURDAY;

// byte sizes
const KB                = \rosasurfer\KB;
const MB                = \rosasurfer\MB;
const GB                = \rosasurfer\GB;

// array indexing types
const ARRAY_ASSOC       = \rosasurfer\ARRAY_ASSOC;
const ARRAY_NUM         = \rosasurfer\ARRAY_NUM;
const ARRAY_BOTH        = \rosasurfer\ARRAY_BOTH;

// class member access levels
const ACCESS_PUBLIC     = \rosasurfer\ACCESS_PUBLIC;
const ACCESS_PROTECTED  = \rosasurfer\ACCESS_PROTECTED;
const ACCESS_PRIVATE    = \rosasurfer\ACCESS_PRIVATE;
const ACCESS_ALL        = \rosasurfer\ACCESS_ALL;

// miscellaneous
const NL                = \rosasurfer\NL;                       // = EOL_UNIX
const EOL_MAC           = \rosasurfer\EOL_MAC;                  // "\r"       CR       0D       13
const EOL_NETSCAPE      = \rosasurfer\EOL_NETSCAPE;             // "\r\r\n"   CRCRLF   0D0D0A   13,13,10
const EOL_UNIX          = \rosasurfer\EOL_UNIX;                 // "\n"       LF       0A       10
const EOL_WINDOWS       = \rosasurfer\EOL_WINDOWS;              // "\r\n"     CRLF     0D0A     13,10


/**
 * Whether or not an array-like variable has the specified key. Wrapper for PHP's disfunctional <tt>array_*</tt> functions
 * which do not work with PHP's own {@link \ArrayAccess} interface.
 *
 * @param  string             $key
 * @param  array|\ArrayAccess $array
 *
 * @return bool
 */
function array_key_exists_ex($key, $array) {
    return \rosasurfer\array_key_exists_ex($key, $array);
}


/**
 * Alias of {@link array_key_exists_ex()}.
 *
 * Whether or not an array-like variable has the specified key. Wrapper for PHP's disfunctional <tt>array_*</tt> functions
 * which do not work with PHP's own {@link \ArrayAccess} interface.
 *
 * @param  string             $key
 * @param  array|\ArrayAccess $array
 *
 * @return bool
 */
function key_exists_ex($key, $array) {
    return \rosasurfer\key_exists_ex($key, $array);
}


/**
 * Convert a value to a boolean and return the human-readable string "true" or "false".
 *
 * @param  mixed $value - value interpreted as a boolean
 *
 * @return string
 */
function boolToStr($value) {
    return \rosasurfer\boolToStr($value);
}


/**
 * Dumps a variable to the standard output device or into a string.
 *
 * @param  mixed $var                     - variable
 * @param  bool  $return       [optional] - TRUE,  if the variable is to be dumped into a string <br>
 *                                          FALSE, if the variable is to be dumped to the standard output device (default)
 * @param  bool  $flushBuffers [optional] - whether or not to flush output buffers on output (default: TRUE)
 *
 * @return string|null - string if the result is to be returned, NULL otherwise
 */
function dump($var, $return=false, $flushBuffers=true) {
    return \rosasurfer\dump($var, $return, $flushBuffers);
}


/**
 * Functional replacement for <tt>"echo($var)"</tt> which is a language construct and can't be used as a regular function.
 *
 * @param  mixed $var
 * @param  bool  $flushBuffers [optional] - whether or not to flush output buffers (default: TRUE)
 */
function echof($var, $flushBuffers = true) {
    \rosasurfer\echof($var, $flushBuffers);
}


/**
 * Alias of printPretty($var, false, $flushBuffers)
 *
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var
 * @param  bool  $flushBuffers [optional] - whether or not to flush output buffers (default: TRUE)
 *
 * @see    printPretty()
 */
function echoPre($var, $flushBuffers = true) {
    \rosasurfer\echoPre($var, $flushBuffers);
}


/**
 * Print a message to STDERR.
 *
 * @param  string $message
 */
function stderror($message) {
    \rosasurfer\stderror($message);
}


/**
 * Send an "X-Debug-???" header with a message. Each sent header name will end with a different and increasing number.
 *
 * @param  string $message
 */
function debugHeader($message) {
    \rosasurfer\debugHeader($message);
}


/**
 * Alias of {@link printPretty()}
 *
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var                     - variable
 * @param  bool  $return       [optional] - TRUE,  if the result is to be returned as a string <br>
 *                                          FALSE, if the result is to be printed to the standard output device (default)
 * @param  bool  $flushBuffers [optional] - whether or not to flush output buffers on output (default: TRUE)
 *
 * @return string|null - string if the result is to be returned, NULL otherwise
 *
 * @see    printPretty()
 */
function pp($var, $return=false, $flushBuffers=true) {
    return \rosasurfer\pp($var, $return, $flushBuffers);
}


/**
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var                     - variable
 * @param  bool  $return       [optional] - TRUE,  if the result is to be returned as a string <br>
 *                                          FALSE, if the result is to be printed to the standard output device (default)
 * @param  bool  $flushBuffers [optional] - whether or not to flush output buffers on output (default: TRUE)
 *
 * @return string|null - string if the result is to be returned, NULL otherwise
 */
function printPretty($var, $return=false, $flushBuffers=true) {
    return \rosasurfer\printPretty($var, $return, $flushBuffers);
}


/**
 * Format a byte value.
 *
 * @param  int|float|string $value               - byte value
 * @param  int              $decimals [optional] - number of decimal digits (default: 1)
 *
 * @return string - formatted byte value
 */
function prettyBytes($value, $decimals = 1) {
    return \rosasurfer\prettyBytes($value, $decimals);
}


/**
 * Convert a byte value to an integer supporting php.ini shorthand notation ("K", "M", "G").
 *
 * @param  string|int $value - byte value
 *
 * @return int - converted byte value
 */
function php_byte_value($value) {
    return \rosasurfer\php_byte_value($value);
}


/**
 * Inline replacement for number_format() removing the default parameter violation.
 *
 * @param  float  $number
 * @param  int    $decimals           [optional] - default: 0
 * @param  string $decimalSeparator   [optional] - default: dot "."
 * @param  string $thousandsSeparator [optional] - default: comma ","
 *
 * @return string - formatted number
 */
function numf($number, $decimals=0, $decimalSeparator='.', $thousandsSeparator=',') {
    return \rosasurfer\numf($number, $decimals, $decimalSeparator, $thousandsSeparator);
}


/**
 * Return the value of a php.ini option as a boolean.
 *
 * NOTE: Never use ini_get() to read boolean php.ini values as it will return the plain string passed to ini_set().
 *
 * @param  string $option            - option name
 * @param  bool   $strict [optional] - Whether or not to enable strict checking of the found value:
 *                                     TRUE:  invalid values cause a runtime exception
 *                                     FALSE: invalid values are converted to the target type (i.e. boolean)
 *                                     (default: TRUE)
 *
 * @return bool|null - boolean value or NULL if the setting doesn't exist
 */
function ini_get_bool($option, $strict = true) {
    return \rosasurfer\ini_get_bool($option, $strict);
}


/**
 * Return the value of a php.ini option as an integer.
 *
 * NOTE: Never use ini_get() to read php.ini integer values as it will return the plain string passed to ini_set().
 *
 * @param  string $option            - option name
 * @param  bool   $strict [optional] - Whether or not to enable strict checking of the found value:
 *                                     TRUE:  invalid values cause a runtime exception
 *                                     FALSE: invalid values are converted to the target type (i.e. integer)
 *                                     (default: TRUE)
 *
 * @return int|null - integer value or NULL if the setting doesn't exist
 */
function ini_get_int($option, $strict = true) {
    return \rosasurfer\ini_get_int($option, $strict);
}


/**
 * Return the value of a php.ini option as a byte value supporting PHP shorthand notation ("K", "M", "G").
 *
 * NOTE: Never use ini_get() to read php.ini byte values as it will return the plain string passed to ini_set().
 *
 * @param  string $option            - option name
 * @param  bool   $strict [optional] - Whether or not to enable strict checking of the found value:
 *                                     TRUE:  invalid values cause a runtime exception
 *                                     FALSE: invalid values are converted to the target type (i.e. integer)
 *                                     (default: TRUE)
 *
 * @return int|null - integer value or NULL if the setting doesn't exist
 */
function ini_get_bytes($option, $strict = true) {
    return \rosasurfer\ini_get_bytes($option, $strict);
}


/**
 * Convert special characters to HTML entities.
 *
 * Inline replacement and shortcut for htmlSpecialChars() using different default flags.
 *
 * @param  string $string
 * @param  int    $flags        [optional] - default: ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5
 * @param  string $encoding     [optional] - default: ini_get("default_charset")
 * @param  bool   $doubleEncode [optional] - default: TRUE
 *
 * @return string - converted string
 *
 * @see   \htmlSpecialChars()
 */
function hsc($string, $flags=null, $encoding=null, $doubleEncode=true) {
    return \rosasurfer\hsc($string, $flags, $encoding, $doubleEncode);
}


/**
 * Whether or not the byte order of the machine we are running on is "little endian".
 *
 * @return bool
 */
function isLittleEndian() {
    return \rosasurfer\isLittleEndian();
}


/**
 * Whether or not the specified path is relative or absolute, according to the current operating system.
 *
 * @param  string $path
 *
 * @return bool
 */
function isRelativePath($path) {
    return \rosasurfer\isRelativePath($path);
}


/**
 * Functional replacement for ($stringA === $stringB).
 *
 * @param  string $stringA
 * @param  string $stringB
 * @param  bool   $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strCompare($stringA, $stringB, $ignoreCase = false) {
    return \rosasurfer\strCompare($stringA, $stringB, $ignoreCase);
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
    return \rosasurfer\strCompareI($stringA, $stringB);
}


/**
 * Whether or not a string contains a substring.
 *
 * @param  string $haystack
 * @param  string $needle
 * @param  bool   $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strContains($haystack, $needle, $ignoreCase = false) {
    return \rosasurfer\strContains($haystack, $needle, $ignoreCase);
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
    return \rosasurfer\strContainsI($haystack, $needle);
}


/**
 * Whether or not a string starts with a substring. If multiple prefixes are specified whether or not the string starts
 * with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $prefix                - one or more prefixes
 * @param  bool            $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strStartsWith($string, $prefix, $ignoreCase = false) {
    return \rosasurfer\strStartsWith($string, $prefix, $ignoreCase);
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
    return \rosasurfer\strStartsWithI($string, $prefix);
}


/**
 * Whether or not a string ends with a substring. If multiple suffixes are specified whether or not the string ends
 * with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $suffix                - one or more suffixes
 * @param  bool            $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strEndsWith($string, $suffix, $ignoreCase = false) {
    return \rosasurfer\strEndsWith($string, $suffix, $ignoreCase);
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
    return \rosasurfer\strEndsWithI($string, $suffix);
}


/**
 * Return a left part of a string.
 *
 * @param  string $string - initial string
 * @param  int    $length - greater than/equal to zero: length of the returned substring<br>
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
    return \rosasurfer\strLeft($string, $length);
}


/**
 * Return the left part of a string up to the specified occurrence of a limiting substring.
 *
 * @param  string $string                    - initial string
 * @param  string $limiter                   - limiting substring (one or more characters)
 * @param  int    $count          [optional] - positive: the specified occurrence of the limiting substring from the start
 *                                                       of the string<br>
 *                                             negative: the specified occurrence of the limiting substring from the end of
 *                                                       the string<br>
 *                                             zero:     an empty string is returned<br>
 *                                             (default: 1 = the first occurrence)
 * @param  bool   $includeLimiter [optional] - whether or not to include the limiting substring in the returned result
 *                                             (default: FALSE)
 * @param  mixed  $onNotFound     [optional] - value to return if the specified occurrence of the limiting substring is not found
 *                                             (default: the initial string)
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
    return \rosasurfer\strLeftTo(...func_get_args());
}


/**
 * Return a right part of a string.
 *
 * @param  string $string - initial string
 * @param  int    $length - greater than/equal to zero: length of the returned substring<br>
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
    return \rosasurfer\strRight($string, $length);
}


/**
 * Return the right part of a string from the specified occurrence of a limiting substring.
 *
 * @param  string $string                    - initial string
 * @param  string $limiter                   - limiting substring (one or more characters)
 * @param  int    $count          [optional] - positive: the specified occurrence of the limiting substring counted from the
 *                                                       start of the string<br>
 *                                             negative: the specified occurrence of the limiting substring counted from the
 *                                                       end of the string<br>
 *                                             zero:     the initial string is returned<br>
 *                                             (default: 1 = the first occurrence)
 * @param  bool   $includeLimiter [optional] - whether or not to include the limiting substring in the returned result
 *                                             (default: FALSE)
 * @param  mixed  $onNotFound     [optional] - value to return if the specified occurrence of the limiting substring is not found
 *                                             (default: empty string)
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
    return \rosasurfer\strRightFrom(...func_get_args());
}


/**
 * Whether or not a string is wrapped in single or double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsQuoted($value) {
    return \rosasurfer\strIsQuoted($value);
}


/**
 * Whether or not a string is wrapped in single quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsSingleQuoted($value) {
    return \rosasurfer\strIsSingleQuoted($value);
}


/**
 * Whether or not a string is wrapped in double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDoubleQuoted($value) {
    return \rosasurfer\strIsDoubleQuoted($value);
}


/**
 * Whether or not a string consists only of digits (0-9).
 *
 * @param  mixed $value
 *
 * @return bool
 */
function strIsDigits($value) {
    return \rosasurfer\strIsDigits($value);
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
    return \rosasurfer\strIsNumeric($value);
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
    return \rosasurfer\strToBool($value);
}


/**
 * Reduce multiple consecutive white space characters in a string to a single one.
 *
 * @param  string $string               - string to process
 * @param  bool   $joinLines [optional] - whether or not to return a single line result (default: yes)
 * @param  string $separator [optional] - the separator to use for joining (default: space " ")
 *
 * @return string
 */
function strCollapseWhiteSpace($string, $joinLines=true, $separator=' ') {
    return \rosasurfer\strCollapseWhiteSpace($string, $joinLines, $separator);
}


/**
 * Normalize line endings of a string. If the string contains mixed line endings the number of lines of the original
 * and the resulting string may differ. Netscape line endings are honored only if all line endings are Netscape format
 * (no mixed mode).
 *
 * @param  string $string          - string to normalize
 * @param  string $mode [optional] - format of the resulting string, can be one of:<br>
 *                                   EOL_MAC:      line endings are converted to Mac format      "\r"<br>
 *                                   EOL_NETSCAPE: line endings are converted to Netscape format "\r\r\n"<br>
 *                                   EOL_UNIX:     line endings are converted to Unix format     "\n" (default)<br>
 *                                   EOL_WINDOWS:  line endings are converted to Windows format  "\r\n"
 * @return string
 */
function normalizeEOL($string, $mode = EOL_UNIX) {
    return \rosasurfer\normalizeEOL($string, $mode);
}


/**
 * Convert an object to an array.
 *
 * @param  object $object
 * @param  int    $access [optional] - access levels of the properties to return in the result
 *                                     (default: ACCESS_PUBLIC)
 * @return array
 */
function objectToArray($object, $access = ACCESS_PUBLIC) {
    return \rosasurfer\objectToArray($object, $access);
}


/**
 * Alias of getType() for C/C++ enthusiasts.
 *
 * @param  mixed $var
 *
 * @return string
 */
function typeOf($var) {
    return \rosasurfer\typeOf($var);
}


/**
 * Check whether a directory exists. If not try to create it. Check further if write permission is granted.
 *
 * @param  string $path            - same as mkDir(): directory name
 * @param  int    $mode [optional] - same as mkDir(): permission mode to set if the directory is created<br>
 *                                                    (default: 0755 = rwxr-xr-x)
 */
function mkDirWritable($path, $mode = 0755) {
    return \rosasurfer\mkDirWritable($path, $mode);
}


/**
 * Whether or not a directory is considered empty.
 *
 * (a directory with just '.svn' or '.git' is empty)
 *
 * @param  string          $dirname
 * @param  string|string[] $ignore - one or more directory entries to intentionally ignore during the check, e.g. ".git"
 *                                   (default: none)
 * @return bool
 */
function is_dir_empty($dirname, $ignore = []) {
    return \rosasurfer\is_dir_empty($dirname, $ignore);
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
    return \rosasurfer\is_class($name);
}


/**
 * Whether or not the specified interface exists (loaded or not) and is not a class or a trait. Identical to
 * <pre>interface_exists($name, true)</pre> except it also returnes FALSE if auto loading triggers an exception.
 *
 * @param  string $name - interface name
 *
 * @return bool
 */
function is_interface($name) {
    return \rosasurfer\is_interface($name);
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
    return \rosasurfer\is_trait($name);
}


/**
 * Whether or not a variable can be used like an array. Wrapper for PHP's disfunctional <tt>array_*</tt> functions which
 * do not work with PHP's own {@link \ArrayAccess} interface.
 *
 * @param  array|\ArrayAccess $var
 *
 * @return bool
 */
function is_array_ex($var) {
    return \rosasurfer\is_array_ex($var);
}


/**
 * Return the simple name of a class name (i.e. the base name).
 *
 * @param  string $className - full class name
 *
 * @return string
 */
function simpleClassName($className) {
    return \rosasurfer\simpleClassName($className);
}


/**
 * Return one of the metatypes "class", "interface" or "trait" for an object type identifier.
 *
 * @param  string $name - name
 *
 * @return string metatype
 */
function metatypeOf($name) {
    return \rosasurfer\metatypeOf($name);
}


/**
 * Procedural replacement for rosasurfer\util\Validator::isDateTime()
 *
 * Whether or not the specified string value represents a valid date or datetime value.
 *
 * @param  string          $string            - string value
 * @param  string|string[] $format [optional] - A valid date/datetime format. If multiple values are supplied whether or not the specified
 *                                              string fits at least one of them.<br>
 *                                              Supported format strings: 'Y-m-d [H:i[:s]]'<br>
 *                                                                         'Y.m.d [H:i[:s]]'<br>
 *                                                                         'd.m.Y [H:i[:s]]'<br>
 *                                                                         'd/m/Y [H:i[:s]]'
 *
 * @return int|bool - timestamp matching the string or FALSE if the string is not a valid date/datetime value
 *
 * @see    rosasurfer\util\Validator::isDateTime()
 */
function is_datetime($string, $format = 'Y-m-d') {
    return \rosasurfer\is_datetime($string, $format);
}


/**
 * Functional equivalent of the value TRUE.
 *
 * @param  mixed $value [optional] - ignored
 *
 * @return bool - TRUE
 */
function true($value = null) {
    return true;
}


/**
 * Return $value or $altValue if $value evaluates to TRUE. Functional equivalent of ternary test for TRUE.
 *
 * @param  mixed $value
 * @param  mixed $altValue
 *
 * @return mixed
 */
function ifTrue($value, $altValue) {
    return \rosasurfer\ifTrue($value, $altValue);
}


/**
 * Functional equivalent of the value FALSE.
 *
 * @param  mixed $value [optional] - ignored
 *
 * @return bool - FALSE
 */
function false($value = null) {
    return false;
}


/**
 * Return $value or $altValue if $value evaluates to FALSE. Functional equivalent of ternary test for FALSE.
 *
 * @param  mixed $value
 * @param  mixed $altValue
 *
 * @return mixed
 */
function ifFalse($value, $altValue) {
    return \rosasurfer\ifFalse($value, $altValue);
}


/**
 * Functional equivalent of the value NULL.
 *
 * @param  mixed $value [optional] - ignored
 *
 * @return NULL
 */
function null($value = null) {
    return null;
}


/**
 * Return $value or $altValue if $value is strictly NULL. Functional equivalent of ternary test for NULL.
 *
 * @param  mixed $value
 * @param  mixed $altValue
 *
 * @return mixed
 *
 * @see    is_null()
 */
function ifNull($value, $altValue) {
    return \rosasurfer\ifNull($value, $altValue);
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
    return \rosasurfer\ifEmpty($value, $altValue);
}


/**
 * Return a sorted copy of the specified array using the algorythm and parameters of ksort().
 *
 * @param  array $values
 * @param  int   $sort_flags [optional]
 *
 * @return array
 *
 * @see    ksort()
 */
function ksort_r(array $values, $sort_flags = SORT_REGULAR) {
    return \rosasurfer\ksort_r($values, $sort_flags);
}


/**
 * Return a pluralized string according to the specified number of items.
 *
 * @param  int    $count               - the number of items to determine the output from
 * @param  string $singular [optional] - singular form of string
 * @param  string $plural   [optional] - plural form of string
 *
 * @return string
 */
function pluralize($count, $singular='', $plural='s') {
    return \rosasurfer\pluralize($count, $singular, $plural);
}


/**
 * Execute a task in a synchronized way. Emulates the Java keyword "synchronized".
 *
 * @param  \Closure $task             - task to execute (an anonymous function is implicitly casted)
 * @param  string   $mutex [optional] - mutex identifier (default: the calling line of code)
 */
function synchronized(\Closure $task, $mutex = null) {
    return \rosasurfer\synchronized($task, $mutex);
}


/**
 * Lookup and return a {@link Url} helper for the named {@link ActionMapping}.
 *
 * @param  string $name - route name
 *
 * @return Url
 */
function route($name) {
    return \rosasurfer\route($name);
}


/**
 * Return a {@link Url} helper for the given URI. An URI starting with a slash "/" is interpreted as relative to the
 * application's base URI. An URI not starting with a slash is interpreted as relative to the application {@link Module}'s
 * base URI (the module the current request belongs to).<br>
 *
 * Procedural equivalent of <tt>new \rosasurfer\ministruts\url\Url($uri)</tt>.
 *
 * @param  string $uri
 *
 * @return Url
 */
function url($uri) {
    return \rosasurfer\url($uri);
}


/**
 * Return a version-aware URL helper for the given URI {@link VersionedUrl}. An URI starting with a slash "/" is interpreted
 * as relative to the application's base URI. An URI not starting with a slash is interpreted as relative to the application
 * {@link Module}'s base URI (the module the current request belongs to).<br>
 *
 * Procedural equivalent of <tt>new \rosasurfer\ministruts\url\VersionedUrl($uri)</tt>.
 *
 * @param  string $uri
 *
 * @return VersionedUrl
 */
function asset($uri) {
    return \rosasurfer\asset($uri);
}
