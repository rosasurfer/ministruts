<?php
/**
 * Helper functions and constants
 */
namespace rosasurfer;

use rosasurfer\console\docopt\DocoptParser;
use rosasurfer\console\docopt\DocoptResult;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\di\proxy\Request;
use rosasurfer\core\exception\InvalidValueException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\core\lock\Lock;
use rosasurfer\ministruts\ActionMapping;
use rosasurfer\ministruts\Module;
use rosasurfer\ministruts\url\Url;
use rosasurfer\ministruts\url\VersionedUrl;
use rosasurfer\util\Validator;


// Whether we run on a command line interface, on localhost and/or on Windows.
define('rosasurfer\_CLI',        defined('\STDIN') && is_resource(\STDIN));
define('rosasurfer\_LOCALHOST',  !_CLI && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', $_SERVER['SERVER_ADDR']]));
define('rosasurfer\_MACOS',      strtoupper(PHP_OS) == 'DARWIN');
define('rosasurfer\_WINDOWS',    defined('\PHP_WINDOWS_VERSION_BUILD'));
define('rosasurfer\_NUL_DEVICE', _WINDOWS ? 'nul' : '/dev/null');

/** @var bool    - whether we run on a command line interface */
const CLI        = _CLI;
/** @var bool    - whether we run on a webserver's localhost */
const LOCALHOST  = _LOCALHOST;
/** @var bool    - whether we run on MacOS */                       // constant declarations improve IDE code completion
const MACOS      = _MACOS;
/** @var bool    - whether we run on Windows */
const WINDOWS    = _WINDOWS;
/** @var string  - the system's NUL device name */
const NUL_DEVICE = _NUL_DEVICE;

// custom log level
const L_DEBUG           =  1;
const L_INFO            =  2;
const L_NOTICE          =  4;
const L_WARN            =  8;
const L_ERROR           = 16;
const L_FATAL           = 32;

// log destinations for the built-in function error_log()
const ERROR_LOG_DEFAULT =  0;                                       // message is sent to syslog, a file or the SAPI logger, depending on php.ini setting "error_log" (default)
const ERROR_LOG_MAIL    =  1;                                       // message is sent by e-mail
const ERROR_LOG_DEBUG   =  2;                                       // message is sent through the PHP debugging connection (no longer available)
const ERROR_LOG_FILE    =  3;                                       // message is appended to the file in parameter 'destination'
const ERROR_LOG_SAPI    =  4;                                       // message is sent directly to the SAPI logger (e.g. Apache or STDERR)

// time periods
const SECOND            =   1;           const SECONDS = SECOND;
const MINUTE            =  60 * SECONDS; const MINUTES = MINUTE;
const HOUR              =  60 * MINUTES; const HOURS   = HOUR;
const DAY               =  24 * HOURS;   const DAYS    = DAY;
const WEEK              =   7 * DAYS;    const WEEKS   = WEEK;
const MONTH             =  31 * DAYS;    const MONTHS  = MONTH;     // fuzzy but garantied to cover any month
const YEAR              = 366 * DAYS;    const YEARS   = YEAR;      // fuzzy but garantied to cover any year

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
const MB                = 1024 << 10;
const GB                = 1024 << 20;                               // not TB (doesn't fit in 32 bits)

// array indexing types
const ARRAY_ASSOC       = 1;
const ARRAY_NUM         = 2;
const ARRAY_BOTH        = 3;

// class member access levels
const ACCESS_PUBLIC     = 1;
const ACCESS_PROTECTED  = 2;
const ACCESS_PRIVATE    = 4;
const ACCESS_ALL        = ACCESS_PUBLIC | ACCESS_PROTECTED | ACCESS_PRIVATE;

// miscellaneous
const NL                = "\n";                                     // - ctrl --- hex --- dec ----
const EOL_MAC           = "\r";                                     //   CR       0D      13
const EOL_NETSCAPE      = "\r\r\n";                                 //   CRCRLF   0D0D0A  13,13,10
const EOL_UNIX          = "\n";                                     //   LF       0A      10
const EOL_WINDOWS       = "\r\n";                                   //   CRLF     0D0A    13,10

// global definitions
!defined('PHP_INT_MIN') && define('PHP_INT_MIN', ~PHP_INT_MAX);     // built-in since PHP 7.0

// php.ini access level
define('INI_ONLY',       0         );                               // undefined access level
define('PHP_INI_ONLY',   INI_ONLY  );       // 0    no flag         // entry can be set in php.ini only
define('PHP_INI_USER',   INI_USER  );       // 1    flag            // entry can be set in scripts and in .user.ini
define('PHP_INI_PERDIR', INI_PERDIR);       // 2    flag            // entry can be set in php.ini, httpd.conf, .htaccess and in .user.ini
define('PHP_INI_SYSTEM', INI_SYSTEM);       // 4    flag            // entry can be set in php.ini and in httpd.conf
define('PHP_INI_ALL',    INI_ALL   );       // 7    flag            // entry can be set anywhere


/**
 * Filters elements of an array-like variable using a callback function. Iterates over each value in the array passing it to
 * the callback function. If the function returns TRUE, the current value from the array is returned as part of the resulting
 * array. Array keys are preserved.
 *
 * Complement of PHP's <tt>array_filter()</tt> function adding support for {@link \Traversable} parameters.
 *
 * @param  array|\Traversable $input
 * @param  callable           $callback [optional]
 * @param  int                $flags    [optional]
 *
 * @return array
 */
function array_filter($input, $callback=null, $flags=0) {
    $args = func_get_args();
    if ($input instanceof \Traversable)
        $args[0] = iterator_to_array($input, $useKeys=true);
    return \array_filter(...$args);
}


/**
 * Whether the given index exists in an array-like variable.
 *
 * Complement of PHP's <tt>array_key_exists()</tt> function adding support for {@link \ArrayAccess} parameters.
 *
 * @param  mixed              $key
 * @param  array|\ArrayAccess $array
 *
 * @return bool
 */
function array_key_exists($key, $array) {
    if ($array instanceof \ArrayAccess)
        return $array->offsetExists($key);
    return \array_key_exists($key, $array);
}


/**
 * Alias of {@link rosasurfer\array_key_exists()}.
 *
 * Whether the given index exists in an array-like variable.
 *
 * Complement of PHP's <tt>key_exists()</tt> function adding support for {@link \ArrayAccess} parameters.
 *
 * @param  mixed              $key
 * @param  array|\ArrayAccess $array
 *
 * @return bool
 */
function key_exists($key, $array) {
    return array_key_exists($key, $array);
}


/**
 * Return all or a subset of the keys of an array-like variable.
 *
 * Complement of PHP's <tt>array_keys()</tt> function adding support for {@link \Traversable} parameters.
 *
 * @param  array|\Traversable $array
 * @param  mixed              $search [optional]
 * @param  bool               $strict [optional]
 *
 * @return array
 */
function array_keys($array, $search=null, $strict=false) {
    $args = func_get_args();
    if ($array instanceof \Traversable) {
        $args[0] = iterator_to_array($array, $useKeys=true);
    }
    return \array_keys(...$args);
}


/**
 * Merges the elements of one or more array-like variables together so that the values of one are appended to the end of the
 * previous one. Values with the same string keys will overwrite the previous one. Numeric keys will be renumbered and values
 * with the same numeric keys will not overwrite the previous one.
 *
 * Complement of PHP's <tt>array_merge()</tt> function adding support for {@link \Traversable} parameters.
 *
 * @param  array|\Traversable           $array1
 * @param  array<array|\Traversable> ...$arrays
 *
 * @return array
 */
function array_merge($array1, ...$arrays) {
    $args = func_get_args();
    foreach ($args as $key => $arg) {
        if ($arg instanceof \Traversable)
            $args[$key] = iterator_to_array($arg, $useKeys=true);
    }
    return \array_merge(...$args);
}


/**
 * Checks if a value exists in an array-like variable.
 *
 * Complement of PHP's <tt>in_array()</tt> function adding support for {@link \Traversable} parameters.
 *
 * @param  mixed              $needle
 * @param  array|\Traversable $haystack
 * @param  bool               $strict [optional]
 *
 * @return bool
 */
function in_array($needle, $haystack, $strict = false) {
    if ($haystack instanceof \Traversable)
        $haystack = iterator_to_array($haystack, $useKeys=false);
    return \in_array($needle, $haystack, $strict);
}


/**
 * Return the first element of an array-like variable without affecting the internal array pointer.
 *
 * @param  array|\Traversable $values
 *
 * @return mixed - the first element or NULL if the array-like variable is empty
 */
function first($values) {
    if ($values instanceof \Traversable)
        $values = iterator_to_array($values, $useKeys=false);
    return reset($values);
}


/**
 * Return the first key of an array-like variable without affecting the internal array pointer.
 *
 * @param  array|\Traversable $values
 *
 * @return mixed - the first key or NULL if the array-like variable is empty
 */
function firstKey($values) {
    if ($values instanceof \Traversable)
        $values = iterator_to_array($values);
    reset($values);
    return key($values);
}


/**
 * Return the last element of an array-like variable without affecting the internal array pointer.
 *
 * @param  array|\Traversable $values
 *
 * @return mixed - the last element or NULL if the array-like variable is empty
 */
function last($values) {
    if ($values instanceof \Traversable) {
        $values = iterator_to_array($values, $useKeys=false);
    }
    else Assert::isArray($values);

    return $values ? end($values) : null;
}


/**
 * Return the last key of an array-like variable without affecting the internal array pointer.
 *
 * @param  array|\Traversable $values
 *
 * @return mixed - the last key or NULL if the array-like variable is empty
 */
function lastKey($values) {
    if ($values instanceof \Traversable) {
        $values = iterator_to_array($values);
    }
    else Assert::isArray($values);

    if (!$values)
        return null;
    end($values);
    return key($values);
}


/**
 * Convert a value to a boolean and return the human-readable string "true" or "false".
 *
 * @param  mixed $value - value interpreted as a boolean
 *
 * @return string
 */
function boolToStr($value) {
    if (is_string($value)) {
        $value = trim(strtolower($value));
        switch ($value) {
            case 'true' :
            case 'on'   :
            case 'yes'  : return 'true';

            case 'false':
            case 'off'  :
            case 'no'   : return 'false';
        }
    }
    return $value ? 'true':'false';
}


/**
 * Print a message to STDOUT.
 *
 * @param  string $message
 */
function stdout($message) {
    Assert::string($message);

    $hStream = CLI ? \STDOUT : fopen('php://stdout', 'a');
    fwrite($hStream, $message);
    if (!CLI) fclose($hStream);
}


/**
 * Print a message to STDERR.
 *
 * @param  string $message
 */
function stderr($message) {
    Assert::string($message);

    $hStream = CLI ? \STDERR : fopen('php://stderr', 'a');
    fwrite($hStream, $message);
    if (!CLI) fclose($hStream);
}


/**
 * Send an "X-Debug-{id}" header with a message. Each sent header will have a different and increasing id.
 *
 * @param  string $message
 */
function debugHeader($message) {
    if (CLI) return;

    if (!is_string($message))
        $message = (string) $message;
    $message = str_replace(chr(0), '\0', $message);         // headers must not contain NUL bytes
    $message = normalizeEOL($message);
    $message = str_replace(NL, '\n ', $message);            // header() does not accept multi-line headers

    static $i = 0;
    header('X-Debug-'.++$i.': '.$message);
}


/**
 * Dumps a variable to the screen or into a string.
 *
 * @param  mixed $var                     - variable
 * @param  bool  $return       [optional] - TRUE,  if the variable is to be dumped into a string <br>
 *                                          FALSE, if the variable is to be dumped to the standard output device (default)
 * @param  bool  $flushBuffers [optional] - whether to flush output buffers on output (default: TRUE)
 *
 * @return string|null - string if the result is to be returned, NULL otherwise
 */
function dump($var, $return=false, $flushBuffers=true) {
    if ($return) ob_start();
    var_dump($var);
    if ($return) return ob_get_clean();

    $flushBuffers && ob_get_level() && ob_flush();
    return null;
}


/**
 * Alias of pp($var, false, $flushBuffers)
 *
 * Outputs a variable in a formatted and pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var
 * @param  bool  $flushBuffers [optional] - whether to flush output buffers (default: yes)
 */
function echof($var, $flushBuffers = true) {
    pp($var, false, $flushBuffers);
}


/**
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var                     - variable
 * @param  bool  $return       [optional] - TRUE,  if the result is to be returned as a string <br>
 *                                          FALSE, if the result is to be printed to the screen (default)
 * @param  bool  $flushBuffers [optional] - whether to flush output buffers on output (default: TRUE)
 *
 * @return string|null - string if the result is to be returned, NULL otherwise
 */
function pp($var, $return=false, $flushBuffers=true) {
    if (is_object($var) && method_exists($var, '__toString') && !$var instanceof \SimpleXMLElement) {
        $str = (string) $var;
    }
    elseif (is_object($var) || is_array($var)) {
        $str = print_r($var, true);
    }
    elseif ($var === null) {
        $str = '(null)';                            // analogous to typeof(null) = 'NULL'
    }
    elseif (is_bool($var)) {
        $str = ($var ? 'true':'false').' (bool)';
    }
    else {
        $str = (string) $var;
    }

    if (!CLI) {
        $str = '<div align="left"
                     style="display:initial; visibility:initial; clear:both;
                     position:relative; z-index:4294967295; top:initial; left:initial;
                     float:left; width:initial; height:initial;
                     margin:0; padding:0; border-width:0;
                     color:inherit; background-color:inherit">
                    <pre style="width:initial; height:initial; margin:0; padding:0; border-width:0;
                                color:inherit; background-color:inherit; white-space:pre; line-height:12px;
                                font:normal normal 12px/normal \'Courier New\',courier,serif">'.hsc($str).'</pre>
                </div>';
    }
    if (!strEndsWith($str, NL))
        $str .= NL;

    if ($return)
        return $str;

    echo $str;
    $flushBuffers && ob_get_level() && ob_flush();
    return null;
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
    if (!is_int($value)) {
        if (is_string($value)) {
            if (!strIsNumeric($value)) throw new InvalidValueException('Invalid parameter $value: "'.$value.'" (non-numeric)');
            $value = (float) $value;
        }
        else Assert::float($value, '$value');

        if ($value < PHP_INT_MIN) throw new InvalidValueException('Invalid parameter $value: '.$value.' (out of range)');
        if ($value > PHP_INT_MAX) throw new InvalidValueException('Invalid parameter $value: '.$value.' (out of range)');
        $value = (int) round($value);
    }
    Assert::int($decimals, '$decimals');

    if ($value < 1024)
        return (string) $value;

    $unit = '';
    foreach (['K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'] as $unit) {
        $value /= 1024;
        if ($value < 1024)
            break;
    }

    return sprintf('%.'.$decimals.'f%s', $value, $unit);
}


/**
 * Convert a byte value to an integer supporting "php.ini" shorthand notation ("K", "M", "G").
 *
 * @param  string|int $value - byte value
 *
 * @return int - converted byte value
 */
function php_byte_value($value) {
    if (is_int($value)) return $value;
    Assert::string($value);
    if (!strlen($value)) return 0;

    $match = null;
    if (!preg_match('/^([+-]?[0-9]+)([KMG]?)$/i', $value, $match)) {
        throw new InvalidValueException('Invalid parameter $value: "'.$value.'" (not a PHP byte value)');
    }

    $iValue = (int)$match[1];

    switch (strtoupper($match[2])) {
        case 'K': $iValue <<= 10; break;    // 1024
        case 'M': $iValue <<= 20; break;    // 1024 * 1024
        case 'G': $iValue <<= 30; break;    // 1024 * 1024 * 1024
    }
    return $iValue;
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
    return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
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
 * @return bool|null - boolean value or NULL if the setting doesn't exist
 */
function ini_get_bool($option, $strict = true) {
    $value = ini_get($option);

    if ($value === false) return null;      // setting doesn't exist
    if ($value === '')    return false;     // setting is NULL (unset)

    switch (strtolower($value)) {
        case '1'    :
        case 'on'   :
        case 'true' :
        case 'yes'  : return true;

        case '0'    :
        case 'off'  :
        case 'false':
        case 'no'   :
        case 'none' : return false;
    }
    if ($strict) throw new InvalidValueException('Invalid "php.ini" setting for type boolean: "'.$option.'" = "'.$value.'"');

    return (bool)(int)$value;
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
 * @return int|null - integer value or NULL if the setting doesn't exist
 */
function ini_get_int($option, $strict = true) {
    $value = ini_get($option);

    if ($value === false) return null;      // setting doesn't exist
    if ($value === '')    return 0;         // setting is NULL (unset)

    $iValue = (int)$value;

    if ($strict && $value!==(string)$iValue)
        throw new InvalidValueException('Invalid "php.ini" setting for type integer: "'.$option.'" = "'.$value.'"');

    return $iValue;
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
 * @return int|null - integer value or NULL if the setting doesn't exist
 */
function ini_get_bytes($option, $strict = true) {
    $value = ini_get($option);

    if ($value === false) return null;      // setting doesn't exist
    if ($value === '')    return 0;         // setting is NULL (unset)

    $result = 0;
    try {
        $result = php_byte_value($value);
    }
    catch (InvalidValueException $ex) {
        if ($strict) throw new InvalidValueException('Invalid "php.ini" setting for PHP byte value: "'.$option.'" = "'.$value.'"', 0, $ex);
        $result = (int)$value;
    }
    return $result;
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
    if ($flags    === null) $flags = ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5;
    if ($encoding === null) $encoding = 'UTF-8';

    return htmlspecialchars($string, $flags, $encoding, $doubleEncode);
}


/**
 * Whether the byte order of the machine we are running on is "little endian".
 *
 * @return bool
 */
function isLittleEndian() {
    return (pack('S', 1) == "\x01\x00");
}


/**
 * Whether the specified path is relative or absolute, according to the current operating system.
 *
 * @param  string $path
 *
 * @return bool
 */
function isRelativePath($path) {
    Assert::string($path);

    if (WINDOWS)
        return !preg_match('/^[a-z]:/i', $path);

    if (strlen($path) && $path[0]=='/')
        return false;

    return true;                // an empty string cannot be considered absolute, so it's assumed to be a relative
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
    Assert::nullOrString($stringA, '$stringA');
    Assert::nullOrString($stringB, '$stringB');

    if ($ignoreCase) {
        if ($stringA===null || $stringB===null)
            return ($stringA === $stringB);
        return (strtolower($stringA) === strtolower($stringB));
    }
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
    return strCompare($stringA, $stringB, $ignoreCase=true);
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
    Assert::nullOrString($haystack, '$haystack');
    Assert::string      ($needle,   '$needle');

    $haystackLen = strlen($haystack);
    $needleLen   = strlen($needle);

    if (!$haystackLen || !$needleLen)
        return false;

    if ($ignoreCase)
        return (stripos($haystack, $needle) !== false);
    return (strpos($haystack, $needle) !== false);
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
    return strContains($haystack, $needle, $ignoreCase=true);
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
    if (is_array($prefix)) {
        foreach ($prefix as $p) {
            if (strStartsWith($string, $p, $ignoreCase)) return true;
        }
        return false;
    }

    Assert::nullOrString($string, '$string');
    Assert::string      ($prefix, '$prefix');

    $stringLen = strlen($string);
    $prefixLen = strlen($prefix);

    if (!$stringLen || !$prefixLen)
        return false;

    if ($ignoreCase)
        return (stripos($string, $prefix) === 0);
    return (strpos($string, $prefix) === 0);
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
    return strStartsWith($string, $prefix, $ignoreCase=true);
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
    if (is_array($suffix)) {
        foreach ($suffix as $s) {
            if (strEndsWith($string, $s, $ignoreCase)) return true;
        }
        return false;
    }
    Assert::nullOrString($string, '$string');
    Assert::string      ($suffix, '$suffix');

    $stringLen = strlen($string);
    $suffixLen = strlen($suffix);

    if (!$stringLen || !$suffixLen)
        return false;

    if ($ignoreCase)
        return (($stringLen-$suffixLen) === strripos($string, $suffix));
    return (($stringLen-$suffixLen) === strrpos($string, $suffix));
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
    return strEndsWith($string, $suffix, $ignoreCase=true);
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
    if (!isset($string)) return '';
    Assert::string($string, '$string');
    Assert::int   ($length, '$length');

    return substr($string, 0, $length);
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
 *  strLeftTo('abccc', 'c', -99) => 'abccc'   // specified number of occurrences doesn't exist
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
    Assert::string($string,         '$string');
    Assert::string($limiter,        '$limiter');
    Assert::int   ($count,          '$count');
    Assert::bool  ($includeLimiter, '$includeLimiter');
    if (!strlen($limiter)) throw new InvalidValueException('Invalid limiting substring: "" (empty)');

    if ($count > 0) {
        $pos = -1;
        while ($count) {
            $offset = $pos + 1;
            $pos = strpos($string, $limiter, $offset);
            if ($pos === false)                                      // not found
                return func_num_args() > 4 ? $onNotFound : $string;
            $count--;
        }
        $result = substr($string, 0, $pos);
        if ($includeLimiter)
            $result .= $limiter;
        return $result;
    }

    if ($count < 0) {
        $len = strlen($string);
        $pos = $len;
        while ($count) {
            $offset = $pos - $len - 1;
            if ($offset < -$len)                                     // not found
                return func_num_args() > 4 ? $onNotFound : $string;
            $pos = strrpos($string, $limiter, $offset);
            if ($pos === false)                                      // not found
                return func_num_args() > 4 ? $onNotFound : $string;
            $count++;
        }
        $result = substr($string, 0, $pos);
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
    if (!isset($string)) return '';
    Assert::string($string, '$string');
    Assert::int   ($length, '$length');

    if ($length == 0)
        return '';

    $result = substr($string, -$length);
    return $result===false ? '' : $result;
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
    Assert::string($string,         '$string');
    Assert::string($limiter,        '$limiter');
    Assert::int   ($count,          '$count');
    Assert::bool  ($includeLimiter, '$includeLimiter');
    if (!strlen($limiter)) throw new InvalidValueException('Illegal limiting substring: "" (empty)');

    if ($count > 0) {
        $pos = -1;
        while ($count) {
            $offset = $pos + 1;
            $pos = strpos($string, $limiter, $offset);
            if ($pos === false)                                      // not found
                return func_num_args() > 4 ? $onNotFound : '';
            $count--;
        }
        $pos   += strlen($limiter);
        $result = ($pos >= strlen($string)) ? '' : substr($string, $pos);
        if ($includeLimiter)
            $result = $limiter.$result;
        return $result;
    }

    if ($count < 0) {
        $len = strlen($string);
        $pos = $len;
        while ($count) {
            $offset = $pos - $len - 1;
            if ($offset < -$len)                                     // not found
                return func_num_args() > 4 ? $onNotFound : '';
            $pos = strrpos($string, $limiter, $offset);
            if ($pos === false)                                      // not found
                return func_num_args() > 4 ? $onNotFound : '';
            $count++;
        }
        $pos   += strlen($limiter);
        $result = ($pos >= strlen($string)) ? '' : substr($string, $pos);
        if ($includeLimiter)
            $result = $limiter.$result;
        return $result;
    }

    // $count == 0
    return $string;
}


/**
 * Whether a string is wrapped in single or double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsQuoted($value) {
    if (!is_string($value))
        return false;
    return (strlen($value) > 1 && (strIsSingleQuoted($value) || strIsDoubleQuoted($value)));
}


/**
 * Whether a string is wrapped in single quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsSingleQuoted($value) {
    if (!is_string($value))
        return false;
    $len = strlen($value);
    return ($len > 1 && $value[0]=="'" && $value[--$len]=="'");
}


/**
 * Whether a string is wrapped in double quotes.
 *
 * @param  string $value
 *
 * @return bool
 */
function strIsDoubleQuoted($value) {
    if (!is_string($value))
        return false;
    $len = strlen($value);
    return ($len > 1 && $value[0]=='"' && $value[--$len]=='"');
}


/**
 * Whether a string consists only of digits (0-9).
 *
 * @param  mixed $value
 *
 * @return bool
 */
function strIsDigits($value) {
    return ctype_digit($value);
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

    switch (strtolower($value)) {
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
 * Reduce multiple consecutive white space characters in a string to a single one.
 *
 * @param  string $string               - string to process
 * @param  bool   $joinLines [optional] - whether to return a single line result (default: yes)
 * @param  string $separator [optional] - the separator to use for joining (default: space " ")
 *
 * @return string
 */
function strCollapseWhiteSpace($string, $joinLines=true, $separator=' ') {
    Assert::string($string, '$string');

    $string = normalizeEOL($string);
    if ($joinLines) {
        $string = str_replace(EOL_UNIX, $separator, $string);
    }
    return preg_replace('/\s+/', ' ', $string);
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
    Assert::string($string, '$string');
    Assert::string($mode,   '$mode');
    $done = false;

    if (strContains($string, EOL_NETSCAPE)) {
        $count1 = $count2 = null;
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
        throw new InvalidValueException('Invalid parameter $mode: "'.$mode.'"');
    }
    return $string;
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
    Assert::object($object, '$object');
    Assert::int   ($access, '$access');

    $source = (array)$object;
    $result = [];

    foreach ($source as $name => $value) {
        if ($name[0] != "\0") {                     // public
            if ($access & ACCESS_PUBLIC) {
                $result[$name] = $value;
            }
        }
        else if ($name[1] == '*') {                 // protected
            if ($access & ACCESS_PROTECTED) {
                $publicName = substr($name, 3);
                $result[$publicName] = $value;
            }
        }
        else {                                      // private
            if ($access & ACCESS_PRIVATE) {
                $publicName = strRightFrom($name, "\0", 2);
                if (!\array_key_exists($publicName, $result))
                    $result[$publicName] = $value;
            }
        }
    }
    return $result;
}


/**
 * Alias of gettype() for C enthusiasts.
 *
 * @param  mixed $var
 *
 * @return string
 */
function typeOf($var) {
    return gettype($var);
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
    if (!is_dir($dirname))
        return false;

    if (!is_array($ignore))
        $ignore = [$ignore];
    $ignored = \array_unique(\array_merge(['.', '..'], $ignore));   // always ignore pseudo directories '.' and '..'

    foreach (scandir($dirname) as $entry) {
        if (!in_array($entry, $ignored))
            return false;
    }

    /*
    // TODO: for better performance
    $hDir = openDir($dir);
    while (($entry = readDir($hDir)) !== false) {
        if ($entry=='.' || $entry=='..')
            continue;
    }
    closeDir($hDir);
    */
    return true;
}


/**
 * Manually load the specified class, interface or trait. If the component was already loaded the call does nothing.
 *
 * @param  string $name - name
 *
 * @return string|null - the same name or NULL if a component of that name doesn't exist or couldn't be loaded
 */
function autoload($name) {
    if (class_exists($name, true) || interface_exists($name, true) || trait_exists($name, true))
        return $name;
    return null;
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
    try {
        return class_exists($name, true);
    }
    catch (\Throwable $ex) {}   // faulty class loaders must not block the script from continuation
    catch (\Exception $ex) {}

    return class_exists($name, false);
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
    try {
        return interface_exists($name, true);
    }
    catch (\Throwable $ex) {}   // faulty class loaders must not block the script from continuation
    catch (\Exception $ex) {}

    return interface_exists($name, false);
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
    try {
        return trait_exists($name, true);
    }
    catch (\Throwable $ex) {}   // faulty class loaders must not block the script from continuation
    catch (\Exception $ex) {}

    return trait_exists($name, false);
}


/**
 * Whether a variable can be used like an array.
 *
 * Complement for PHP's <tt>is_array()</tt> function adding support for {@link \ArrayAccess} parameters.
 *
 * @param  array|\ArrayAccess $var
 *
 * @return bool
 */
function is_array_like($var) {
    return is_array($var) || $var instanceof \ArrayAccess;
}


/**
 * Return the simple name of a class name (i.e. the base name).
 *
 * @param  string|object $class - class name or instance
 *
 * @return string
 */
function simpleClassName($class) {
    if (is_object($class)) $class = get_class($class);
    else                   Assert::string($class);
    return strRightFrom($class, $limiter='\\', $count=-1, $includeLimiter=false, $onNotFound=$class);
}


/**
 * Return one of the metatypes "class", "interface" or "trait" for an object type identifier.
 *
 * @param  string $name - name
 *
 * @return string metatype
 */
function metatypeOf($name) {
    Assert::string($name);
    if ($name == '') throw new InvalidValueException('Invalid parameter $name: ""');

    if (is_class    ($name)) return 'class';
    if (is_interface($name)) return 'interface';
    if (is_trait    ($name)) return 'trait';

    return '(unknown type)';
}


/**
 * Procedural replacement for rosasurfer\util\Validator::isDateTime()
 *
 * Whether the specified string value represents a valid date or datetime value.
 *
 * @param  string          $string            - string value
 * @param  string|string[] $format [optional] - A valid date/datetime format. If multiple values are supplied whether the   <br>
 *                                              specified string fits at least one of them.                                 <br>
 *                                              Supported format strings: 'Y-m-d [H:i[:s]]'                                 <br>
 *                                                                        'Y.m.d [H:i[:s]]'                                 <br>
 *                                                                        'd.m.Y [H:i[:s]]'                                 <br>
 *                                                                        'd/m/Y [H:i[:s]]'                                 <br>
 *
 * @return int|bool - timestamp matching the string or FALSE if the string is not a valid date/datetime value
 *
 * @see \rosasurfer\util\Validator::isDateTime()
 */
function is_datetime($string, $format = 'Y-m-d') {
    return Validator::isDateTime($string, $format);
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
    return $value ? $altValue : $value;
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
    return !$value ? $altValue : $value;
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
 * @param  int   $sort_flags [optional]
 *
 * @return array
 *
 * @see    ksort()
 */
function ksort_r(array $values, $sort_flags = SORT_REGULAR) {
    ksort($values, $sort_flags);
    return $values;
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
    Assert::int($count, '$count');
    if (abs($count) == 1)
        return $singular;
    return $plural;
}


/**
 * Execute a task in a synchronized way. Emulates the Java keyword "synchronized".
 *
 * @param  \Closure $task             - task to execute (an anonymous function is implicitly casted)
 * @param  string   $mutex [optional] - mutex identifier (default: the calling line of code)
 */
function synchronized(\Closure $task, $mutex = null) {
    if (!isset($mutex)) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $mutex = $trace[0]['file'].'#'.$trace[0]['line'];
    }
    $lock = new Lock($mutex);

    try {
        $task();
    }
    finally {
        $lock->release();
    }
}


/**
 * Lookup and return a {@link Url} helper for the named {@link ActionMapping}.
 *
 * @param  string $name - route name
 *
 * @return Url
 */
function route($name) {
    $path = $query = $hash = null;

    $pos = strpos($name, '#');
    if ($pos !== false) {
        $hash = substr($name, $pos);
        $name = substr($name, 0, $pos);
    }
    $pos = strpos($name, '?');
    if ($pos !== false) {
        $query = substr($name, $pos);
        $name  = substr($name, 0, $pos);
    }

    $mapping = Request::getModule()->getMapping($name);
    if (!$mapping) throw new RuntimeException('Route "'.$name.'" not found');

    $path = $mapping->getPath();
    if ($path[0] == '/') {
        $path = ($path=='/') ? '' : substr($path, 1);   // substr() returns FALSE on start==length
    }
    if ($query) $path .= $query;
    if ($hash)  $path .= $hash;

    return new Url($path);
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
    return new Url($uri);
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
    return new VersionedUrl($uri);
}


/**
 * Parse command line arguments and match them against the specified {@link http://docopt.org/#} syntax definition.
 *
 * @param  string          $doc                - help text, i.e. a syntax definition in Docopt language format
 * @param  string|string[] $args    [optional] - arguments to parse (default: the arguments passed in $_SERVER['argv'])
 * @param  array           $options [optional] - parser options (default: none)
 *
 * @return DocoptResult - the parsing result
 */
function docopt($doc, $args=null, array $options=[]) {
    $parser = new DocoptParser($options);
    return $parser->parse($doc, $args);
}
