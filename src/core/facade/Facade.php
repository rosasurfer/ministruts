<?php
namespace rosasurfer\core\facade;

use rosasurfer\core\StaticClass;
use rosasurfer\core\exception\UnimplementedFeatureException;


/**
 * A {@link Facade} simplifies access to an underlying API.
 *
 * In MiniStruts it translates static method calls to the API of one or more other instances.
 */
abstract class Facade extends StaticClass {


    /**
     * Resolve the target API instance for a method call.
     *
     * @param  string $method - called method name
     *
     * @return object|null
     */
    abstract protected static function target($method);


    /**
     * Translate a static method call to the underlying API.
     *
     * @param  string $method - method name
     * @param  array  $args   - arguments passed to the method call
     *
     * @return mixed
     */
    public static function __callStatic($method, array $args) {
        throw new UnimplementedFeatureException(static::class.' must implement Facade::'.__FUNCTION__.'()');
    }
}
