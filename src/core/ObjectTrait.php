<?php
namespace rosasurfer\core;

use rosasurfer\core\exception\RuntimeException;

use function rosasurfer\simpleClassName;
use function rosasurfer\strLeftTo;


/**
 * A trait adding {@link CObject} behaviour to any class (i.e. common error detection capabilities).
 */
trait ObjectTrait {


    /**
     * Handle calls of undefined or inaccessible instance methods. Prevents triggering of otherwise fatal script errors.
     *
     * @param  string $method - name of the undefined or inaccessible method
     * @param  array  $args   - arguments passed to the method call
     *
     * @throws RuntimeException
     */
    public function __call($method, array $args) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $class = '{unknown_class}';

        foreach ($trace as $frame) {
            if (strtolower($frame['function']) !== '__call') {
                $class     = $frame['class'];
                $namespace = strLeftTo($class, '\\', -1, true, '');
                $basename  = simpleClassName($class);
                $class     = strtolower($namespace).$basename;
                break;
            }
        }
        throw new RuntimeException('Call of undefined or inaccessible method '.$class.'->'.$method.'()');
    }


    /**
     * Handle calls of undefined or inaccessible static methods. Prevents triggering of otherwise fatal script errors.
     *
     * @param  string $method - name of the undefined or inaccessible method
     * @param  array  $args   - arguments passed to the method call
     *
     * @throws RuntimeException
     */
    public static function __callStatic($method, array $args) {
        // TODO: adjust error message according to stacktrace
        throw new RuntimeException('Call of undefined or inaccessible method '.static::class.'::'.$method.'()');
    }


    /**
     * Method catching otherwise unnoticed write access to undefined properties.
     * Method catching otherwise fatal errors caused by write access to inaccessible properties.
     *
     * @param  string $property - name of the undefined property
     * @param  mixed  $value    - passed property value
     *
     * @throws RuntimeException
     */
    public function __set($property, $value) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS|DEBUG_BACKTRACE_PROVIDE_OBJECT);
        $class = get_class($trace[0]['object']);
        throw new RuntimeException('Undefined or inaccessible property '.$class.'::$'.$property);
    }
}
