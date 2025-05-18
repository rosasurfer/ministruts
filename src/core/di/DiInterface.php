<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di;

use rosasurfer\ministruts\core\di\service\ServiceInterface as IService;
use rosasurfer\ministruts\core\di\service\ServiceNotFoundException;

use Psr\Container\ContainerInterface;


/**
 * DiInterface
 *
 * Interface to be implemented by dependency injection containers.
 *
 * @extends \ArrayAccess<string, object>
 */
interface DiInterface extends \ArrayAccess, ContainerInterface {


    /**
     * Whether a service with the specified name is registered in the container.
     *
     * @param  string $name - service identifier
     *
     * @return bool
     */
    public function has(string $name): bool;


    /**
     * Resolve a named service and return its implementation. This method always returns a new instance.
     *
     * @param  string   $name
     * @param  mixed ...$args - instantiation arguments
     *
     * @return object
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    public function create(string $name, ...$args): object;


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
    public function get(string $name): object;


    /**
     * Register a service in the container.  An already existing service of the same name (and all its name aliases) will
     * be replaced.  The service usage type (service locator or factory pattern) is determined at runtime from the called
     * resolver method.
     *
     * @param  string        $name               - service identifier
     * @param  string|object $definition         - a class name, an instance or a Closure acting as an instance factory
     * @param  string[]      $aliases [optional] - service identifier aliases (default: none)
     *
     * @return string|object - the same definition
     */
    public function set(string $name, $definition, array $aliases = []);


    /**
     * Remove a service from the container.
     *
     * @param  string $name - service identifier
     *
     * @return ?IService - the removed service or NULL if no such service was found
     */
    public function remove(string $name): ?IService;
}
