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
     * Resolve a named service and return its implementation.
     *
     * @param  string $name
     *
     * @return object
     */
    public function get($name);


    /**
     * Resolve a named service and return a new instance of its implementation.
     *
     * @param  string $name
     *
     * @return object
     */
    public function factory($name);


    /**
     * Register a service in the container.
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
     * @param  string $name
     *
     * @return bool
     */
    public function isService($name);
}
