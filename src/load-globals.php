<?php
/**
 * block framework re-includes
 */
if (defined('rosasurfer\MINISTRUTS_ROOT')) return;


/**
 * require namespaced loader
 */
require(__DIR__.'/load.php');


/**
 * define namespaced helper constants and functions globally
 */
define('CLI'      , rosasurfer\CLI      );                                    // whether or not we run on a command line interface
define('LOCALHOST', rosasurfer\LOCALHOST);                                    // whether or not we run on localhost
define('WINDOWS'  , rosasurfer\WINDOWS  );                                    // whether or not we run on Windows

// custom log level
define('L_DEBUG' , rosasurfer\L_DEBUG );
define('L_INFO'  , rosasurfer\L_INFO  );
define('L_NOTICE', rosasurfer\L_NOTICE);
define('L_WARN'  , rosasurfer\L_WARN  );
define('L_ERROR' , rosasurfer\L_ERROR );
define('L_FATAL' , rosasurfer\L_FATAL );

// log destinations for the built-in function error_log()
define('ERROR_LOG_DEFAULT', rosasurfer\ERROR_LOG_DEFAULT);                    // message is sent to the configured log or the system logger
define('ERROR_LOG_MAIL'   , rosasurfer\ERROR_LOG_MAIL   );                    // message is sent by email
define('ERROR_LOG_DEBUG'  , rosasurfer\ERROR_LOG_DEBUG  );                    // message is sent through the PHP debugging connection
define('ERROR_LOG_FILE'   , rosasurfer\ERROR_LOG_FILE   );                    // message is appended to a file destination
define('ERROR_LOG_SAPI'   , rosasurfer\ERROR_LOG_SAPI   );                    // message is sent directly to the SAPI logging handler

// time periods
define('SECOND', rosasurfer\SECOND); define('SECONDS', rosasurfer\SECONDS);
define('MINUTE', rosasurfer\MINUTE); define('MINUTES', rosasurfer\MINUTES);
define('HOUR'  , rosasurfer\HOUR  ); define('HOURS'  , rosasurfer\HOURS  );
define('DAY'   , rosasurfer\DAY   ); define('DAYS'   , rosasurfer\DAYS   );
define('WEEK'  , rosasurfer\WEEK  ); define('WEEKS'  , rosasurfer\WEEKS  );
define('MONTH' , rosasurfer\MONTH ); define('MONTHS' , rosasurfer\MONTHS );   // fuzzy but garantied to cover any month
define('YEAR'  , rosasurfer\YEAR  ); define('YEARS'  , rosasurfer\YEARS  );   // fuzzy but garantied to cover any year

// weekdays
define('SUNDAY'   , rosasurfer\SUNDAY   );
define('MONDAY'   , rosasurfer\MONDAY   );
define('TUESDAY'  , rosasurfer\TUESDAY  );
define('WEDNESDAY', rosasurfer\WEDNESDAY);
define('THURSDAY' , rosasurfer\THURSDAY );
define('FRIDAY'   , rosasurfer\FRIDAY   );
define('SATURDAY' , rosasurfer\SATURDAY );

// miscellaneous
define('NL', rosasurfer\NL);


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
   return \rosasurfer\dump(...func_get_args());
}


/**
 * Functional replacement for "echo($var)" which is a language construct and can't be used as a regular function.
 *
 * @param  mixed $var
 */
function echof($var) {
   return \rosasurfer\echof(...func_get_args());
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
   return \rosasurfer\echoPre(...func_get_args());
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
   return \rosasurfer\pp(...func_get_args());
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
   return \rosasurfer\printPretty(...func_get_args());
}


/**
 * Pretty printer for byte values.
 *
 * @param  int $value - byte value
 *
 * @return string - formatted byte value
 */
function prettyBytes($value) {
   return \rosasurfer\prettyBytes(...func_get_args());
}


/**
 * Whether or not the byte order of the machine we are running on is "little endian".
 *
 * @return bool
 */
function isLittleEndian() {
   return \rosasurfer\isLittleEndian(...func_get_args());
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
   return \rosasurfer\strCompare(...func_get_args());
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
   return \rosasurfer\strCompareI(...func_get_args());
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
   return \rosasurfer\strContains(...func_get_args());
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
   return \rosasurfer\strContainsI(...func_get_args());
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
   return \rosasurfer\strStartsWith(...func_get_args());
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
   return \rosasurfer\strStartsWithI(...func_get_args());
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
   return \rosasurfer\strEndsWith(...func_get_args());
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
   return \rosasurfer\strEndsWithI(...func_get_args());
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
   return \rosasurfer\strLeft(...func_get_args());
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
   return \rosasurfer\strRight(...func_get_args());
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
   return \rosasurfer\strIsQuoted(...func_get_args());
}


/**
 * Whether or not a string is wrapped in single quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsSingleQuoted($value) {
   return \rosasurfer\strIsSingleQuoted(...func_get_args());
}


/**
 * Whether or not a string is wrapped in double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDoubleQuoted($value) {
   return \rosasurfer\strIsDoubleQuoted(...func_get_args());
}


/**
 * Whether or not a string consists of numerical characters (0-9).
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDigits($value) {
   return \rosasurfer\strIsDigits(...func_get_args());
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
   return \rosasurfer\typeOf(...func_get_args());
}


/**
 * Check whether a directory exists. If not try to create it. Check further if write permission is granted.
 *
 * @param  string $path - same as mkDir(): directory name
 * @param  int    $mode - same as mkDir(): permission mode to set if the directory is created
 *                                         (default: 0770 = rwxrwx---)
 */
function mkDirWritable($path, $mode=0770) {
   return \rosasurfer\mkDirWritable(...func_get_args());
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
   return \rosasurfer\is_class(...func_get_args());
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
   return \rosasurfer\is_interface(...func_get_args());
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
   return \rosasurfer\is_trait(...func_get_args());
}


/**
 * Return one of the metatypes "class", "interface" or "trait" for an object type identifier (a name).
 *
 * @param  string $name - name
 *
 * @return string metatype
 */
function metaTypeToStr($name) {
   return \rosasurfer\metaTypeToStr(...func_get_args());
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
   return \rosasurfer\is_datetime(...func_get_args());
}


/**
 * Functional equivalent of the value TRUE.
 *
 * @param  mixed $value - ignored
 *
 * @return TRUE
 */
function _true($value=null) {
   return \rosasurfer\_true(...func_get_args());
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
   return \rosasurfer\ifTrue(...func_get_args());
}


/**
 * Functional equivalent of the value FALSE.
 *
 * @param  mixed $value - ignored
 *
 * @return FALSE
 */
function _false($value=null) {
   return \rosasurfer\_false(...func_get_args());
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
   return \rosasurfer\ifFalse(...func_get_args());
}


/**
 * Functional equivalent of the value NULL.
 *
 * @param  mixed $value - ignored
 *
 * @return NULL
 */
function _null($value=null) {
   return \rosasurfer\_null(...func_get_args());
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
   return \rosasurfer\ifNull(...func_get_args());
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
   return \rosasurfer\ifEmpty(...func_get_args());
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
   return \rosasurfer\ksort_r(...func_get_args());
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
   return \rosasurfer\pluralize(...func_get_args());
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
   return \rosasurfer\url(...func_get_args());
}


/**
 * Return a new version-aware URL helper instance. Procedural replacement for
 * <tt>new \rosasurfer\ministruts\url\VersionedUrl(...)</tt>.
 *
 * @param  string $uri - URI part of the URL to generate
 *
 * @return VersionedUrl
 *
 * @see    Url
 */
function vUrl($uri) {
   return \rosasurfer\vUrl(...func_get_args());
}
