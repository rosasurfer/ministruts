<?php
namespace rosasurfer\core;

use rosasurfer\exception\RuntimeException;

use function rosasurfer\simpleClassName;
use function rosasurfer\strLeftTo;


/**
 * A trait adding {@link Object} behaviour to any class (i.e. common error detection capabilities).
 */
trait ObjectTrait {


    /**
     * Magic method catching other-wise fatal errors triggered by calls of non-existing instance methods.
     *
     * @param  string $method - name of the non-existing method
     * @param  array  $args   - arguments passed to the method call
     *
     * @throws RuntimeException
     */
    public function __call($method, array $args) {
        $trace = debug_backTrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $class = '{unknown_class}';

        foreach ($trace as $frame) {
            if (strToLower($frame['function']) !== '__call') {
                $class     = $frame['class'];
                $namespace = strLeftTo($class, '\\', -1, true, '');
                $baseName  = simpleClassName($class);
                $class     = strToLower($namespace).$baseName;
                break;
            }
        }
        throw new RuntimeException('Call of undefined method '.$class.'->'.$method.'()');
    }


    /**
     * Magic method catching other-wise fatal errors triggered by calls of non-existing static methods.
     *
     * @param  string $method - name of the non-existing method
     * @param  array  $args   - arguments passed to the method call
     *
     * @throws RuntimeException
     */
    public static function __callStatic($method, array $args) {
        // TODO: adjust error message according to stacktrace
        throw new RuntimeException('Call of undefined method '.static::class.'::'.$method.'()');
    }


    /**
     * Magic method catching:
     * - otherwise unnoticed write access to undefined properties
     * - fatal errors caused by write access to inaccessible properties
     *
     * @param  string $property - name of the undefined property
     * @param  mixed  $value    - passed property value
     *
     * @throws RuntimeException
     */
    public function __set($property, $value) {
        $trace = debug_backTrace(DEBUG_BACKTRACE_IGNORE_ARGS|DEBUG_BACKTRACE_PROVIDE_OBJECT);
        $class = get_class($trace[0]['object']);
        throw new RuntimeException('Undefined or inaccessible property '.$class.'::$'.$property);
    }
}
