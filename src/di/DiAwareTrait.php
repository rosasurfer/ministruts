<?php
namespace rosasurfer\di;

use rosasurfer\Application;


/**
 * A trait adding the behaviour "dependency injection awareness" to any class. Used to access dependencies in the dependency
 * container of the {@link \rosasurfer\Application}. Any class can easily be made dependency aware.
 */
trait DiAwareTrait {


    /**
     * Resolve a named service and return its implementation using the service locator pattern, or return the dependency injection
     * container of the {@link \rosasurfer\Application}.
     *
     * @param  string $name [optional] - service identifier (default: none to return the DI container)
     *
     * @return DiInterface|object|null
     */
    protected static function di($name = null) {
        $di = Application::getDi();
        if ($di && isset($name))
            return $di->get($name);
        return $di;
    }
}
