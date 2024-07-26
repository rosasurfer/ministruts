<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core;

use rosasurfer\ministruts\core\exception\RuntimeException;

use function rosasurfer\ministruts\simpleClassName;
use function rosasurfer\ministruts\strLeftTo;


/**
 * A trait capable of adding {@link CObject} behavior to any class. Used to add error detection features.
 */
trait ObjectTrait {


    /**
     * A method catching otherwise fatal errors triggered by calls of non-existing or inaccessible instance methods.
     *
     * @param  string $method - name of the called method
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
     * Method catching otherwise fatal errors triggered by calls of non-existing or inaccessible static methods.
     *
     * @param  string $method - name of the called method
     * @param  array  $args   - arguments passed to the method call
     *
     * @throws RuntimeException
     */
    public static function __callStatic($method, array $args) {
        // TODO: adjust error message according to stacktrace
        throw new RuntimeException('Call of undefined or inaccessible method '.static::class.'::'.$method.'()');
    }


    /**
     * Method catching otherwise unnoticed write access to undefined instance properties.
     * Method catching otherwise fatal errors caused by write access to inaccessible instance properties.
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
