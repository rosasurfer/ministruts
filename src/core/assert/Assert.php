<?php
namespace rosasurfer\core\assert;

use Throwable;

use rosasurfer\core\StaticClass;

use function rosasurfer\strContains;


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
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is array ? true : false)
     *
     * @phpstan-assert array $value
     */
    public static function isArray($value, $message = '', ...$args) {
        if (!is_array($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'array', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a boolean.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is bool ? true : false)
     *
     * @phpstan-assert bool $value
     */
    public static function bool($value, $message = '', ...$args) {
        if (!is_bool($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'bool', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is boolean TRUE.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is true ? true : false)
     *
     * @phpstan-assert true $value
     */
    public static function true($value, $message = '', ...$args) {
        if ($value !== true) {
            throw new InvalidArgumentException("Not true: $message");
        }
        return true;
    }


    /**
     * Ensure that the passed value is boolean FALSE.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is false ? true : false)
     *
     * @phpstan-assert false $value
     */
    public static function false($value, $message = '', ...$args) {
        if ($value !== false) {
            throw new InvalidArgumentException("Not false: $message");
        }
        return true;
    }


    /**
     * Ensure that the passed value is an integer.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is int ? true : false)
     *
     * @phpstan-assert int $value
     */
    public static function int($value, $message = '', ...$args) {
        if (!is_int($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'int', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is float ? true : false)
     *
     * @phpstan-assert float $value
     */
    public static function float($value, $message = '', ...$args) {
        if (!is_float($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'float', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a string.
     *
     * @param  mixed    $value
     * @param  string   $message [optional]
     * @param  mixed ...$args    [optional] - additional message arguments
     *
     * @return ($value is string ? true : false)
     *
     * @phpstan-assert string $value
     */
    public static function string($value, $message = '', ...$args) {
        if (!is_string($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'string', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a scalar.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is scalar ? true : false)
     *
     * @phpstan-assert scalar $value
     */
    public static function scalar($value, $message = '', ...$args) {
        if (!is_scalar($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'scalar', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is an object.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is object ? true : false)
     *
     * @phpstan-assert object $value
     */
    public static function object($value, $message = '', ...$args) {
        if (!is_object($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'object', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is an instance of the specified type.
     *
     * @param  mixed    $value
     * @param  string   $class
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional message arguments
     *
     * @return bool
     *
     * @template        T of object
     * @phpstan-param   class-string<T> $class
     * @phpstan-return  ($value is T ? true : false)
     * @phpstan-assert  T $value
     */
    public static function instanceOf($value, string $class, string $message = '', ...$args) {
        if (!$value instanceof $class) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, $class, $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a throwable object.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is Throwable ? true : false)
     *
     * @phpstan-assert Throwable $value
     */
    public static function throwable($value, $message = '', ...$args) {
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
        return true;
    }


    /**
     * Ensure that the passed object or class has a method.
     *
     * @param  mixed    $objectOrClass
     * @param  string   $method
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function hasMethod($objectOrClass, $method, $message = '', ...$args) {
        if (!method_exists($objectOrClass, $method)) {
            if      (is_string($objectOrClass)) $value = $objectOrClass;
            else if (is_object($objectOrClass)) $value = get_class($objectOrClass);
            else                                $value = static::valueToStr($objectOrClass);
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'object or class with method "'.$method.'"()', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a resource of the specified type.
     *
     * @param  mixed    $value
     * @param  ?string  $type    [optional]
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is resource ? true : false)
     *
     * @phpstan-assert resource $value
     */
    public static function resource($value, $type = null, $message = '', ...$args) {
        if (!is_resource($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'resource', $message, $args));
        }
        if (isset($type) && get_resource_type($value) != $type) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, $type.' resource', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or an array.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is ?array ? true : false)
     *
     * @phpstan-assert ?array $value
     */
    public static function nullOrArray($value, $message = '', ...$args) {
        if (isset($value) && !is_array($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or array', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a boolean.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is ?bool ? true : false)
     *
     * @phpstan-assert ?bool $value
     */
    public static function nullOrBool($value, $message = '', ...$args) {
        if (isset($value) && !is_bool($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or bool', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or an integer.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is ?int ? true : false)
     *
     * @phpstan-assert ?int $value
     */
    public static function nullOrInt($value, $message = '', ...$args) {
        if (isset($value) && !is_int($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or int', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is ?float ? true : false)
     *
     * @phpstan-assert ?float $value
     */
    public static function nullOrFloat($value, $message = '', ...$args) {
        if (isset($value) && !is_float($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or float', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a string.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is ?string ? true : false)
     *
     * @phpstan-assert ?string $value
     */
    public static function nullOrString($value, $message = '', ...$args) {
        if (isset($value) && !is_string($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or string', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a scalar.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is ?scalar ? true : false)
     *
     * @phpstan-assert ?scalar $value
     */
    public static function nullOrScalar($value, $message = '', ...$args) {
        if (isset($value) && !is_scalar($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or scalar', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or an object.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is ?object ? true : false)
     *
     * @phpstan-assert ?object $value
     */
    public static function nullOrObject($value, $message = '', ...$args) {
        if (isset($value) && !is_object($value)) {
            throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or object', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a resource of the specified type.
     *
     * @param  mixed    $value
     * @param  ?string  $type    [optional]
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return ($value is ?resource ? true : false)
     *
     * @phpstan-assert ?resource $value
     */
    public static function nullOrResource($value, $type = null, $message = '', ...$args) {
        if (isset($value)) {
            if (!is_resource($value)) {
                throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or resource', $message, $args));
            }
            if (isset($type) && get_resource_type($value) != $type) {
                throw new IllegalTypeException(static::illegalTypeMessage($value, 'null or '.$type.' resource', $message, $args));
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
}
