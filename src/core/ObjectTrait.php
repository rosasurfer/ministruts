<?php
namespace rosasurfer\core;

use rosasurfer\di\DiAwareTrait;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\strLeftTo;


/**
 * A trait providing dependency injection awareness and default error handling functionality.
 */
trait ObjectTrait {

    use DiAwareTrait;


    /**
     * Magic method. Catches other-wise fatal errors triggered by calls to non-existing instance methods.
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
                $name      = baseName($class);
                $class     = strToLower($namespace).$name;
                break;
            }
        }
        throw new RuntimeException('Call of undefined method '.$class.'->'.$method.'()');
    }


    /**
     * Magic method. Catches other-wise fatal errors triggered by calls to non-existing static methods.
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
     * Magic method. Catches the other-wise unnoticed setting of undefined class properties.
     *
     * @param  string $property - name of the undefined property
     * @param  mixed  $value    - passed value for the undefined property
     *
     * @throws RuntimeException
     */
    public function __set($property, $value) {
        $trace = debug_backTrace(DEBUG_BACKTRACE_IGNORE_ARGS|DEBUG_BACKTRACE_PROVIDE_OBJECT);
        $class = get_class($trace[0]['object']);
        throw new RuntimeException('Undefined class variable '.$class.'::$'.$property);
    }


    /**
     * Return a human-readable version of the instance.
     *
     * @_param  int $levels - how many levels of an object graph to recurse into
     *                        (default: all)
     * @return string
     */
    public function __toString(/*$levels=PHP_INT_MAX*/) {
        /*
        // TODO
        if (func_num_args()) {
            $levels = func_get_arg(0);
            if ($levels != PHP_INT_MAX) {
            }
        }
        */
        return print_r($this, true);
    }
}
