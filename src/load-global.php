<?php
/**
 * block framework re-includes
 */
if (defined('rosasurfer\MINISTRUTS_ROOT')) return;


/**
 * check PHP version
 */
if (PHP_VERSION < '5.6') {
   echo 'application error'.PHP_EOL;
   exit(255|error_log('Error: A PHP version >= 5.6 is required (found version '.PHP_VERSION.').'));
}


/**
 * require namespaced loader
 */
require(__DIR__.'/load.php');


/**
 * define namespaced helper constants and functions globally
 */
define('CLI'      , rosasurfer\CLI      );                                    // whether or not we run on command line interface
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
define('ERROR_LOG_SYSLOG', rosasurfer\ERROR_LOG_SYSLOG);                      // message is sent to PHP's sy stem logger
define('ERROR_LOG_MAIL'  , rosasurfer\ERROR_LOG_MAIL  );                      // message is sent by email
define('ERROR_LOG_DEBUG' , rosasurfer\ERROR_LOG_DEBUG );                      // message is sent through the PHP debugging connection
define('ERROR_LOG_FILE'  , rosasurfer\ERROR_LOG_FILE  );                      // message is appended to a file destination
define('ERROR_LOG_SAPI'  , rosasurfer\ERROR_LOG_SAPI  );                      // message is sent directly to the SAPI logging handler

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
define('EOL', rosasurfer\EOL);
define('NL' , rosasurfer\NL );


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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Functional replacement for "echo($var)" which is a language construct and can't be used as a regular function.
 *
 * @param  mixed $var
 */
function echof($var) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Pretty printer for byte values.
 *
 * @param  int $value - byte value
 *
 * @return string - formatted byte value
 */
function prettyBytes($value) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Whether or not the byte order of the machine we are running on is "little endian".
 *
 * @return bool
 */
function isLittleEndian() {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Whether or not a string is wrapped in single or double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsQuoted($value) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Whether or not a string is wrapped in single quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsSingleQuoted($value) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Whether or not a string is wrapped in double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDoubleQuoted($value) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Whether or not a string consists of numerical characters (0-9).
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDigits($value) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Check whether a directory exists. If not try to create it. Check further if write permission is granted.
 *
 * @param  string $path - same as mkDir(): directory name
 * @param  int    $mode - same as mkDir(): permission mode to set if the directory is created
 *                                         (default: 0770 = rwxrwx---)
 */
function mkDirWritable($path, $mode=0770) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Procedural replacement for the value TRUE for quick code analysis.
 *
 * @param  mixed $value - ignored
 *
 * @return TRUE
 */
function _true($value=null) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Procedural replacement for the value FALSE for quick code analysis.
 *
 * @param  mixed $value - ignored
 *
 * @return FALSE
 */
function _false($value=null) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}


/**
 * Procedural replacement for the value NULL for quick code analysis.
 *
 * @param  mixed $value - ignored
 *
 * @return NULL
 */
function _null($value=null) {
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
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
   return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
}
