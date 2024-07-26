<?php
/**
 * If the {@link \rosasurfer\ministruts\Application} option "app.globals" is set definitions in namespace "\rosasurfer" are additionally mapped
 * to the global namespace.
 */
use rosasurfer\ministruts\Application;
use rosasurfer\ministruts\console\docopt\DocoptResult;
use rosasurfer\ministruts\struts\ActionMapping;
use rosasurfer\ministruts\struts\Module;
use rosasurfer\ministruts\struts\url\Url;
use rosasurfer\ministruts\struts\url\VersionedUrl;


// runtime environment
const CLI               = \rosasurfer\ministruts\CLI;
const LOCALHOST         = \rosasurfer\ministruts\LOCALHOST;
const MACOS             = \rosasurfer\ministruts\MACOS;
const WINDOWS           = \rosasurfer\ministruts\WINDOWS;
const NUL_DEVICE        = \rosasurfer\ministruts\NUL_DEVICE;            // the system's NUL device name

// custom log level
const L_DEBUG           = \rosasurfer\ministruts\L_DEBUG;
const L_INFO            = \rosasurfer\ministruts\L_INFO;
const L_NOTICE          = \rosasurfer\ministruts\L_NOTICE;
const L_WARN            = \rosasurfer\ministruts\L_WARN;
const L_ERROR           = \rosasurfer\ministruts\L_ERROR;
const L_FATAL           = \rosasurfer\ministruts\L_FATAL;

// log destinations for the built-in function error_log()
const ERROR_LOG_DEFAULT = \rosasurfer\ministruts\ERROR_LOG_DEFAULT;
const ERROR_LOG_MAIL    = \rosasurfer\ministruts\ERROR_LOG_MAIL;
const ERROR_LOG_DEBUG   = \rosasurfer\ministruts\ERROR_LOG_DEBUG;
const ERROR_LOG_FILE    = \rosasurfer\ministruts\ERROR_LOG_FILE;
const ERROR_LOG_SAPI    = \rosasurfer\ministruts\ERROR_LOG_SAPI;

// time periods
const SECOND            = \rosasurfer\ministruts\SECOND; const SECONDS = SECOND;
const MINUTE            = \rosasurfer\ministruts\MINUTE; const MINUTES = MINUTE;
const HOUR              = \rosasurfer\ministruts\HOUR;   const HOURS   = HOUR;
const DAY               = \rosasurfer\ministruts\DAY;    const DAYS    = DAY;
const WEEK              = \rosasurfer\ministruts\WEEK;   const WEEKS   = WEEK;
const MONTH             = \rosasurfer\ministruts\MONTH;  const MONTHS  = MONTH;
const YEAR              = \rosasurfer\ministruts\YEAR;   const YEARS   = YEAR;

// weekdays
const SUNDAY            = \rosasurfer\ministruts\SUNDAY;
const MONDAY            = \rosasurfer\ministruts\MONDAY;
const TUESDAY           = \rosasurfer\ministruts\TUESDAY;
const WEDNESDAY         = \rosasurfer\ministruts\WEDNESDAY;
const THURSDAY          = \rosasurfer\ministruts\THURSDAY;
const FRIDAY            = \rosasurfer\ministruts\FRIDAY;
const SATURDAY          = \rosasurfer\ministruts\SATURDAY;

// byte sizes
const KB                = \rosasurfer\ministruts\KB;
const MB                = \rosasurfer\ministruts\MB;
const GB                = \rosasurfer\ministruts\GB;

// array indexing types
const ARRAY_ASSOC       = \rosasurfer\ministruts\ARRAY_ASSOC;
const ARRAY_NUM         = \rosasurfer\ministruts\ARRAY_NUM;
const ARRAY_BOTH        = \rosasurfer\ministruts\ARRAY_BOTH;

// class member access levels
const ACCESS_PUBLIC     = \rosasurfer\ministruts\ACCESS_PUBLIC;
const ACCESS_PROTECTED  = \rosasurfer\ministruts\ACCESS_PROTECTED;
const ACCESS_PRIVATE    = \rosasurfer\ministruts\ACCESS_PRIVATE;
const ACCESS_ALL        = \rosasurfer\ministruts\ACCESS_ALL;

// miscellaneous
const NL                = \rosasurfer\ministruts\NL;                    // = EOL_UNIX
const EOL_MAC           = \rosasurfer\ministruts\EOL_MAC;               // "\r"       CR       0D       13
const EOL_NETSCAPE      = \rosasurfer\ministruts\EOL_NETSCAPE;          // "\r\r\n"   CRCRLF   0D0D0A   13,13,10
const EOL_UNIX          = \rosasurfer\ministruts\EOL_UNIX;              // "\n"       LF       0A       10
const EOL_WINDOWS       = \rosasurfer\ministruts\EOL_WINDOWS;           // "\r\n"     CRLF     0D0A     13,10


/**
 * Return the first element of an array-like variable without affecting the internal array pointer.
 *
 * @param  array|\Traversable $values
 *
 * @return mixed - the first element or NULL if the array-like variable is empty
 */
function first($values) {
    return \rosasurfer\ministruts\first($values);
}


/**
 * Return the first key of an array-like variable without affecting the internal array pointer.
 *
 * @param  array|\Traversable $values
 *
 * @return mixed - the first key or NULL if the array-like variable is empty
 */
function firstKey($values) {
    return \rosasurfer\ministruts\firstKey($values);
}


/**
 * Return the last element of an array-like variable without affecting the internal array pointer.
 *
 * @param  array|\Traversable $values
 *
 * @return mixed - the last element or NULL if the array-like variable is empty
 */
function last($values) {
    return \rosasurfer\ministruts\last($values);
}


/**
 * Return the last key of an array-like variable without affecting the internal array pointer.
 *
 * @param  array|\Traversable $values
 *
 * @return mixed - the last key or NULL if the array-like variable is empty
 */
function lastKey($values) {
    return \rosasurfer\ministruts\lastKey($values);
}


/**
 * Convert a value to a boolean and return the human-readable string "true" or "false".
 *
 * @param  mixed $value - value interpreted as a boolean
 *
 * @return string
 */
function boolToStr($value) {
    return \rosasurfer\ministruts\boolToStr($value);
}


/**
 * Print a message to STDOUT.
 *
 * @param  string $message
 */
function stdout($message) {
    \rosasurfer\ministruts\stdout($message);
}


/**
 * Print a message to STDERR.
 *
 * @param  string $message
 */
function stderr($message) {
    \rosasurfer\ministruts\stderr($message);
}


/**
 * Send an "X-Debug-{id}" header with a message. Each sent header will have a different and increasing id.
 *
 * @param  mixed $message
 */
function debugHeader($message) {
    \rosasurfer\ministruts\debugHeader($message);
}


/**
 * Dumps a variable to the screen or into a string.
 *
 * @param  mixed $var                     - variable
 * @param  bool  $return       [optional] - TRUE,  if the variable is to be dumped into a string <br>
 *                                          FALSE, if the variable is to be dumped to the standard output device (default)
 * @param  bool  $flushBuffers [optional] - whether to flush output buffers on output (default: TRUE)
 *
 * @return ?string - string if the result is to be returned, NULL otherwise
 */
function dump($var, $return=false, $flushBuffers=true) {
    return \rosasurfer\ministruts\dump($var, $return, $flushBuffers);
}


/**
 * Alias of print_p($var, false, $flushBuffers)
 *
 * Outputs a variable in a formatted and pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var
 * @param  bool  $flushBuffers [optional] - whether to flush output buffers (default: yes)
 *
 * @return bool - always TRUE
 */
function echof($var, $flushBuffers = true) {
    return \rosasurfer\ministruts\echof($var, $flushBuffers);
}


/**
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var                     - variable
 * @param  bool  $return       [optional] - TRUE,  if the result is to be returned as a string <br>
 *                                          FALSE, if the result is to be printed to the screen (default)
 * @param  bool  $flushBuffers [optional] - whether to flush output buffers on output (default: TRUE)
 *
 * @return ?string - string if the result is to be returned, NULL otherwise
 */
function print_p($var, $return=false, $flushBuffers=true) {
    return \rosasurfer\ministruts\print_p($var, $return, $flushBuffers);
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
    return \rosasurfer\ministruts\prettyBytes($value, $decimals);
}


/**
 * Convert a byte value to an integer supporting "php.ini" shorthand notation ("K", "M", "G").
 *
 * @param  string|int $value - byte value
 *
 * @return int - converted byte value
 */
function php_byte_value($value) {
    return \rosasurfer\ministruts\php_byte_value($value);
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
    return \rosasurfer\ministruts\numf($number, $decimals, $decimalSeparator, $thousandsSeparator);
}


/**
 * Return the value of a "php.ini" option as a boolean.
 *
 * NOTE: Don't use ini_get() to read boolean "php.ini" values as it will return the plain string as passed to ini_set().
 *
 * @param  string $option            - option name
 * @param  bool   $strict [optional] - whether to enable strict checking of the found value:
 *                                     TRUE:  invalid values cause a runtime exception (default)
 *                                     FALSE: invalid values are converted to a boolean
 *
 * @return ?bool - boolean value or NULL if the setting doesn't exist
 */
function ini_get_bool($option, $strict = true) {
    return \rosasurfer\ministruts\ini_get_bool($option, $strict);
}


/**
 * Return the value of a "php.ini" option as an integer.
 *
 * NOTE: Don't use ini_get() to read "php.ini" integer values as it will return the plain string as passed to ini_set().
 *
 * @param  string $option            - option name
 * @param  bool   $strict [optional] - whether to enable strict checking of the found value:
 *                                     TRUE:  invalid values cause a runtime exception (default)
 *                                     FALSE: invalid values are converted to an integer
 *
 * @return ?int - integer value or NULL if the setting doesn't exist
 */
function ini_get_int($option, $strict = true) {
    return \rosasurfer\ministruts\ini_get_int($option, $strict);
}


/**
 * Return the value of a "php.ini" option as a byte value supporting PHP shorthand notation ("K", "M", "G").
 *
 * NOTE: Don't use ini_get() to read "php.ini" byte values as it will return the plain string as passed to ini_set().
 *
 * @param  string $option            - option name
 * @param  bool   $strict [optional] - whether to enable strict checking of the found value:
 *                                     TRUE:  invalid values cause a runtime exception (default)
 *                                     FALSE: invalid values are converted to an integer
 *
 * @return ?int - integer value or NULL if the setting doesn't exist
 */
function ini_get_bytes($option, $strict = true) {
    return \rosasurfer\ministruts\ini_get_bytes($option, $strict);
}


/**
 * Convert special characters to HTML entities.
 *
 * Inline replacement and shortcut for htmlspecialchars() using different default flags.
 *
 * @param  string $string
 * @param  int    $flags        [optional] - default: ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5
 * @param  string $encoding     [optional] - default: 'UTF-8'
 * @param  bool   $doubleEncode [optional] - default: TRUE
 *
 * @return string - converted string
 *
 * @see   \htmlspecialchars()
 */
function hsc($string, $flags=null, $encoding=null, $doubleEncode=true) {
    return \rosasurfer\ministruts\hsc($string, $flags, $encoding, $doubleEncode);
}


/**
 * Whether the byte order of the machine we are running on is "little endian".
 *
 * @return bool
 */
function isLittleEndian() {
    return \rosasurfer\ministruts\isLittleEndian();
}


/**
 * Whether the specified path is relative or absolute, according to the current operating system.
 *
 * @param  string $path
 *
 * @return bool
 */
function isRelativePath($path) {
    return \rosasurfer\ministruts\isRelativePath($path);
}


/**
 * Functional replacement for ($stringA === $stringB).
 *
 * @param  ?string $stringA
 * @param  ?string $stringB
 * @param  bool    $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strCompare($stringA, $stringB, $ignoreCase = false) {
    return \rosasurfer\ministruts\strCompare($stringA, $stringB, $ignoreCase);
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
    return \rosasurfer\ministruts\strCompareI($stringA, $stringB);
}


/**
 * Whether a string contains a substring.
 *
 * @param  string $haystack
 * @param  string $needle
 * @param  bool   $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strContains($haystack, $needle, $ignoreCase = false) {
    return \rosasurfer\ministruts\strContains($haystack, $needle, $ignoreCase);
}


/**
 * Whether a string contains a substring ignoring upper/lower case differences.
 *
 * @param  string $haystack
 * @param  string $needle
 *
 * @return bool
 */
function strContainsI($haystack, $needle) {
    return \rosasurfer\ministruts\strContainsI($haystack, $needle);
}


/**
 * Whether a string starts with a substring. If multiple prefixes are specified whether the string starts with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $prefix                - one or more prefixes
 * @param  bool            $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strStartsWith($string, $prefix, $ignoreCase = false) {
    return \rosasurfer\ministruts\strStartsWith($string, $prefix, $ignoreCase);
}


/**
 * Whether a string starts with a substring ignoring upper/lower case differences. If multiple prefixes are specified
 * whether the string starts with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $prefix - one or more prefixes
 *
 * @return bool
 */
function strStartsWithI($string, $prefix) {
    return \rosasurfer\ministruts\strStartsWithI($string, $prefix);
}


/**
 * Whether a string ends with a substring. If multiple suffixes are specified whether the string ends with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $suffix                - one or more suffixes
 * @param  bool            $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strEndsWith($string, $suffix, $ignoreCase = false) {
    return \rosasurfer\ministruts\strEndsWith($string, $suffix, $ignoreCase);
}


/**
 * Whether a string ends with a substring ignoring upper/lower case differences. If multiple suffixes are specified whether
 * the string ends with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $suffix - one or more suffixes
 *
 * @return bool
 */
function strEndsWithI($string, $suffix) {
    return \rosasurfer\ministruts\strEndsWithI($string, $suffix);
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
 *  strLeft('abcde',  2) => 'ab'
 *  strLeft('abcde', -1) => 'abcd'
 * </pre>
 */
function strLeft($string, $length) {
    return \rosasurfer\ministruts\strLeft($string, $length);
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
 * @param  bool   $includeLimiter [optional] - whether to include the limiting substring in the returned result
 *                                             (default: FALSE)
 * @param  string $onNotFound     [optional] - string to return if the specified occurrence of the limiter is not found
 *                                             (default: the initial string)
 *
 * @return string - left part of the initial string or the $onNotFound value
 *
 * @example
 * <pre>
 *  strLeftTo('abcde', 'd')      => 'abc'
 *  strLeftTo('abcde', 'x')      => 'abcde'   // limiter not found
 *  strLeftTo('abccc', 'c',   3) => 'abcc'
 *  strLeftTo('abccc', 'c',  -3) => 'ab'
 *  strLeftTo('abccc', 'c', -99) => 'abccc'   // number of occurrences doesn't exist
 * </pre>
 */
function strLeftTo($string, $limiter, $count=1, $includeLimiter=false, $onNotFound='') {
    return \rosasurfer\ministruts\strLeftTo(...func_get_args());
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
 *  strRight('abcde',  1) => 'e'
 *  strRight('abcde', -2) => 'cde'
 * </pre>
 */
function strRight($string, $length) {
    return \rosasurfer\ministruts\strRight($string, $length);
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
 * @param  bool   $includeLimiter [optional] - whether to include the limiting substring in the returned result
 *                                             (default: FALSE)
 * @param  string $onNotFound     [optional] - value to return if the specified occurrence of the limiting substring is not found
 *                                             (default: empty string)
 *
 * @return string - right part of the initial string or the $onNotFound value
 *
 * @example
 * <pre>
 *  strRightFrom('abc_abc', 'c')     => '_abc'
 *  strRightFrom('abcabc',  'x')     => ''             // limiter not found
 *  strRightFrom('abc_abc', 'a',  2) => 'bc'
 *  strRightFrom('abc_abc', 'b', -2) => 'c_abc'
 * </pre>
 */
function strRightFrom($string, $limiter, $count=1, $includeLimiter=false, $onNotFound='') {
    return \rosasurfer\ministruts\strRightFrom(...func_get_args());
}


/**
 * Whether a string is wrapped in single or double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsQuoted($value) {
    return \rosasurfer\ministruts\strIsQuoted($value);
}


/**
 * Whether a string is wrapped in single quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsSingleQuoted($value) {
    return \rosasurfer\ministruts\strIsSingleQuoted($value);
}


/**
 * Whether a string is wrapped in double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDoubleQuoted($value) {
    return \rosasurfer\ministruts\strIsDoubleQuoted($value);
}


/**
 * Whether a string consists only of digits (0-9).
 *
 * @param  scalar $value
 *
 * @return bool
 */
function strIsDigits($value) {
    return \rosasurfer\ministruts\strIsDigits($value);
}


/**
 * Whether a string represents a valid integer value, i.e. consists of only digits and optionally a leading "-" (minus) character.
 *
 * @param  scalar $value
 *
 * @return bool
 */
function strIsInteger($value) {
    return \rosasurfer\ministruts\strIsInteger($value);
}


/**
 * Whether a string consists only of numerical characters and represents a valid numerical value. Opposite to the
 * built-in PHP function is_numeric() this function returns FALSE if the string begins with non-numerical characters
 * (e.g. white space).
 *
 * @param  scalar $value
 *
 * @return bool
 */
function strIsNumeric($value) {
    return \rosasurfer\ministruts\strIsNumeric($value);
}


/**
 * Convert a boolean representation to a boolean.
 *
 * @param  mixed $value - boolean representation
 *
 * @return ?bool - Boolean or NULL if the parameter doesn't represent a boolean. The accepted values of a boolean's
 *                 numerical string representation (integer or float) are 0 (zero) and 1 (one).
 */
function strToBool($value) {
    return \rosasurfer\ministruts\strToBool($value);
}


/**
 * Reduce multiple consecutive white space characters in a string to a single one.
 *
 * @param  string $string               - string to process
 * @param  bool   $joinLines [optional] - whether to return a single line result (default: yes)
 * @param  string $separator [optional] - the separator to use for joining (default: space " ")
 *
 * @return string
 */
function strCollapseWhiteSpace($string, $joinLines=true, $separator=' ') {
    return \rosasurfer\ministruts\strCollapseWhiteSpace($string, $joinLines, $separator);
}


/**
 * Normalize line endings of a string. If the string contains mixed line endings the number of lines of the original and the
 * resulting string may differ. Netscape line endings are honored only if all line endings are Netscape format (no mixed mode).
 *
 * @param  string $string          - string to normalize
 * @param  string $mode [optional] - format of the resulting string, can be one of:                             <br>
 *                                   EOL_MAC:      line endings are converted to Mac format      "\r"           <br>
 *                                   EOL_NETSCAPE: line endings are converted to Netscape format "\r\r\n"       <br>
 *                                   EOL_UNIX:     line endings are converted to Unix format     "\n" (default) <br>
 *                                   EOL_WINDOWS:  line endings are converted to Windows format  "\r\n"         <br>
 * @return string
 */
function normalizeEOL($string, $mode = EOL_UNIX) {
    return \rosasurfer\ministruts\normalizeEOL($string, $mode);
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
    return \rosasurfer\ministruts\objectToArray($object, $access);
}


/**
 * Alias of gettype() for C/C++ enthusiasts.
 *
 * @param  mixed $var
 *
 * @return string
 */
function typeOf($var) {
    return \rosasurfer\ministruts\typeOf($var);
}


/**
 * Whether a directory is considered empty.
 *
 * (a directory with just '.svn' or '.git' is empty)
 *
 * @param  string          $dirname
 * @param  string|string[] $ignore - one or more directory entries to intentionally ignore during the check, e.g. ".git"
 *                                   (default: none)
 * @return bool
 */
function is_dir_empty($dirname, $ignore = []) {
    return \rosasurfer\ministruts\is_dir_empty($dirname, $ignore);
}


/**
 * Manually load the specified class, interface or trait. If the component was already loaded the call does nothing.
 *
 * @param  string $name - name
 *
 * @return ?string - the same name or NULL if a component of that name doesn't exist or couldn't be loaded
 */
function autoload($name) {
    return \rosasurfer\ministruts\autoload($name);
}


/**
 * Whether the specified class exists (loaded or not) and is not an interface or a trait. Identical to
 * <pre>class_exists($name, true)</pre> except it also returnes FALSE if auto loading triggers an exception.
 *
 * @param  string $name - class name
 *
 * @return bool
 */
function is_class($name) {
    return \rosasurfer\ministruts\is_class($name);
}


/**
 * Whether the specified interface exists (loaded or not) and is not a class or a trait. Identical to
 * <pre>interface_exists($name, true)</pre> except it also returnes FALSE if auto loading triggers an exception.
 *
 * @param  string $name - interface name
 *
 * @return bool
 */
function is_interface($name) {
    return \rosasurfer\ministruts\is_interface($name);
}


/**
 * Whether the specified trait exists (loaded or not) and is not a class or an interface. Identical to
 * <pre>trait_exists($name, true)</pre> except it also returnes FALSE if auto loading triggers an exception.
 *
 * @param  string $name - trait name
 *
 * @return bool
 */
function is_trait($name) {
    return \rosasurfer\ministruts\is_trait($name);
}


/**
 * Whether a variable can be used like an array.
 *
 * Complement for PHP's <tt>is_array()</tt> function adding support for {@link \ArrayAccess} parameters.
 *
 * @param  ?mixed $var
 *
 * @return bool
 */
function is_array_like($var) {
    return \rosasurfer\ministruts\is_array_like($var);
}


/**
 * Return the simple name of a class name (i.e. the base name).
 *
 * @param  string|object $class - class name or instance
 *
 * @return string
 */
function simpleClassName($class) {
    return \rosasurfer\ministruts\simpleClassName($class);
}


/**
 * Return one of the metatypes "class", "interface" or "trait" for an object type identifier.
 *
 * @param  string $name - name
 *
 * @return string metatype
 */
function metatypeOf($name) {
    return \rosasurfer\ministruts\metatypeOf($name);
}


/**
 * Procedural replacement for rosasurfer\ministruts\util\Validator::isDateTime()
 *
 * Whether the specified string value represents a valid date or datetime value.
 *
 * @param  string          $string            - string value
 * @param  string|string[] $format [optional] - A valid date/datetime format. If multiple values are supplied whether the
 *                                              specified string fits at least one of them.  <br>
 *                                              Supported format strings: 'Y-m-d [H:i[:s]]'  <br>
 *                                                                        'Y.m.d [H:i[:s]]'  <br>
 *                                                                        'd.m.Y [H:i[:s]]'  <br>
 *                                                                        'd/m/Y [H:i[:s]]'  <br>
 *
 * @return int|bool - timestamp matching the string or FALSE if the string is not a valid date/datetime value
 *
 * @see    rosasurfer\ministruts\util\Validator::isDateTime()
 */
function is_datetime($string, $format = 'Y-m-d') {
    return \rosasurfer\ministruts\is_datetime($string, $format);
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
    return \rosasurfer\ministruts\ifTrue($value, $altValue);
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
    return \rosasurfer\ministruts\ifFalse($value, $altValue);
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
    return \rosasurfer\ministruts\ifNull($value, $altValue);
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
    return \rosasurfer\ministruts\ifEmpty($value, $altValue);
}


/**
 * Return the host name of the internet host specified by a given IP address.
 *
 * @param  string $ipAddress - the host IP address
 *
 * @return string - the host name on success, or the unmodified IP address on resolver error
 */
function getHostByAddress($ipAddress) {
    return \rosasurfer\ministruts\getHostByAddress($ipAddress);
}


/**
 * Return a sorted copy of the specified array using the algorythm and parameters of {@link \ksort()}.
 * Opposite to ksort() this function will not modify the passed array.
 *
 * @param  array $values
 * @param  int   $sort_flags [optional]
 *
 * @return array
 */
function ksortc(array $values, $sort_flags = SORT_REGULAR) {
    return \rosasurfer\ministruts\ksortc($values, $sort_flags);
}


/**
 * Return a pluralized string according to the specified number of items.
 *
 * @param  int    $count               - the number of items to determine the output from
 * @param  string $singular [optional] - singular form of string (default: empty string)
 * @param  string $plural   [optional] - plural form of string (default: "s")
 *
 * @return string
 */
function pluralize($count, $singular='', $plural='s') {
    return \rosasurfer\ministruts\pluralize($count, $singular, $plural);
}


/**
 * Execute a task in a synchronized way. Emulates the Java keyword "synchronized".
 *
 * @param  \Closure $task             - task to execute (an anonymous function is implicitly casted)
 * @param  string   $mutex [optional] - mutex identifier (default: the calling line of code)
 */
function synchronized(\Closure $task, $mutex = null) {
    \rosasurfer\ministruts\synchronized($task, $mutex);
}


/**
 * Lookup and return a {@link \rosasurfer\ministruts\struts\url\Url} helper for the named {@link \rosasurfer\ministruts\struts\ActionMapping}.
 *
 * @param  string $name - route name
 *
 * @return Url
 */
function route($name) {
    return \rosasurfer\ministruts\route($name);
}


/**
 * Return a {@link \rosasurfer\ministruts\struts\url\Url} helper for the given URI. An URI starting with a slash "/" is interpreted
 * as relative to the application's base URI. An URI not starting with a slash is interpreted as relative to the application
 * {@link \rosasurfer\ministruts\struts\Module}'s base URI (the module the current request belongs to).<br>
 *
 * Procedural equivalent of <tt>new \rosasurfer\ministruts\struts\url\Url($uri)</tt>.
 *
 * @param  string $uri
 *
 * @return Url
 */
function url($uri) {
    return \rosasurfer\ministruts\url($uri);
}


/**
 * Return a version-aware URL helper for the given URI {@link VersionedUrl}. An URI starting with a slash "/" is interpreted
 * as relative to the application's base URI. An URI not starting with a slash is interpreted as relative to the application
 * {@link \rosasurfer\ministruts\struts\Module}'s base URI (the module the current request belongs to).<br>
 *
 * Procedural equivalent of <tt>new \rosasurfer\ministruts\struts\url\VersionedUrl($uri)</tt>.
 *
 * @param  string $uri
 *
 * @return VersionedUrl
 */
function asset($uri) {
    return \rosasurfer\ministruts\asset($uri);
}


/**
 * Parse command line arguments and match them against the specified {@link https://docopt.org/#} syntax definition.
 *
 * @param  string               $doc                - help text, i.e. a syntax definition in Docopt language format
 * @param  string|string[]|null $args    [optional] - arguments to parse (default: the arguments passed in $_SERVER['argv'])
 * @param  array                $options [optional] - parser options (default: none)
 *
 * @return DocoptResult - the parsing result
 */
function docopt($doc, $args=null, array $options=[]) {
    return \rosasurfer\ministruts\docopt($doc, $args, $options);
}
