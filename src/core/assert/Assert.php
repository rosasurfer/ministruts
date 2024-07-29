<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\assert;

use rosasurfer\ministruts\core\StaticClass;

use function rosasurfer\ministruts\strContains;


/**
 * Assert
 *
 * Efficient assertions to validate arguments.
 */
class Assert extends StaticClass {


    /**
     * Ensure that the passed value is an array.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function isArray($value, $message = '', ...$args) {
        if (!is_array($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'array', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a boolean.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function bool($value, $message = '', ...$args) {
        if (!is_bool($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'bool', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is an integer.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function int($value, $message = '', ...$args) {
        if (!is_int($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'int', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function float($value, $message = '', ...$args) {
        if (!is_float($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'float', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a string.
     *
     * @param  mixed    $value
     * @param  string   $message [optional]
     * @param  array ...$args    [optional] - additional message arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function string($value, $message = '', ...$args) {
        if (!is_string($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'string', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a scalar.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function scalar($value, $message = '', ...$args) {
        if (!is_scalar($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'scalar', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is an object.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function object($value, $message = '', ...$args) {
        if (!is_object($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'object', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a throwable object.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function throwable($value, $message = '', ...$args) {
        if (!$value instanceof \Throwable) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, '\Throwable', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed object or class has a method.
     *
     * @param  mixed    $objectOrClass
     * @param  string   $method
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function hasMethod($objectOrClass, $method, $message = '', ...$args) {
        if (!method_exists($objectOrClass, $method)) {
            if      (is_string($objectOrClass)) $value = $objectOrClass;
            else if (is_object($objectOrClass)) $value = get_class($objectOrClass);
            else                                $value = static::valueToStr($objectOrClass);
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'object or class with method "'.$method.'"()', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a resource of the specified type.
     *
     * @param  mixed    $value
     * @param  ?string  $type    [optional]
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function resource($value, $type = null, $message = '', ...$args) {
        if (!is_resource($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'resource', $message, $args));
        }
        if (isset($type) && get_resource_type($value) != $type) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, $type.' resource', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or an array.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function nullOrArray($value, $message = '', ...$args) {
        if (isset($value) && !is_array($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or array', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a boolean.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function nullOrBool($value, $message = '', ...$args) {
        if (isset($value) && !is_bool($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or bool', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or an integer.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function nullOrInt($value, $message = '', ...$args) {
        if (isset($value) && !is_int($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or int', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function nullOrFloat($value, $message = '', ...$args) {
        if (isset($value) && !is_float($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or float', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a string.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function nullOrString($value, $message = '', ...$args) {
        if (isset($value) && !is_string($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or string', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a scalar.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function nullOrScalar($value, $message = '', ...$args) {
        if (isset($value) && !is_scalar($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or scalar', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or an object.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function nullOrObject($value, $message = '', ...$args) {
        if (isset($value) && !is_object($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or object', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a resource of the specified type.
     *
     * @param  mixed    $value
     * @param  ?string  $type    [optional]
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function nullOrResource($value, $type = null, $message = '', ...$args) {
        if (isset($value)) {
            if (!is_resource($value)) {
                throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or resource', $message, $args));
            }
            if (isset($type) && get_resource_type($value) != $type) {
                throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or '.$type.' resource', $message, $args));
            }
        }
        return true;
    }


    /**
     * Compose a "type assertion failed" error message.
     *
     * @param  mixed  $value        - checked variable
     * @param  string $expectedType - expected variable type
     * @param  string $message      - variabe identifier or custom value description
     * @param  array  $args         - additional description arguments
     *
     * @return string - generated error message
     */
    protected static function illegalTypeMessage($value, $expectedType, $message, array $args) {
        $message = (string) $message;
        if (strlen($message)) {
            if (strContains($message, '%')) {
                $message = sprintf($message, ...$args);
            }
            if (!ctype_upper($message[0])) {                // ignore multi-byte or special UTF-8 chars
                if ($message[0] == '$')
                    $message = 'argument '.$message;
                $message = sprintf('Illegal type %s of %s (%s expected)', static::typeToStr($value), $message, $expectedType);
            }
        }
        else {
            $message = sprintf('Illegal type %s (%s expected)', static::typeToStr($value), $expectedType);
        }
        return $message;
    }


    /**
     * Return a more readable version of a variable type.
     *
     * @param  mixed  $value
     *
     * @return string
     */
    protected static function typeToStr($value) {
        return is_object($value) ? get_class($value) : gettype($value);
    }


    /**
     * Return a more readable version of a variable.
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
}
