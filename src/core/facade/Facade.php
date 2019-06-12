<?php
namespace rosasurfer\core\facade;

use rosasurfer\core\StaticClass;


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
}
