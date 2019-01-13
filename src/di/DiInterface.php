<?php
namespace rosasurfer\di;

use rosasurfer\di\service\ServiceInterface as IService;


/**
 * DiInterface
 *
 * Interface to be implemented by dependency injection/service locator containers.
 */
interface DiInterface extends \ArrayAccess {


    /**
     * Resolve a named {@link IService} and return its implementation.
     *
     * @param  string $name
     *
     * @return object
     */
    public function get($name);


    /**
     * Resolve a named {@link IService} and return a new instance of its implementation.
     *
     * @param  string $name
     *
     * @return object
     */
    public function getNew($name);


    /**
     * Register a service in the container.
     *
     * @param  string        $name       - service identifier
     * @param  string|object $definition - a service class name, a service instance or a \Closure acting as an instance
     *                                     factory
     *
     * @return self - the same container (for chaining)
     */
    public function set($name, $definition);


    /**
     * Remove a service from the container.
     *
     * @param  string $name - service identifier
     */
    public function remove($name);


    /**
     * Whether a {@link IService} with the specified name is registered in the container.
     *
     * @param  mixed $name
     *
     * @return bool
     */
    public function isService($name);
}
