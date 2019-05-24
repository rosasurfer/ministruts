<?php
namespace rosasurfer\core\assert;

use rosasurfer\core\StaticClass;
use function rosasurfer\strContains;


/**
 * Assert
 *
 * Efficient assertions to validate arguments.
 *
 * @method static void nullOrObject(  mixed $value, string $message = null)                      Ensure that the passed value is either NULL or an object.
 * @method static void nullOrResource(mixed $value, string $type = null, string $message = null) Ensure that the passed value is either NULL or a resource.
 */
class Assert extends StaticClass {


    /**
     * Ensure that the passed value is an array.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function isArray($value, $message = null, ...$args) {
        if (!is_array($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'array', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is a boolean.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function bool($value, $message = null, ...$args) {
        if (!is_bool($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'bool', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is an integer.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function int($value, $message = null, ...$args) {
        if (!is_int($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'int', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is either an integer or a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function intOrFloat($value, $message = null, ...$args) {
        if (!is_int($value) && !is_float($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'int or float', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is either an integer or a string.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function intOrString($value, $message = null, ...$args) {
        if (!is_int($value) && !is_string($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'int or string', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function float($value, $message = null, ...$args) {
        if (!is_float($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'float', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is a string.
     *
     * @param  mixed    $value
     * @param  string   $message [optional]
     * @param  array ...$args    [optional] - additional message arguments
     */
    public static function string($value, $message = null, ...$args) {
        if (!is_string($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'string', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is a scalar.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function scalar($value, $message = null, ...$args) {
        if (!is_scalar($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'scalar', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is a resource of the specified type.
     *
     * @param  mixed    $value
     * @param  string   $type    [optional]
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function resource($value, $type = null, $message = null, ...$args) {
        if (!is_resource($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'resource', $message, $args));
        }
        if (isset($type) && get_resource_type($value) != $type) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, $type.' resource', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is an object.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function object($value, $message = null, ...$args) {
        if (!is_object($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'object', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is a throwable object.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function throwable($value, $message = null, ...$args) {
        if (PHP_VERSION_ID < 70000) {
            if (!$value instanceof \Exception) {
                throw new IllegalTypeException(static::illegalTypeMessage($value, '\Exception', $message, $args));
            }
        }
        else {
            if (!$value instanceof \Throwable) {
                throw new IllegalTypeException(static::illegalTypeMessage($value, '\Throwable', $message, $args));
            }
        }
    }


    /**
     * Ensure that the passed class has a method.
     *
     * @param  mixed    $classOrObject
     * @param  string   $method
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function methodExists($classOrObject, $method, $message = null, ...$args) {
        if (!method_exists($classOrObject, $method)) {
            if      (is_string($classOrObject)) $value = $classOrObject;
            else if (is_object($classOrObject)) $value = get_class($classOrObject);
            else                                $value = static::valueToStr($classOrObject);
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'object with method "'.$method.'"()', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is NULL.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function null($value, $message = null) {
        if ($value !== null) {
            $message = sprintf($message ?: 'Invalid value: %s', static::valueToStr($value)).' (null expected)';
            throw new InvalidArgumentException($message);
        }
    }


    /**
     * Ensure that the passed value is either NULL or an array.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function nullOrArray($value, $message = null, ...$args) {
        if (isset($value) && !is_array($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or array', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is either NULL or a boolean.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function nullOrBool($value, $message = null, ...$args) {
        if (isset($value) && !is_bool($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or bool', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is either NULL or an integer.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function nullOrInt($value, $message = null, ...$args) {
        if (isset($value) && !is_int($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or int', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is either NULL, an integer or a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function nullOrIntOrFloat($value, $message = null, ...$args) {
        if (isset($value) && (!is_int($value) && !is_float($value))) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null, int or float', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is either NULL, an integer or a string.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function nullOrIntOrString($value, $message = null, ...$args) {
        if (isset($value) && (!is_int($value) && !is_string($value))) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null, int or string', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is either NULL or a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function nullOrFloat($value, $message = null, ...$args) {
        if (isset($value) && !is_float($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or float', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is either NULL or a scalar.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function nullOrScalar($value, $message = null, ...$args) {
        if (isset($value) && !is_scalar($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or scalar', $message, $args));
        }
    }


    /**
     * Ensure that the passed value is either NULL or a string.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or custom user message
     * @param  array ...$args    [optional] - additional user message arguments
     */
    public static function nullOrString($value, $message = null, ...$args) {
        if (isset($value) && !is_string($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or string', $message, $args));
        }
    }


    /**
     * Compose a "type assertion failed" error message.
     *
     * @param  mixed  $value        - checked variable
     * @param  string $expectedType - expected variable type
     * @param  string $message      - variabe identifier or custom user message
     * @param  array  $args         - additional user message arguments
     *
     * @return string - generated error message
     */
    protected static function illegalTypeMessage($value, $expectedType, $message, array $args) {
        if (is_string($message) && strContains($message, '%')) {
            $message = sprintf($message, ...$args);
        }
        else {
            $message = isset($message) ? ' of '.$message : '';
            $message = sprintf('Illegal type%s: %s (%s expected)', $message, static::typeToStr($value), $expectedType);
        }
        return $message;
    }


    /**
     * Return a human-readable version of a variable's type.
     *
     * @param  mixed  $value
     *
     * @return string
     */
    protected static function typeToStr($value) {
        return is_object($value) ? get_class($value) : gettype($value);
    }


    /**
     * Return a human-readable version of a variable.
     *
     * @param  mixed  $value
     *
     * @return string
     */
    protected static function valueToStr($value) {
        if ($value === null )    return '(null)';
        if ($value === true )    return '(true)';
        if ($value === false)    return '(false)';
        if (is_array   ($value)) return 'array';
        if (is_resource($value)) return 'resource';
        if (is_string  ($value)) return '"'.$value.'"';
        if (is_object  ($value)) {
            if (method_exists($value, '__toString'))
                return get_class($value).' {'.$value.'}';
            return get_class($value);
        }
        return (string) $value;
    }


    /**
     * Intercept calls to virtual nullOr*() assertions.
     *
     * @param  string $method - name of the virtual method
     * @param  array  $args   - arguments passed to the method call
     */
    public static function __callStatic($method, array $args) {
        if (substr($method, 0, 6) == 'nullOr') {
            if (isset($args[0])) {
                $method = substr($method, 6);
                static::$method(...$args);
            }
            return;
        }
        // pass on all other calls
        parent::__callStatic($method, $args);
    }
}
