<?php
declare(strict_types=1);

/**
 * Helper functions and constants
 */
namespace rosasurfer\ministruts;

use ArrayAccess;
use Closure;
use ErrorException;
use Traversable;

use rosasurfer\ministruts\console\docopt\DocoptParser;
use rosasurfer\ministruts\console\docopt\DocoptResult;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\di\proxy\Request;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\core\lock\Lock;
use rosasurfer\ministruts\struts\url\Url;
use rosasurfer\ministruts\struts\url\VersionedUrl;


// Whether we run on a command line interface, on localhost and/or on Windows.
define('rosasurfer\ministruts\_CLI',        defined('\STDIN') && is_resource(\STDIN));
define('rosasurfer\ministruts\_LOCALHOST',  !_CLI && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', $_SERVER['SERVER_ADDR']]));
define('rosasurfer\ministruts\_MACOS',      strtoupper(PHP_OS) == 'DARWIN');
define('rosasurfer\ministruts\_WINDOWS',    defined('\PHP_WINDOWS_VERSION_BUILD'));
define('rosasurfer\ministruts\_NUL_DEVICE', _WINDOWS ? 'nul' : '/dev/null');

/** @var bool    - whether we run on a command line interface */
const CLI        = _CLI;
/** @var bool    - whether we run on a webserver's localhost */
const LOCALHOST  = _LOCALHOST;
/** @var bool    - whether we run on MacOS */                       // constant declarations improve IDE auto-completion
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
const GB                = 1024 << 20;

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
 * Complement of PHP's <tt>array_filter()</tt> function adding support for {@link Traversable} arguments.
 *
 * @template T of mixed
 *
 * @param  iterable<T> $input
 * @param  ?callable   $callback [optional]
 * @param  int         $flags    [optional]
 *
 * @return array<T>
 */
function array_filter(iterable $input, ?callable $callback = null, int $flags = 0): array {
    $args = func_get_args();
    if ($input instanceof Traversable) {
        $args[0] = iterator_to_array($input, true);
    }
    return \array_filter(...$args);
}


/**
 * Return all or a subset of the keys of an array-like variable.
 *
 * Complement of PHP's <tt>array_keys()</tt> function adding support for {@link Traversable} arguments.
 *
 * @param  iterable<mixed> $array
 * @param  mixed           $search [optional]
 * @param  bool            $strict [optional]
 *
 * @return array<int|string>
 */
function array_keys(iterable $array, $search = null, bool $strict = false): array {
    $args = func_get_args();
    if ($array instanceof Traversable) {
        $args[0] = iterator_to_array($array, true);
    }
    return \array_keys(...$args);
}


/**
 * Merges the elements of one or more array-like variables together so that the values of one are appended to the end of the
 * previous one. Values with the same string keys will overwrite the previous one. Numeric keys will be renumbered and values
 * with the same numeric keys will not overwrite the previous one.
 *
 * Complement of PHP's <tt>array_merge()</tt> function adding support for {@link Traversable} arguments.
 *
 * @template T1 of mixed
 * @template T2 of mixed
 *
 * @param  iterable<T1> $array
 * @param  iterable<T2> ...$arrays
 *
 * @return array<T1|T2>
 */
function array_merge(iterable $array, iterable ...$arrays): array {
    $args = func_get_args();
    foreach ($args as $key => $arg) {
        if ($arg instanceof Traversable) {
            $args[$key] = iterator_to_array($arg, true);
        }
    }
    return \array_merge(...$args);
}


/**
 * Return a version-aware URL helper for the given URI {@link VersionedUrl}. An URI starting with a slash "/"
 * is interpreted as relative to the application's base URI. An URI not starting with a slash is interpreted as
 * relative to the application {@link Module}'s base URI (the module the current request belongs to).
 *
 * Procedural equivalent of <tt>new \rosasurfer\ministruts\struts\url\VersionedUrl($uri)</tt>.
 *
 * @param  string $uri
 *
 * @return VersionedUrl
 */
function asset(string $uri): VersionedUrl {
    return new VersionedUrl($uri);
}


/**
 * Manually load the specified class, interface or trait. If the component was already loaded the call does nothing.
 *
 * @param  string $name - name
 *
 * @return ?string - the same name or NULL if a component of that name doesn't exist or couldn't be loaded
 */
function autoload(string $name): ?string {
    if (class_exists($name, true) || interface_exists($name, true) || trait_exists($name, true)) {
        return $name;
    }
    return null;
}


/**
 * Convert a value to a boolean and return the string "true" or "false".
 *
 * @param  mixed $value - value to interpret
 *
 * @return string
 */
function boolToStr($value): string {
    if (is_string($value)) {
        $value = trim(strtolower($value));
        switch ($value) {
            case 'true':
            case 'on':
            case 'yes': return 'true';

            case 'false':
            case 'off':
            case 'no':  return 'false';

            default:
                if (is_numeric($value)) {
                    $value = (float) $value;
                }
        }
    }
    return $value ? 'true':'false';
}


/**
 * Send an "X-Debug-???" header with a message. Each sent header name will end with an increasing number.
 *
 * @param  mixed $message
 *
 * @return void
 */
function debugHeader($message): void {
    if (CLI) return;

    if     (is_scalar($message)) $message = (string) $message;
    elseif (is_null($message))   $message = '(null)';
    else                         $message = print_r($message, true);

    static $i = 0;
    $i++;
    header("X-Debug-$i: ".str_replace(["\r", "\n"], ['\r', '\n'], $message));
}


/**
 * Parse command line arguments and match them against the specified {@link https://docopt.org/#} syntax definition.
 *
 * @param  string                     $doc                - help text, i.e. a syntax definition in Docopt language format
 * @param  string|string[]|null       $args    [optional] - arguments to parse (default: the arguments passed in $_SERVER['argv'])
 * @param  array<string, bool|string> $options [optional] - parser options (default: none)
 *
 * @return DocoptResult - the parsing result
 */
function docopt(string $doc, $args=null, array $options=[]): DocoptResult {
    $parser = new DocoptParser($options);
    return $parser->parse($doc, $args);
}


/**
 * Dumps a variable to the standard output device or into a string.
 *
 * @param  mixed $var                     - variable
 * @param  bool  $return       [optional] - TRUE,  if the variable is to be dumped into a string <br>
 *                                          FALSE, if the variable is to be dumped to the standard output device (default)
 * @param  bool  $flushBuffers [optional] - whether to flush output buffers on output (default: yes)
 *
 * @return ($return is true ? string : null)
 */
function dump($var, bool $return = false, bool $flushBuffers = true): ?string {
    if ($return) ob_start();
    var_dump($var);
    if ($return) return ob_get_clean();

    $flushBuffers && ob_get_level() && ob_flush();
    return null;
}


/**
 * Alias of print_p($var, false, $flushBuffers)
 *
 * Outputs a variable in a formatted and pretty way. Output always ends with a EOL marker.
 *
 * @param  mixed $var
 * @param  bool  $flushBuffers [optional] - whether to flush output buffers (default: yes)
 *
 * @return void
 */
function echof($var, bool $flushBuffers = true): void {
    print_p($var, false, $flushBuffers);
}


/**
 * Return the first element of an array-like variable without affecting the internal array pointer.
 *
 * @template T of mixed
 *
 * @param  iterable<T> $values
 *
 * @return ?T - the first element or NULL if the array-like variable is empty
 */
function first(iterable $values) {
    if ($values instanceof Traversable) {
        $values = iterator_to_array($values, false);
    }
    return $values ? reset($values) : null;
}


/**
 * Return the first key of an array-like variable without affecting the internal array pointer.
 *
 * @param  iterable<mixed> $values
 *
 * @return int|string|null - the first key or NULL if the array-like variable is empty
 */
function firstKey(iterable $values) {
    if ($values instanceof Traversable) {
        $values = iterator_to_array($values);
    }
    if ($values) {
        reset($values);
        return key($values);
    }
    return null;
}


/**
 * Return the host name of the internet host specified by a given IP address.
 *
 * @param  string $ipAddress - the host IP address
 *
 * @return string - the host name on success, or the unmodified IP address on resolver error
 */
function getHostByAddress(string $ipAddress): string {
    if ($ipAddress == '') throw new InvalidValueException('Invalid parameter $ipAddress: "" (empty)');

    $result = \gethostbyaddr($ipAddress);

    if ($result === false) throw new InvalidValueException("Invalid parameter \$ipAddress: \"$ipAddress\"");

    if ($result==='localhost' && !strStartsWith($ipAddress, '127.')) {
        $result = $ipAddress;
    }
    return $result;
}


/**
 * Convert special characters to HTML entities.
 *
 * Inline replacement and shortcut for htmlspecialchars() using different default flags.
 *
 * @param  string  $string
 * @param  ?int    $flags        [optional] - default: ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5
 * @param  ?string $encoding     [optional] - default: 'UTF-8'
 * @param  bool    $doubleEncode [optional] - default: yes
 *
 * @return string - converted string
 *
 * @see   \htmlspecialchars()
 */
function hsc(string $string, ?int $flags = null, ?string $encoding = null, bool $doubleEncode = true): string {
    if (!isset($flags))    $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5;
    if (!isset($encoding)) $encoding = 'UTF-8';

    return htmlspecialchars($string, $flags, $encoding, $doubleEncode);
}


/**
 * Checks if a value exists in an array-like variable.
 *
 * Complement of PHP's <tt>in_array()</tt> function adding support for {@link Traversable} arguments.
 *
 * @param  mixed           $needle
 * @param  iterable<mixed> $haystack
 * @param  bool            $strict [optional]
 *
 * @return bool
 */
function in_array($needle, iterable $haystack, bool $strict = false): bool {
    if ($haystack instanceof Traversable) {
        $haystack = iterator_to_array($haystack, false);
    }
    return \in_array($needle, $haystack, $strict);
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
function ini_get_bool(string $option, bool $strict = true): ?bool {
    $value = \ini_get($option);

    if ($value === false) return null;      // setting doesn't exist
    if ($value === '')    return false;     // setting is empty or NULL (unset)

    $flags = $strict ? FILTER_NULL_ON_FAILURE : 0;
    /** @var ?bool $result */
    $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, $flags);

    if ($result === null) {
        throw new InvalidValueException("Invalid \"php.ini\" setting for strict type boolean: \"$option\" = \"$value\"");
    }
    return $result;
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
function ini_get_bytes(string $option, bool $strict = true): ?int {
    $value = \ini_get($option);

    if ($value === false) return null;      // setting doesn't exist
    if ($value === '')    return 0;         // setting is empty or NULL (unset)

    $result = 0;
    try {
        $result = php_byte_value($value);
    }
    catch (InvalidValueException $ex) {
        if ($strict) throw new InvalidValueException("Invalid \"php.ini\" setting for PHP byte value: \"$option\" = \"$value\"", 0, $ex);
        $result = (int)$value;
    }
    return $result;
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
function ini_get_int(string $option, bool $strict = true): ?int {
    $value = \ini_get($option);

    if ($value === false) return null;      // setting doesn't exist
    if ($value === '')    return 0;         // setting is empty or NULL (unset)

    $iValue = (int)$value;

    if ($strict && $value !== (string)$iValue) {
        throw new InvalidValueException("Invalid \"php.ini\" setting for strict type int: \"$option\" = \"$value\"");
    }
    return $iValue;
}


/**
 * Whether a variable can be used like an array.
 *
 * Complement for PHP's <tt>is_array()</tt> function adding support for {@link \ArrayAccess} arguments.
 *
 * @param  mixed $var
 *
 * @return bool
 */
function is_array_like($var): bool {
    return \is_array($var) || $var instanceof \ArrayAccess;
}


/**
 * Whether the byte order of the machine we are running on is "little endian".
 *
 * @return bool
 */
function isLittleEndian(): bool {
    return (pack('S', 1) == "\x01\x00");
}


/**
 * Whether the specified path is relative or absolute, according to the current operating system.
 *
 * @param  string $path
 *
 * @return bool
 */
function isRelativePath(string $path): bool {
    if (WINDOWS) {
        return !preg_match('/^[a-z]:/i', $path);
    }
    if (strlen($path) && $path[0]=='/') {
        return false;
    }
    return true;                // an empty string cannot be considered absolute, so it's interpreted as relative
}


/**
 * Whether the given index exists in an array-like variable.
 *
 * Complement of PHP's <tt>key_exists()</tt> function adding support for {@link \ArrayAccess} arguments.
 *
 * @param  int|string                             $key
 * @param  mixed[]|ArrayAccess<int|string, mixed> $array
 *
 * @return bool
 */
function key_exists($key, $array): bool {
    if ($array instanceof ArrayAccess) {
        return $array->offsetExists($key);
    }
    return \array_key_exists($key, $array);
}


/**
 * Return a sorted copy of the specified array using the algorythm and parameters of {@link \ksort()}.
 * Unlike the PHP function this function will not modify the passed array.
 *
 * @template T of mixed
 *
 * @param  array<T> $values
 * @param  int      $flags [optional]
 *
 * @return array<T>
 */
function ksortc(array $values, int $flags = SORT_REGULAR): array {
    ksort($values, $flags);
    return $values;
}


/**
 * Return the last element of an array-like variable without affecting the internal array pointer.
 *
 * @template T of mixed
 *
 * @param  iterable<T> $values
 *
 * @return ?T - the last element or NULL if the array-like variable is empty
 */
function last(iterable $values) {
    if ($values instanceof Traversable) {
        $values = iterator_to_array($values, false);
    }
    return $values ? end($values) : null;
}


/**
 * Return the last key of an array-like variable without affecting the internal array pointer.
 *
 * @param  iterable<mixed> $values
 *
 * @return int|string|null - the last key or NULL if the array-like variable is empty
 */
function lastKey(iterable $values) {
    if ($values instanceof Traversable) {
        $values = iterator_to_array($values);
    }
    if ($values) {
        end($values);
        return key($values);
    }
    return null;
}


/**
 * Return one of the meta types "class", "interface" or "trait" for a component identifier.
 *
 * @param  string $name - name
 *
 * @return string - meta type
 */
function metatypeOf(string $name): string {
    if ($name == '') throw new InvalidValueException('Invalid parameter $name: "" (empty)');

    if (class_exists($name))     return 'class';
    if (interface_exists($name)) return 'interface';
    if (trait_exists($name))     return 'trait';

    return '(unknown type)';
}


/**
 * Normalize EOL markers in a string. If the string contains mixed line endings the number of lines of the passed and the
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
function normalizeEOL(string $string, string $mode = EOL_UNIX): string {
    $done = false;

    if (strContains($string, EOL_NETSCAPE)) {
        $count1 = $count2 = null;
        $tmp = str_replace(EOL_NETSCAPE, EOL_UNIX, $string, $count1);
        if (!strContains($tmp, EOL_MAC)) {
            str_replace(EOL_UNIX, '.', $tmp, $count2);
            if ($count1 == $count2) {
                $string = $tmp;            // only Netscape => OK
                $done = true;
            }
        }
    }
    if (!$done) $string = str_replace([EOL_WINDOWS, EOL_MAC], EOL_UNIX, $string);

    if ($mode==EOL_MAC || $mode==EOL_NETSCAPE || $mode==EOL_WINDOWS) {
        $string = str_replace(EOL_UNIX, $mode, $string);
    }
    elseif ($mode != EOL_UNIX) {
        throw new InvalidValueException("Invalid parameter \$mode: \"$mode\"");
    }
    return $string;
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
function numf(float $number, int $decimals = 0, string $decimalSeparator = '.', string $thousandsSeparator = ','): string {
    return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
}


/**
 * Convert an object to an array.
 *
 * @param  object $object
 * @param  int    $access [optional] - access levels of the properties to return in the result (default: ACCESS_PUBLIC)
 *
 * @return array<string, mixed>
 */
function objectToArray(object $object, int $access = ACCESS_PUBLIC): array {
    $source = (array)$object;
    $array = [];

    foreach ($source as $name => $value) {
        if ($name[0] != "\0") {                     // public
            if ($access & ACCESS_PUBLIC) {
                $array[$name] = $value;
            }
        }
        elseif ($name[1] == '*') {                  // protected
            if ($access & ACCESS_PROTECTED) {
                $publicName = substr($name, 3);
                $array[$publicName] = $value;
            }
        }
        elseif ($access & ACCESS_PRIVATE) {         // private
            $publicName = strRightFrom($name, "\0", 2);
            if (!\key_exists($publicName, $array)) {
                $array[$publicName] = $value;
            }
        }
    }
    return $array;
}


/**
 * Convert a byte value to an integer supporting "php.ini" shorthand notation ("K", "M", "G").
 *
 * @param  string|int $value - byte value
 *
 * @return int - converted byte value
 */
function php_byte_value($value): int {
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
 * Return a pluralized string according to the specified number of items.
 *
 * @param  int    $count               - the number of items to determine the output from
 * @param  string $singular [optional] - singular form of string (default: empty string)
 * @param  string $plural   [optional] - plural form of string (default: "s")
 *
 * @return string
 */
function pluralize(int $count, string $singular='', string $plural='s'): string {
    if (abs($count) == 1) {
        return $singular;
    }
    return $plural;
}


/**
 * Format a byte value.
 *
 * @param  int|float|string $value               - byte value
 * @param  int              $decimals [optional] - number of decimal digits (default: 1)
 *
 * @return string - formatted byte value
 */
function prettyBytes($value, int $decimals = 1): string {
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
 * Prints a variable in a formatted and pretty way. Output always ends with a EOL marker.
 *
 * @param  mixed $var                     - variable
 * @param  bool  $return       [optional] - TRUE,  if the result is to be returned as a string <br>
 *                                          FALSE, if the result is to be printed to the screen (default)
 * @param  bool  $flushBuffers [optional] - whether to flush output buffers on output (default: TRUE)
 *
 * @return ($return is true ? string : null)
 */
function print_p($var, bool $return = false, bool $flushBuffers = true): ?string {
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
                     position:relative; top:initial; left:initial; z-index:4294967295;
                     float:left; width:initial; height:initial;
                     margin:0; padding:0; border-width:0;
                     color:inherit; background-color:inherit">
                   <pre style="width:initial; height:initial; margin:0; padding:0; border-width:0;
                        color:inherit; background-color:inherit; white-space:pre; line-height:12px;
                        font:normal normal 12px/normal \'Courier New\',courier,serif">'.hsc($str).'</pre>
                </div>';
    }
    if (!strEndsWith($str, NL)) {
        $str .= NL;
    }
    if ($return) return $str;

    echo $str;
    $flushBuffers && ob_get_level() && ob_flush();
    return null;
}


/**
 * Same as the builtin function but throws an exception in case of errors.
 *
 * @param  string $path
 *
 * @return string
 *
 * @throws ErrorException in case of errors
 *
 * @link   http://www.php.net/manual/en/function.realpath.php
 */
function realpath(string $path): string {
    // realpath() can return FALSE without generating an internal error
    $result = \realpath($path);
    if ($result === false) throw new ErrorException("Error executing realpath(\"$path\") => false");
    return $result;
}


/**
 * Lookup and return a {@link Url} helper for the named {@link \rosasurfer\ministruts\struts\ActionMapping}.
 *
 * @param  string $name - route name
 *
 * @return Url
 */
function route(string $name): Url {
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

    $module = Request::getModule();
    if (!$module) throw new RuntimeException("Request module not found");

    $mapping = $module->getMapping($name);
    if (!$mapping) throw new RuntimeException("Route \"$name\" not found");

    $path = $mapping->getPath();
    if ($path[0] == '/') {
        $path = ($path=='/') ? '' : substr($path, 1);   // substr() returns FALSE on start==length
    }
    if ($query) $path .= $query;
    if ($hash)  $path .= $hash;

    return new Url($path);
}


/**
 * Return the simple name of a class (i.e. the base name).
 *
 * @param  string|object $class - class name or instance
 *
 * @return string
 */
function simpleClassName($class) {
    if (is_object($class)) {
        $class = get_class($class);
    }
    else Assert::string($class);

    return strRightFrom($class, '\\', -1, false, $class);
}


/**
 * Print a message to STDERR.
 *
 * @param  string $message
 *
 * @return void
 */
function stderr(string $message): void {
    $hStream = CLI ? \STDERR : fopen('php://stderr', 'a');
    fwrite($hStream, $message);
    if (!CLI) fclose($hStream);
}


/**
 * Print a message to STDOUT.
 *
 * @param  string $message
 *
 * @return void
 */
function stdout(string $message): void {
    $hStream = CLI ? \STDOUT : fopen('php://stdout', 'a');
    fwrite($hStream, $message);
    if (!CLI) fclose($hStream);
}


/**
 * Collapse multiple consecutive white space characters in a string to a single one.
 *
 * @param  string $string               - string to process
 * @param  bool   $joinLines [optional] - whether to return a single line result (default: yes)
 * @param  string $separator [optional] - the separator to use for joining (default: space character " ")
 *
 * @return string
 */
function strCollapseWhiteSpace(string $string, bool $joinLines=true, string $separator=' '): string {
    $string = normalizeEOL($string);
    if ($joinLines) {
        $string = str_replace(EOL_UNIX, $separator, $string);
    }
    /** @var string result */
    $result = preg_replace('/\s+/', ' ', $string);
    return $result;
}


/**
 * Functional replacement for ($a === $b).
 *
 * @param  ?string $a
 * @param  ?string $b
 * @param  bool    $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strCompare(?string $a, ?string $b, bool $ignoreCase = false): bool {
    if ($ignoreCase) {
        if (isset($a, $b)) {
            $a = strtolower($a);
            $b = strtolower($b);
        }
    }
    return ($a === $b);
}


/**
 * Functional replacement for ($a === $b) ignoring case differences.
 *
 * @param  ?string $a
 * @param  ?string $b
 *
 * @return bool
 */
function strCompareI(?string $a, ?string $b): bool {
    return strCompare($a, $b, true);
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
function strContains(string $haystack, string $needle, bool $ignoreCase = false): bool {
    if (!strlen($haystack) || !strlen($needle)) {
        return false;
    }
    if ($ignoreCase) {
        return (stripos($haystack, $needle) !== false);
    }
    return (strpos($haystack, $needle) !== false);
}


/**
 * Whether a string contains a substring ignoring case differences.
 *
 * @param  string $haystack
 * @param  string $needle
 *
 * @return bool
 */
function strContainsI(string $haystack, string $needle) : bool {
    return strContains($haystack, $needle, true);
}


/**
 * Whether a string ends with a substring. If multiple suffixes are given, whether the string
 * ends with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $suffix                - one or more suffixes
 * @param  bool            $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strEndsWith(string $string, $suffix, bool $ignoreCase = false): bool {
    if (is_array($suffix)) {
        foreach ($suffix as $s) {
            if (strEndsWith($string, $s, $ignoreCase)) {
                return true;
            }
        }
        return false;
    }
    Assert::string($suffix, '$suffix');

    $stringLen = strlen($string);
    $suffixLen = strlen($suffix);

    if (!$stringLen || !$suffixLen) {
        return false;
    }
    if ($ignoreCase) {
        return (strripos($string, $suffix) === $stringLen-$suffixLen);
    }
    return (strrpos($string, $suffix) === $stringLen-$suffixLen);
}


/**
 * Whether a string ends with a substring ignoring case differences. If multiple suffixes
 * are given, whether the string ends with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $suffix - one or more suffixes
 *
 * @return bool
 */
function strEndsWithI(string $string, $suffix): bool {
    return strEndsWith($string, $suffix, true);
}


/**
 * Whether a string consists only of digits (0-9).
 *
 * @param  string $string
 *
 * @return bool
 */
function strIsDigits(string $string): bool {
    return ctype_digit($string);
}


/**
 * Whether a string is wrapped in double quotes.
 *
 * @param  string $string
 *
 * @return bool
 */
function strIsDoubleQuoted(string $string): bool {
    $len = strlen($string);
    if ($len > 1) {
        return ($string[0]=='"' && $string[--$len]=='"');
    }
    return false;
}


/**
 * Whether a string consists only of numerical characters and represents a valid numerical value. Unlike the built-in
 * PHP function is_numeric() this function returns FALSE if the string begins with non-numerical characters
 * (e.g. white space).
 *
 * @param  string $string
 *
 * @return bool
 */
function strIsNumeric(string $string): bool {
    if (is_numeric($string)) {
        return ctype_graph($string);
    }
    return false;
}


/**
 * Whether a string is wrapped in single or double quotes.
 *
 * @param  string $string
 *
 * @return bool
 */
function strIsQuoted(string $string): bool {
    $len = strlen($string);
    if ($len > 1) {
        return (strIsSingleQuoted($string) || strIsDoubleQuoted($string));
    }
    return false;
}


/**
 * Whether a string is wrapped in single quotes.
 *
 * @param  string $string
 *
 * @return bool
 */
function strIsSingleQuoted(string $string): bool {
    $len = strlen($string);
    if ($len > 1) {
        return ($string[0]=="'" && $string[--$len]=="'");
    }
    return false;
}


/**
 * Return the left part of a string.
 *
 * @param  string $string - initial string
 * @param  int    $length - positive: number of returned left characters <br>
 *                          negative: all except the specified number of right characters
 *
 * @return string - substring
 *
 * @example
 * <pre>
 *  strLeft('abcde',  2) => 'ab'
 *  strLeft('abcde', -1) => 'abcd'
 * </pre>
 */
function strLeft(string $string, int $length): string {
    return substr($string, 0, $length);
}


/**
 * Return the left part of a string up to the specified occurrence of a limiting substring.
 *
 * @param  string $string                    - initial string
 * @param  string $limiter                   - limiting substring (one or more characters)
 * @param  int    $count          [optional] - positive: occurrence of the limiting substring counted from the start of the string <br>
 *                                             negative: occurrence of the limiting substring counted from the end of the string   <br>
 *                                             0:        an empty string is returned                                               <br>
 *                                             (default: 1 = the first occurrence)
 * @param  bool   $includeLimiter [optional] - whether to include the limiter in the returned result
 *                                             (default: no)
 * @param  string $onNotFound     [optional] - string to return if the specified occurrence of the limiter is not found
 *                                             (default: the initial string)
 * @return string
 *
 * @example
 * <pre>
 *  strLeftTo('abcde', 'd')      => 'abc'
 *  strLeftTo('abcde', 'x')      => 'abcde'    // limiter not found
 *  strLeftTo('abccc', 'c',   3) => 'abcc'
 *  strLeftTo('abccc', 'c',  -3) => 'ab'
 *  strLeftTo('abccc', 'c', -99) => 'abccc'    // specified number of occurrences not found
 * </pre>
 */
function strLeftTo(string $string, string $limiter, int $count=1, bool $includeLimiter=false, string $onNotFound=''): string {
    if (!strlen($limiter)) throw new InvalidValueException('Invalid limiting substring: "" (empty)');

    if ($count > 0) {
        $pos = -1;
        while ($count) {
            $offset = $pos + 1;
            $pos = strpos($string, $limiter, $offset);
            if ($pos === false) {                                   // not found
                return func_num_args() > 4 ? $onNotFound : $string;
            }
            $count--;
        }
        $result = substr($string, 0, $pos);
        if ($includeLimiter) {
            $result .= $limiter;
        }
        return $result;
    }

    if ($count < 0) {
        $len = strlen($string);
        $pos = $len;
        while ($count) {
            $offset = $pos - $len - 1;
            if ($offset < -$len) {                                  // not found
                return func_num_args() > 4 ? $onNotFound : $string;
            }
            $pos = strrpos($string, $limiter, $offset);
            if ($pos === false) {                                   // not found
                return func_num_args() > 4 ? $onNotFound : $string;
            }
            $count++;
        }
        $result = substr($string, 0, $pos);
        if ($includeLimiter) {
            $result .= $limiter;
        }
        return $result;
    }

    // $count == 0
    return '';
}


/**
 * Return the right part of a string.
 *
 * @param  string $string - initial string
 * @param  int    $length - positive: number of returned right characters <br>
 *                          negative: all except the specified number of left characters
 *
 * @return string - substring
 *
 * @example
 * <pre>
 *  strRight('abcde',  1) => 'e'
 *  strRight('abcde', -2) => 'cde'
 * </pre>
 */
function strRight(string $string, int $length): string {
    if (!$length) return '';

    /** @var string|false $result - since PHP8.0 substr() always returns string */
    $result = substr($string, -$length);
    return $result===false ? '' : $result;
}


/**
 * Return the right part of a string from the specified occurrence of a limiting substring.
 *
 * @param  string $string                    - initial string
 * @param  string $limiter                   - limiting substring (one or more characters)
 * @param  int    $count          [optional] - positive: occurrence of the limiting substring counted from the start of the string <br>
 *                                             negative: occurrence of the limiting substring counted from the end of the string   <br>
 *                                             0:        the initial string is returned                                            <br>
 *                                             (default: 1 = the first occurrence)
 * @param  bool   $includeLimiter [optional] - whether to include the limiter in the returned result
 *                                             (default: no)
 * @param  string $onNotFound     [optional] - string to return if the specified occurrence of the limiter is not found
 *                                             (default: empty string)
 *
 * @return string
 *
 * @example
 * <pre>
 *  strRightFrom('abc_abc', 'c')     => '_abc'
 *  strRightFrom('abcabc',  'x')     => ''          // limiter not found
 *  strRightFrom('abc_abc', 'a',  2) => 'bc'
 *  strRightFrom('abc_abc', 'b', -2) => 'c_abc'
 * </pre>
 */
function strRightFrom(string $string, string $limiter, int $count=1, bool $includeLimiter=false, string $onNotFound=''): string {
    if (!strlen($limiter)) throw new InvalidValueException('Illegal limiting substring: "" (empty)');

    if ($count > 0) {
        $pos = -1;
        while ($count) {
            $offset = $pos + 1;
            $pos = strpos($string, $limiter, $offset);
            if ($pos === false) {                                   // not found
                return func_num_args() > 4 ? $onNotFound : '';
            }
            $count--;
        }
        $pos += strlen($limiter);
        $result = ($pos >= strlen($string)) ? '' : substr($string, $pos);
        if ($includeLimiter) {
            $result = $limiter.$result;
        }
        return $result;
    }

    if ($count < 0) {
        $len = strlen($string);
        $pos = $len;
        while ($count) {
            $offset = $pos - $len - 1;
            if ($offset < -$len) {                                  // not found
                return func_num_args() > 4 ? $onNotFound : '';
            }
            $pos = strrpos($string, $limiter, $offset);
            if ($pos === false) {                                   // not found
                return func_num_args() > 4 ? $onNotFound : '';
            }
            $count++;
        }
        $pos += strlen($limiter);
        $result = ($pos >= strlen($string)) ? '' : substr($string, $pos);
        if ($includeLimiter) {
            $result = $limiter.$result;
        }
        return $result;
    }

    // $count == 0
    return $string;
}


/**
 * Whether a string starts with a substring. If multiple prefixes are given, whether the string
 * starts with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $prefix                - one or more prefixes
 * @param  bool            $ignoreCase [optional] - default: no
 *
 * @return bool
 */
function strStartsWith(string $string, $prefix, bool $ignoreCase = false): bool {
    if (is_array($prefix)) {
        foreach ($prefix as $p) {
            if (strStartsWith($string, $p, $ignoreCase)) {
                return true;
            }
        }
        return false;
    }
    Assert::string($prefix, '$prefix');

    if (!strlen($string) || !strlen($prefix)) {
        return false;
    }
    if ($ignoreCase) {
        return (stripos($string, $prefix) === 0);
    }
    return (strpos($string, $prefix) === 0);
}


/**
 * Whether a string starts with a substring ignoring case differences. If multiple prefixes
 * are given, whether the string starts with one of them.
 *
 * @param  string          $string
 * @param  string|string[] $prefix - one or more prefixes
 *
 * @return bool
 */
function strStartsWithI(string $string, $prefix): bool  {
    return strStartsWith($string, $prefix, true);
}


/**
 * Interpret a string as a boolean.
 *
 * @param  string $string            - string
 * @param  bool   $strict [optional] - whether to apply strict interpretation rules:
 *                                     FALSE - returns TRUE only for "1", "true", "on" and "yes", and FALSE otherwise (default)
 *                                     TRUE  - as above but FALSE is returned only for "0", "false", "off" and "no", and NULL
 *                                             is returned for all other values
 *
 * @return ?bool - boolean value or NULL if the string does not represent a requested strict boolean value
 */
function strToBool(string $string, bool $strict = false): ?bool {
    $flags = 0;
    if ($strict) {
        if ($string === '') {               // PHP considers NULL and '' strict boolean values
            return null;
        }
        $flags = FILTER_NULL_ON_FAILURE;
    }
    return filter_var($string, FILTER_VALIDATE_BOOLEAN, $flags);
}


/**
 * Parse a string representing a date/time value and convert it to a Unix timestamp.
 *
 * @param  string          $string            - string to parse
 * @param  string|string[] $format [optional] - date/time format the string is required to match (default: 'Y-m-d')
 *                                              if an array the string must match at least one of the provided formats
 *
 * @return int|bool - Unix timestamp or FALSE if the string doesn't match the specified format(s)
 *
 * <pre>
 *  Supported format strings:
 *  'Y-m-d [H:i[:s]]'
 *  'Y.m.d [H:i[:s]]'
 *  'd.m.Y [H:i[:s]]'
 *  'd/m/Y [H:i[:s]]'
 * </pre>
 */
function strToTimestamp(string $string, $format = 'Y-m-d') {
    // TODO: rewrite and add strToDateTime()

    if (is_array($format)) {
        foreach ($format as $value) {
            $timestamp = strToTimestamp($string, $value);
            if (is_int($timestamp)) {
                return $timestamp;
            }
        }
        return false;
    }
    Assert::string($format, '$format');

    $year = $month = $day = $hour = $minute = $second = $m = null;

    if ($format == 'Y-m-d') {
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $string, $m)) return false;
        $year   = (int)$m[1];
        $month  = (int)$m[2];
        $day    = (int)$m[3];
        $hour   = 0;
        $minute = 0;
        $second = 0;
    }
    elseif ($format == 'Y-m-d H:i') {
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})$/', $string, $m)) return false;
        $year   = (int)$m[1];
        $month  = (int)$m[2];
        $day    = (int)$m[3];
        $hour   = (int)$m[4];
        $minute = (int)$m[5];
        $second = 0;
    }
    elseif ($format == 'Y-m-d H:i:s') {
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $string, $m)) return false;
        $year   = (int)$m[1];
        $month  = (int)$m[2];
        $day    = (int)$m[3];
        $hour   = (int)$m[4];
        $minute = (int)$m[5];
        $second = (int)$m[6];
    }
    elseif ($format == 'Y.m.d') {
        if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2})$/', $string, $m)) return false;
        $year   = (int)$m[1];
        $month  = (int)$m[2];
        $day    = (int)$m[3];
        $hour   = 0;
        $minute = 0;
        $second = 0;
    }
    elseif ($format == 'Y.m.d H:i') {
        if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2}):([0-9]{2})$/', $string, $m)) return false;
        $year   = (int)$m[1];
        $month  = (int)$m[2];
        $day    = (int)$m[3];
        $hour   = (int)$m[4];
        $minute = (int)$m[5];
        $second = 0;
    }
    elseif ($format == 'Y.m.d H:i:s') {
        if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $string, $m)) return false;
        $year   = (int)$m[1];
        $month  = (int)$m[2];
        $day    = (int)$m[3];
        $hour   = (int)$m[4];
        $minute = (int)$m[5];
        $second = (int)$m[6];
    }
    elseif ($format == 'd.m.Y') {
        if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/', $string, $m)) return false;
        $year   = (int)$m[3];
        $month  = (int)$m[2];
        $day    = (int)$m[1];
        $hour   = 0;
        $minute = 0;
        $second = 0;
    }
    elseif ($format == 'd.m.Y H:i') {
        if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4}) ([0-9]{2}):([0-9]{2})$/', $string, $m)) return false;
        $day    = (int)$m[1];
        $month  = (int)$m[2];
        $year   = (int)$m[3];
        $hour   = (int)$m[4];
        $minute = (int)$m[5];
        $second = 0;
    }
    elseif ($format == 'd.m.Y H:i:s') {
        if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $string, $m)) return false;
        $day    = (int)$m[1];
        $month  = (int)$m[2];
        $year   = (int)$m[3];
        $hour   = (int)$m[4];
        $minute = (int)$m[5];
        $second = (int)$m[6];
    }
    elseif ($format == 'd/m/Y') {
        if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $string, $m)) return false;
        $year   = (int)$m[3];
        $month  = (int)$m[2];
        $day    = (int)$m[1];
        $hour   = 0;
        $minute = 0;
        $second = 0;
    }
    elseif ($format == 'd/m/Y H:i') {
        if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2})$/', $string, $m)) return false;
        $day    = (int)$m[1];
        $month  = (int)$m[2];
        $year   = (int)$m[3];
        $hour   = (int)$m[4];
        $minute = (int)$m[5];
        $second = 0;
    }
    elseif ($format == 'd/m/Y H:i:s') {
        if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $string, $m)) return false;
        $day    = (int)$m[1];
        $month  = (int)$m[2];
        $year   = (int)$m[3];
        $hour   = (int)$m[4];
        $minute = (int)$m[5];
        $second = (int)$m[6];
    }
    else {
        return false;
    }

    if (checkdate($month, $day, $year) && $hour < 24 && $minute < 60 && $second < 60) {
        return mktime($hour, $minute, $second, $month, $day, $year);
    }
    return false;
}


/**
 * Execute a task in a synchronized way. Emulates the Java keyword "synchronized".
 *
 * @param  Closure $task             - task to execute (an anonymous function is implicitly casted)
 * @param  ?string $mutex [optional] - mutex identifier (default: the calling line of code)
 *
 * @return void
 */
function synchronized(Closure $task, ?string $mutex = null): void {
    if (!isset($mutex)) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file  = $trace[0]['file'] ?? '';
        $line  = $trace[0]['line'] ?? '';
        $mutex = "$file#$line";
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
 * Alias of gettype() for C enthusiasts.
 *
 * @param  mixed $var
 *
 * @return string
 */
function typeof($var): string {
    return gettype($var);
}


/**
 * Return a {@link Url} helper for the given URI. An URI starting with a slash "/" is interpreted as relative
 * to the application's base URI. An URI not starting with a slash is interpreted as relative to the application
 * {@link Module}'s base URI (the module the current request belongs to).
 *
 * Procedural equivalent of <tt>new \rosasurfer\ministruts\struts\url\Url($uri)</tt>.
 *
 * @param  string $uri
 *
 * @return Url
 */
function url(string $uri): Url {
    return new Url($uri);
}
