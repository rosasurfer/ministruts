<?php
namespace rosasurfer\di;

use rosasurfer\di\service\ServiceInterface as IService;
use rosasurfer\di\service\ServiceNotFoundException;

use Psr\Container\ContainerInterface;


/**
 * DiInterface
 *
 * Interface to be implemented by dependency injection containers.
 */
interface DiInterface extends \ArrayAccess, ContainerInterface {


    /**
     * Whether a service with the specified name is registered in the container.
     *
     * @param  string $name - service identifier
     *
     * @return bool
     */
    public function has($name);


    /**
     * Whether a service with the specified name is registered in the container.
     *
     * @param  string $name - service identifier
     *
     * @return bool
     *
     * @deprecated
     */
    public function isService($name);


    /**
     * Resolve a named service and return its implementation. This method always returns the same instance.
     *
     * @param  string $name
     *
     * @return object
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    public function get($name);


    /**
     * Resolve a named service and return its implementation. This method always returns a new instance.
     *
     * @param  string   $name
     * @param  array ...$params - variable list of custom parameters
     *
     * @return object
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    public function create($name, ...$params);


    /**
     * Register a service in the container. An already existing service of the same name will be replaced. The service
     * usage type (service locator or factory pattern) is determined at runtime from the called resolver method.
     *
     * @param  string        $name       - service identifier
     * @param  string|object $definition - a class name, an instance or a Closure acting as an instance factory
     *
     * @return string|object - the same definition
     */
    public function set($name, $definition);


    /**
     * Remove a service from the container.
     *
     * @param  string $name - service identifier
     *
     * @return IService|null - the removed service wrapper or NULL if no such service was found
     */
    public function remove($name);
}
