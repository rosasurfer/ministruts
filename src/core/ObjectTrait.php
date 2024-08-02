<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core;

use rosasurfer\ministruts\core\exception\RuntimeException;

use function rosasurfer\ministruts\simpleClassName;
use function rosasurfer\ministruts\strLeftTo;
use rosasurfer\ministruts\core\error\ErrorHandler;


/**
 * A trait capable of adding {@link CObject} behavior to any class. Used to add error detection features.
 */
trait ObjectTrait {


    /**
     * Method signaling possibly unnoticed read access to undefined instance properties.
     *
     * @param  string $property - property name
     *
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function __get(string $property) {
        $ex = new RuntimeException('Read access to undefined property '.static::class.'::$'.$property);
        ErrorHandler::shiftStackFramesByMethod($ex, __FUNCTION__);
        throw $ex;
    }


    /**
     * Method signaling otherwise unnoticed write access to undefined instance properties.
     *
     * @param  string $property - property name
     * @param  mixed  $value    - property value
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public function __set(string $property, $value): void {
        $ex = new RuntimeException('Write access to undefined property '.static::class.'::$'.$property);
        ErrorHandler::shiftStackFramesByMethod($ex, __FUNCTION__);
        throw $ex;
    }


    /**
     * Method catching otherwise fatal errors triggered by calls of non-existing or inaccessible instance methods.
     *
     * @param  string  $method - name of the called method
     * @param  mixed[] $args   - arguments passed to the method call
     *
     * @return mixed
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
     * @param  string  $method - name of the called method
     * @param  mixed[] $args   - arguments passed to the method call
     *
     * @return never
     *
     * @throws RuntimeException
     */
    public static function __callStatic($method, array $args) {
        // TODO: adjust error message according to stacktrace
        throw new RuntimeException('Call of undefined or inaccessible method '.static::class.'::'.$method.'()');
    }
}
