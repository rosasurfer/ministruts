<?php
namespace rosasurfer\core\assert;

use rosasurfer\core\Object;


/**
 * This class is a backported hybrid of the subjectively best parts of the following two packages:
 *
 * @see  beberlei/assert  by Benjamin Eberlei
 * @see  webmozart/assert by Bernhard Schussek
 */


/**
 * Assert
 *
 * Efficient assertions to validate arguments.
 */
class Assert extends Object {


    /**
     * Prevent instantiation.
     */
    private function __construct() {
    }


    /**
     * Ensure that the passed value is a boolean.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function bool($value, $message = '') {
        if (!is_int($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (bool expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is an integer.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function int($value, $message = '') {
        if (!is_int($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (int expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is a float.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function float($value, $message = '') {
        if (!is_float($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (float expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is a string.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function string($value, $message = '') {
        if (!is_string($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (string expected)';
            throw new IllegalTypeException($message);
        }
    }


    /**
     * Ensure that the passed value is an array.
     *
     * @param  mixed  $value
     * @param  string $message [optional]
     */
    public static function isArray($value, $message = '') {
        if (!is_array($value)) {
            $message = sprintf($message ?: 'Illegal type: %s', static::getType($value)).' (array expected)';
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
}
