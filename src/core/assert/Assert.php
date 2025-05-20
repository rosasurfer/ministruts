<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\assert;

use Throwable;
use rosasurfer\ministruts\core\StaticClass;

use function rosasurfer\ministruts\strContains;

/**
 * Assert
 *
 * Efficient assertions to validate arguments.
 */
class Assert extends StaticClass {

    /**
     * Ensure that the passed value is NULL.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is null ? true : false)
     * @phpstan-assert null $value
     */
    public static function null($value, string $message = '', ...$args): bool {
        if (isset($value)) {
            throw new InvalidValueException("Not NULL: $message");
        }
        return true;
    }


    /**
     * Ensure that the passed value is considered "empty".
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is empty ? true : false)
     * @phpstan-assert empty $value
     */
    public static function empty($value, string $message = '', ...$args): bool {
        if (!empty($value)) {
            throw new InvalidValueException("Not empty: $message");
        }
        return true;
    }


    /**
     * Ensure that the passed value is considered "non-empty".
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is empty ? false : true)
     * @phpstan-assert !empty $value
     */
    public static function notEmpty($value, string $message = '', ...$args): bool {
        if (empty($value)) {
            throw new InvalidValueException("Empty: $message");
        }
        return true;
    }


    /**
     * Ensure that two values are considered "equal" (weak comparison: not necessarily identical).
     *
     * @param  mixed    $value1
     * @param  mixed    $value2
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return bool
     */
    public static function equal($value1, $value2, string $message = '', ...$args): bool {
        if ($value1 != $value2) {
            throw new InvalidValueException("Not equal: $message");
        }
        return true;
    }


    /**
     * Ensure that two values are considered "not equal" (weak comparison).
     *
     * @param  mixed    $value1
     * @param  mixed    $value2
     * @param  string   $message [optional] - value identifier or description
     * @param  mixed ...$args    [optional] - additional description arguments
     *
     * @return bool
     */
    public static function notEqual($value1, $value2, string $message = '', ...$args): bool {
        if ($value1 == $value2) {
            throw new InvalidValueException("Not different: $message");
        }
        return true;
    }


    /**
     * Ensure that the passed value is an array.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is array ? true : false)
     * @phpstan-assert array<mixed> $value
     */
    public static function isArray($value, string $message = '', ...$args): bool {
        if (!is_array($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'array', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a boolean.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is bool ? true : false)
     * @phpstan-assert bool $value
     */
    public static function bool($value, string $message = '', ...$args): bool {
        if (!is_bool($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'bool', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is boolean TRUE.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is true ? true : false)
     * @phpstan-assert true $value
     */
    public static function true($value, string $message = '', ...$args): bool {
        if ($value !== true) {
            throw new InvalidValueException("Not true: $message");
        }
        return true;
    }


    /**
     * Ensure that the passed value is boolean FALSE.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is false ? true : false)
     * @phpstan-assert false $value
     */
    public static function false($value, string $message = '', ...$args): bool {
        if ($value !== false) {
            throw new InvalidValueException("Not false: $message");
        }
        return true;
    }


    /**
     * Ensure that the passed value is an integer.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is int ? true : false)
     * @phpstan-assert int $value
     */
    public static function int($value, string $message = '', ...$args): bool {
        if (!is_int($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'int', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a float.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is float ? true : false)
     * @phpstan-assert float $value
     */
    public static function float($value, string $message = '', ...$args): bool {
        if (!is_float($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'float', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a string.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is string ? true : false)
     * @phpstan-assert string $value
     */
    public static function string($value, string $message = '', ...$args): bool {
        if (!is_string($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'string', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a non-empty string.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is non-empty-string ? true : false)
     * @phpstan-assert non-empty-string $value
     */
    public static function stringNotEmpty($value, string $message = '', ...$args): bool {
        static::string($value, $message, ...$args);
        static::notEqual($value, '', $message, ...$args);
        return true;
    }


    /**
     * Ensure that the passed value is a scalar.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is scalar ? true : false)
     * @phpstan-assert scalar $value
     */
    public static function scalar($value, string $message = '', ...$args): bool {
        if (!is_scalar($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'scalar', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is an object.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is object ? true : false)
     * @phpstan-assert object $value
     */
    public static function object($value, string $message = '', ...$args): bool {
        if (!is_object($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'object', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is an instance of the specified type.
     *
     * @param  mixed  $value
     * @param  string $class
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @template       T of object
     * @phpstan-param  class-string<T> $class
     * @phpstan-return ($value is T ? true : false)
     * @phpstan-assert T $value
     */
    public static function instanceOf($value, string $class, string $message = '', ...$args): bool {
        if (!$value instanceof $class) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, $class, $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is a throwable object.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is Throwable ? true : false)
     * @phpstan-assert Throwable $value
     */
    public static function throwable($value, string $message = '', ...$args): bool {
        if (!$value instanceof Throwable) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'Throwable', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed object or class has a method.
     *
     * @param  mixed  $objectOrClass
     * @param  string $method
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool - whether the assertion is TRUE
     */
    public static function hasMethod($objectOrClass, string $method, string $message = '', ...$args): bool {
        if (is_string($objectOrClass) || is_object($objectOrClass)) {
            if (method_exists($objectOrClass, $method)) {
                return true;
            }
        }
        $value = static::valueToStr($objectOrClass);
        throw new InvalidTypeException(static::illegalTypeMessage($value, "object or class with method \"$method()\"", $message, $args));
    }


    /**
     * Ensure that the passed value is a resource.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is resource ? true : false)
     * @phpstan-assert resource $value
     */
    public static function resource($value, string $message = '', ...$args): bool {
        if (!is_resource($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'resource', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or an array.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is ?array ? true : false)
     * @phpstan-assert ?array<mixed> $value
     */
    public static function nullOrArray($value, string $message = '', ...$args): bool {
        if (isset($value) && !is_array($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or array', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a boolean.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is ?bool ? true : false)
     * @phpstan-assert ?bool $value
     */
    public static function nullOrBool($value, string $message = '', ...$args): bool {
        if (isset($value) && !is_bool($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or bool', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or an integer.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is ?int ? true : false)
     * @phpstan-assert ?int $value
     */
    public static function nullOrInt($value, string $message = '', ...$args): bool {
        if (isset($value) && !is_int($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or int', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a float.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is ?float ? true : false)
     * @phpstan-assert ?float $value
     */
    public static function nullOrFloat($value, string $message = '', ...$args): bool {
        if (isset($value) && !is_float($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or float', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a string.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is ?string ? true : false)
     * @phpstan-assert ?string $value
     */
    public static function nullOrString($value, string $message = '', ...$args): bool {
        if (isset($value) && !is_string($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or string', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a scalar.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is ?scalar ? true : false)
     * @phpstan-assert ?scalar $value
     */
    public static function nullOrScalar($value, string $message = '', ...$args): bool {
        if (isset($value) && !is_scalar($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or scalar', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or an object.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is ?object ? true : false)
     * @phpstan-assert ?object $value
     */
    public static function nullOrObject($value, string $message = '', ...$args): bool {
        if (isset($value) && !is_object($value)) {
            throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or object', $message, $args));
        }
        return true;
    }


    /**
     * Ensure that the passed value is either NULL or a resource.
     *
     * @param  mixed  $value
     * @param  string $message [optional] - value description
     * @param  scalar ...$args [optional] - additional message arguments
     *
     * @return bool
     *
     * @phpstan-return ($value is ?resource ? true : false)
     * @phpstan-assert ?resource $value
     */
    public static function nullOrResource($value, string $message = '', ...$args): bool {
        if (isset($value)) {
            if (!is_resource($value)) {
                throw new InvalidTypeException(static::illegalTypeMessage($value, 'null or resource', $message, $args));
            }
        }
        return true;
    }


    /**
     * Compose a "type assertion failed" error message.
     *
     * @param  mixed    $value        - checked variable
     * @param  string   $expectedType - expected variable type
     * @param  string   $message      - variabe identifier or description
     * @param  scalar[] $args         - additional description arguments
     *
     * @return string - generated error message
     */
    protected static function illegalTypeMessage($value, string $expectedType, string $message, array $args): string {
        $message = (string) $message;
        if (strlen($message)) {
            if (strContains($message, '%')) {
                $message = sprintf($message, ...$args);
            }
            if (!ctype_upper($message[0])) {                // ignore multi-byte or special UTF-8 chars
                if ($message[0] == '$') {
                    $message = 'argument '.$message;
                }
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
     * @param  mixed $value
     *
     * @return string
     */
    protected static function typeToStr($value): string {
        return is_object($value) ? get_class($value) : gettype($value);
    }


    /**
     * Return a more readable version of a variable.
     *
     * @param  mixed $value
     *
     * @return string
     */
    protected static function valueToStr($value): string {
        if ($value === null )    return '(null)';
        if ($value === true )    return '(true)';
        if ($value === false)    return '(false)';
        if (is_array   ($value)) return 'array';
        if (is_resource($value)) return 'resource';
        if (is_string  ($value)) return '"'.$value.'"';
        if (is_object  ($value)) {
            if (method_exists($value, '__toString')) {
                return get_class($value).' {'.$value.'}';
            }
            return get_class($value);
        }
        /** @var int|float $value */
        $value = $value;
        return (string) $value;
    }
}
