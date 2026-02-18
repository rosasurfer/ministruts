<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core;

use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\util\Trace;

/**
 * A trait capable of adding {@link CObject} behavior to any class. Used to add error detection features.
 */
trait ObjectTrait
{
    /**
     * Method signaling read access to inaccessible (protected or private) or non-existing properties.
     *
     * @param  string $property - property name
     *
     * @return mixed
     */
    public function __get(string $property) {
        $ex = new RuntimeException('Read access to inaccessible or non-existing property '.static::class.'::$'.$property);
        Trace::unwindTraceToMethod($ex, __FUNCTION__);
        throw $ex;
    }


    /**
     * Method signaling write access to inaccessible (protected or private) or non-existing properties.
     *
     * @param  string $property - property name
     * @param  mixed  $value    - property value
     *
     * @return void
     */
    public function __set(string $property, $value): void {
        $ex = new RuntimeException('Write access to inaccessible or non-existing property '.static::class.'::$'.$property);
        Trace::unwindTraceToMethod($ex, __FUNCTION__);
        throw $ex;
    }


    /**
     * Method catching otherwise fatal errors triggered by calls of inaccessible (protected or private) or non-existing instance methods.
     *
     * @param  string  $method - name of the called method
     * @param  mixed[] $args   - arguments passed to the method call
     *
     * @return mixed
     */
    public function __call(string $method, array $args) {
        $ex = new RuntimeException('Call of inaccessible or non-existing instance method '.static::class.'->'.$method.'()');
        Trace::unwindTraceToMethod($ex, __FUNCTION__);
        throw $ex;
    }


    /**
     * Method catching otherwise fatal errors triggered by calls of inaccessible (protected or private) or non-existing static methods.
     *
     * @param  string  $method - name of the called method
     * @param  mixed[] $args   - arguments passed to the method call
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $args) {
        $ex = new RuntimeException('Call of inaccessible or non-existing static method '.static::class.'::'.$method.'()');
        Trace::unwindTraceToMethod($ex, __FUNCTION__);
        throw $ex;
    }
}
