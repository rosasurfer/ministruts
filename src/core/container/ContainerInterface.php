<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\container;

use ArrayAccess;

use rosasurfer\ministruts\core\container\service\ServiceInterface as IService;
use rosasurfer\ministruts\core\container\service\ServiceNotFoundException;

use Psr\Container\ContainerInterface as PsrContainer;

/**
 * Interface to be implemented by dependency containers.
 *
 * @extends ArrayAccess<string, object>
 */
interface ContainerInterface extends ArrayAccess, PsrContainer
{
    /**
     * Whether a dependency with the specified name is registered in the container.
     *
     * @param  string $name - service identifier
     *
     * @return bool
     */
    public function has(string $name): bool;


    /**
     * Resolve a named dependency and return its implementation. Alias of {DiInterface::service()}.
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
     * Service locator
     *
     * Resolve a named dependency and return its implementation. This method always returns the same instance.
     *
     * @param  string $name
     *
     * @return object
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    public function service(string $name): object;


    /**
     * Factory pattern
     *
     * Resolve a named dependency and return a new instance of the wrapped object.
     *
     * @param  string   $name
     * @param  mixed ...$args - instantiation arguments
     *
     * @return object
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    public function factory(string $name, ...$args): object;


    /**
     * Register a dependency in the container. An already existing dependency of the same name (and its name aliases) will
     * be replaced. The usage type (service locator or factory pattern) is determined at runtime from the called resolver method.
     *
     * @param  string        $name               - dependency identifier
     * @param  string|object $definition         - a class name, an instance or a Closure acting as an instance factory
     * @param  string[]      $aliases [optional] - identifier aliases (default: none)
     *
     * @return $this
     */
    public function set(string $name, $definition, array $aliases = []): self;


    /**
     * Remove a dependency from the container.
     *
     * @param  string $name - dependency identifier
     *
     * @return ?IService - the removed dependency or NULL if no such dependency was found
     */
    public function remove(string $name): ?IService;
}
