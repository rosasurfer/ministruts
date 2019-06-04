<?php
namespace rosasurfer\core\assert;

use function rosasurfer\strContains;


/**
 * A trait providing assertions-specific helpers to a class.
 *
 * It modifies the exception's call stack to point to the failed assertion.
 */
trait FailedAssertionTrait {


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
