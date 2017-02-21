<?php
/**
 * Map the helper constants and functions in namespace \rosasurfer to the global namespace.
 */
use rosasurfer\ministruts\url\Url;
use rosasurfer\ministruts\url\VersionedUrl;


// block re-includes
if (defined('CLI')) return;


// runtime environment
const CLI               = \rosasurfer\CLI;
const LOCALHOST         = \rosasurfer\LOCALHOST;
const WINDOWS           = \rosasurfer\WINDOWS;

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

// php.ini changable modes
const PHP_INI_ALL       = \rosasurfer\PHP_INI_ALL;
const PHP_INI_USER      = \rosasurfer\PHP_INI_USER;
const PHP_INI_ONLY      = \rosasurfer\PHP_INI_ONLY;
const PHP_INI_SYSTEM    = \rosasurfer\PHP_INI_SYSTEM;
const PHP_INI_PERDIR    = \rosasurfer\PHP_INI_PERDIR;

// PHP types
const PHP_TYPE_BOOL     = \rosasurfer\PHP_TYPE_BOOL;
const PHP_TYPE_INT      = \rosasurfer\PHP_TYPE_INT;
const PHP_TYPE_FLOAT    = \rosasurfer\PHP_TYPE_FLOAT;
const PHP_TYPE_STRING   = \rosasurfer\PHP_TYPE_STRING;
const PHP_TYPE_ARRAY    = \rosasurfer\PHP_TYPE_ARRAY;

// miscellaneous
const NL                = \rosasurfer\NL;                      // = EOL_UNIX
const EOL_MAC           = \rosasurfer\EOL_MAC;                 // "\r"       CR       0D       13
const EOL_NETSCAPE      = \rosasurfer\EOL_NETSCAPE;            // "\r\r\n"   CRCRLF   0D0D0A   13,13,10
const EOL_UNIX          = \rosasurfer\EOL_UNIX;                // "\n"       LF       0A       10
const EOL_WINDOWS       = \rosasurfer\EOL_WINDOWS;             // "\r\n"     CRLF     0D0A     13,10


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
   return \rosasurfer\dump($var, $return, $flushBuffers);
}


/**
 * Functional replacement for "echo($var)" which is a language construct and can't be used as a regular function.
 *
 * @param  mixed $var
 * @param  bool  $flushBuffers - whether or not to flush output buffers (default: TRUE)
 */
function echof($var, $flushBuffers=true) {
   \rosasurfer\echof($var, $flushBuffers);
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
   \rosasurfer\echoPre($var, $flushBuffers);
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
 * @param  mixed $var          - variable
 * @param  bool  $return       - TRUE,  if the result is to be returned as a string;
 *                               FALSE, if the result is to be printed to STDOUT (default)
 * @param  bool  $flushBuffers - whether or not to flush output buffers on output (default: TRUE)
 *
 * @return string|null - string if the result is to be returned, NULL otherwise
 */
function printPretty($var, $return=false, $flushBuffers=true) {
   return \rosasurfer\printPretty($var, $return, $flushBuffers);
}


/**
 * Pretty printer for byte values.
 *
 * @param  int $value - byte value
 *
 * @return string - formatted byte value
 */
function prettyBytes($value) {
   return \rosasurfer\prettyBytes($value);
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
 * Functional replacement for ($stringA === $stringB).
 *
 * @param  string $stringA
 * @param  string $stringB
 * @param  bool   $ignoreCase - default: no
 *
 * @return bool
 */
function strCompare($stringA, $stringB, $ignoreCase=false) {
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
 * @param  bool   $ignoreCase - default: no
 *
 * @return bool
 */
function strContains($haystack, $needle, $ignoreCase=false) {
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
 * @param  string|string[] $prefix     - one or more prefixes
 * @param  bool            $ignoreCase - default: no
 *
 * @return bool
 */
function strStartsWith($string, $prefix, $ignoreCase=false) {
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
 * @param  string|string[] $suffix     - one or more suffixes
 * @param  bool            $ignoreCase - default: no
 *
 * @return bool
 */
function strEndsWith($string, $suffix, $ignoreCase=false) {
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
   return \rosasurfer\strLeft($string, $length);
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
   return \rosasurfer\strLeftTo(...func_get_args());
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
   return \rosasurfer\strRight($string, $length);
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
 * @param  string $value
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
   return \rosasurfer\normalizeEOL($string, $mode);
}


/**
 * Alias for getType() for C/C++ enthusiasts.
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
 * @param  string $path - same as mkDir(): directory name
 * @param  int    $mode - same as mkDir(): permission mode to set if the directory is created
 *                                         (default: 0770 = rwxrwx---)
 */
function mkDirWritable($path, $mode=0770) {
   return \rosasurfer\mkDirWritable($path, $mode);
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
   return \rosasurfer\is_datetime($string, $format);
}


/**
 * Functional equivalent of the value TRUE.
 *
 * @param  mixed $value - ignored
 *
 * @return bool - TRUE
 */
function _true($value=null) {
   return \rosasurfer\_true($value);
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
   return \rosasurfer\ifTrue($value, $altValue);
}


/**
 * Functional equivalent of the value FALSE.
 *
 * @param  mixed $value - ignored
 *
 * @return bool - FALSE
 */
function _false($value=null) {
   return \rosasurfer\_false($value);
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
   return \rosasurfer\ifFalse($value, $altValue);
}


/**
 * Functional equivalent of the value NULL.
 *
 * @param  mixed $value - ignored
 *
 * @return NULL
 */
function _null($value=null) {
   return \rosasurfer\_null($value);
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
 * @param  int   $sort_flags
 *
 * @return array
 *
 * @see    ksort()
 */
function ksort_r(array $values, $sort_flags=SORT_REGULAR) {
   return \rosasurfer\ksort_r($values, $sort_flags);
}


/**
 * Return a pluralized message according to the specified number of items.
 *
 * @param  int    $count    - the number of items to determine the message form from
 * @param  string $singular - singular form of message
 * @param  string $plural   - plural form of message
 *
 * @return string
 */
function pluralize($count, $singular='', $plural='s') {
   return \rosasurfer\pluralize($count, $singular, $plural);
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
   return \rosasurfer\url($uri);
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
   return \rosasurfer\versionedUrl($uri);
}
