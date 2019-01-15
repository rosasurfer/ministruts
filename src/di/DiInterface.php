<?php
namespace rosasurfer\di;

use rosasurfer\di\service\ServiceInterface;


/**
 * DiInterface
 *
 * Interface to be implemented by dependency injection/service locator containers.
 */
interface DiInterface extends \ArrayAccess {


    /**
     * Resolve a named service and return its implementation. This method uses the service locator pattern and always
     * returns the same instance.
     *
     * @param  string $name
     *
     * @return object
     */
    public function get($name);


    /**
     * Resolve a named service and return its implementation. This method uses the factory pattern and always returns
     * a new instance.
     *
     * @param  string $name
     * @param  mixed  $params - variable list of custom parameters
     *
     * @return object
     */
    public function factory($name, ...$params);


    /**
     * Register a service in the container. An already existing service of the same will be replaced.
     * The type (service locator or factory pattern) is determined at runtime from the chosen resolver method.
     *
     * @param  string        $name       - service identifier
     * @param  string|object $definition - a class name, an implementation instance or a \Closure acting as an instance
     *                                     factory
     *
     * @return ServiceInterface - the new service wrapper
     */
    public function set($name, $definition);


    /**
     * Remove a service from the container.
     *
     * @param  string $name - service identifier
     *
     * @return ServiceInterface|null - the removed service wrapper or NULL if no such service was found
     */
    public function remove($name);


    /**
     * Whether a service with the specified name is registered in the container.
     *
     * @param  string $name - service identifier
     *
     * @return bool
     */
    public function isService($name);
}
