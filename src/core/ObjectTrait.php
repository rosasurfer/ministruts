<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core;

use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\RuntimeException;


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
    public function __get($property) {
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
    public function __set($property, $value) {
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
        $ex = new RuntimeException('Call of undefined or inaccessible method '.static::class.'->'.$method.'()');
        ErrorHandler::shiftStackFramesByMethod($ex, __FUNCTION__);
        throw $ex;
    }


    /**
     * Method catching otherwise fatal errors triggered by calls of non-existing or inaccessible static methods.
     *
     * @param  string  $method - name of the called method
     * @param  mixed[] $args   - arguments passed to the method call
     *
     * @return mixed
     *
     * @throws RuntimeException
     */
    public static function __callStatic($method, array $args) {
        $ex = new RuntimeException('Call of undefined or inaccessible method '.static::class.'::'.$method.'()');
        ErrorHandler::shiftStackFramesByMethod($ex, __FUNCTION__);
        throw $ex;
    }
}
