<?php
namespace rosasurfer\core\assert;

use rosasurfer\core\StaticClass;


/**
 * Assert
 *
 * Efficient assertions to validate arguments.
 *
 * @method static void nullOrBool(        mixed $value, string $message = null)                      Ensure that the passed value is either NULL or a boolean.
 * @method static void nullOrFloat(       mixed $value, string $message = null)                      Ensure that the passed value is either NULL or a float.
 * @method static void nullOrInt(         mixed $value, string $message = null)                      Ensure that the passed value is either NULL or an integer
 * @method static void nullOrIntOrFloat(  mixed $value, string $message = null)                      Ensure that the passed value is either NULL, an integer or a float.
 * @method static void nullOrIntOrString( mixed $value, string $message = null)                      Ensure that the passed value is either NULL, an integer or a string.
 * @method static void nullOrObject(      mixed $value, string $message = null)                      Ensure that the passed value is either NULL or an object.
 * @method static void nullOrResource(    mixed $value, string $type = null, string $message = null) Ensure that the passed value is either NULL or a resource.
 * @method static void nullOrScalar(      mixed $value, string $message = null)                      Ensure that the passed value is either NULL or a scalar.
 * @method static void nullOrString(      mixed $value, string $message = null)                      Ensure that the passed value is either NULL or a string.
 */
class Assert extends StaticClass {


    /**
     * Ensure that the passed value is a boolean.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function bool($value, $message = null) {
        if (!is_int($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (bool expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is a float.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function float($value, $message = null) {
        if (!is_float($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (float expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is an integer.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function int($value, $message = null) {
        if (!is_int($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (int expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is either an integer or a float.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function intOrFloat($value, $message = null) {
        if (!is_int($value) && !is_float($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (int or float expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is either an integer or a string.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function intOrString($value, $message = null) {
        if (!is_int($value) && !is_string($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (int or string expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is an array.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function isArray($value, $message = null) {
        if (!is_array($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (array expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed class has a method.
     *
     * @param  mixed  $classOrObject
     * @param  string $method
     * @param  string $message [optional]
     */
    public static function methodExists($classOrObject, $method, $message = null) {
        if (!method_exists($classOrObject, $method)) {
            if      (is_string($classOrObject)) $value = $classOrObject;
            else if (is_object($classOrObject)) $value = get_class($classOrObject);
            else                                $value = static::valueToString($classOrObject);
            $message = sprintf($message ?: 'Illegal type: %s', $value).' (object with method "'.$method.'"() expected)';
            throw new IllegalTypeException($message);
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
            $message = sprintf($message ?: 'Invalid value: %s', static::valueToString($value)).' (NULL expected)';
            throw new InvalidArgumentException($message);
        }
    }


    /**
     * Ensure that the passed value is either NULL or an array.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function nullOrArray($value, $message = null) {
        if (isset($value) && !is_array($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (NULL or array expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is an object.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function object($value, $message = null) {
        if (!is_object($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (object expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is a resource of the specified type.
     *
     * @param  mixed  $value
     * @param  string $type    [optional]
     * @param  string $message [optional]
     */
    public static function resource($value, $type = null, $message = null) {
        if (!is_resource($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (resource expected)';
            throw new IllegalTypeException($message);
        }
        if (isset($type) && get_resource_type($value) != $type) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' ('.$type.' resource expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is a scalar.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function scalar($value, $message = null) {
        if (!is_scalar($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (scalar expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is a string.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function string($value, $message = null) {
        if (!is_string($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (string expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Return the type of a variable.
     *
     * @param  mixed  $value
     *
     * @return string
     */
    protected static function getType($value) {
        return is_object($value) ? get_class($value) : gettype($value);
    }


    /**
     * Return a human-readable version of a variable.
     *
     * @param  mixed  $value
     *
     * @return string
     */
    protected static function valueToString($value) {
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
