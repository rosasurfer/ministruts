<?php
namespace rosasurfer\di;

use rosasurfer\Application;


/**
 * A trait adding the behavior "dependency injection awareness" to any class. Used to access dependencies in the dependency
 * container of the {@link Application}. Any class can easily be made dependency aware.
 */
trait DiAwareTrait {


    /**
     * Optionally resolve a named service and return its implementation using the service locator pattern&#46;  If no service
     * was specified return the default dependency injection container of the {@link Application}.
     *
     * @param  string $name [optional] - service identifier (default: the dependency injection container)
     *
     * @return DiInterface|object
     */
    protected static function di($name = null) {
        $di = Application::getDi();
        if ($di && isset($name))
            return $di->get($name);
        return $di;
    }
}
