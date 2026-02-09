<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di;

use Throwable;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\di\service\Service;
use rosasurfer\ministruts\core\di\service\ServiceInterface as IService;
use rosasurfer\ministruts\core\di\service\ServiceNotFoundException;

/**
 * A class for managing application dependencies.
 *
 * The definition of a dependency does not specify its intended runtime usage (i.e. service locator vs factory pattern).
 * The applied usage is determined at runtime from the called resolver method.
 *
 * <pre>
 *  $container = new Container();                           // create a new container
 *  $container = Application::container();                  // get the application container
 *
 *  // define a parameterless service using a string
 *  $container->set('request', 'rosasurfer\\ministruts\\struts\\Request');
 *
 *  // define a parameterized service using an anonymous function
 *  $container->set('tile', function(...$args) {
 *      return new \rosasurfer\ministruts\struts\Tile(...$args);
 *  });
 *
 *  $request = $container->get('request');                  // resolve a dependency using Psr\ContainerInterface::get()
 *  $request = $container->service('request');              // resolve a dependency using the service locator pattern
 *  $tile    = $container->factory('tile', ...$args);       // resolve a new instance using the factory pattern
 * </pre>
 */
class Container extends CObject implements ContainerInterface {

    /** @var IService[] - list of registered services */
    protected array $services = [];


    /**
     * Create a new instance and optionally load dependency definitions.
     *
     * @param  ?string $configDir [optional] - directory to load dependency definitions from (default: no loading)
     */
    public function __construct(?string $configDir = null) {
        if (isset($configDir)) {
            $this->loadServices($configDir);
        }
    }


    /**
     * Register a {@link Service} in the container.
     *
     * @param  IService $service
     *
     * @return $this
     */
    protected function addService(IService $service): self {
        foreach ($service->getAliases() as $alias) {
            $this->services[$alias] = $service;
        }
        return $this;
    }


    /**
     * Load and register dependency definitions.
     *
     * @param  string $configDir - directory to load dependency definitions from
     *
     * @return bool - whether dependency definitions have been found and successfully processed
     */
    protected function loadServices(string $configDir): bool {
        if (!is_file($file = $configDir.'/services.php'))
            return false;

        foreach (include($file) as $name => $definition) {
            $aliases = [];
            if (is_array($definition)) {
                $aliases = $definition;
                $definition = array_shift($aliases);
            }
            $this->set($name, $definition, $aliases);
        }
        return true;
    }


    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool {
        return isset($this->services[$name]);
    }


    /**
     * {@inheritDoc}
     */
    public function get(string $name): object {
        return $this->service($name);
    }


    /**
     * {@inheritDoc}
     */
    public function service(string $name): object {
        if (!isset($this->services[$name])) throw new ServiceNotFoundException('Service "'.$name.'" not found.');
        try {
            return $this->services[$name]->resolve(false);
        }
        catch (Throwable $ex) {
            throw new ContainerException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }


    /**
     * {@inheritDoc}
     */
    public function factory(string $name, ...$args): object {
        if (!isset($this->services[$name])) throw new ServiceNotFoundException('Service "'.$name.'" not found.');
        try {
            return $this->services[$name]->resolve(true, $args);
        }
        catch (Throwable $ex) {
            throw new ContainerException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }


    /**
     * {@inheritDoc}
     */
    public function set($name, $definition, array $aliases = []): self {
        $service = new Service($name, $definition);

        foreach ($aliases as $alias) {
            $service->addAlias($alias);
        }
        $this->addService($service);
        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function remove(string $name): ?IService {
        $service = null;
        if ($this->has($name)) {
            $service = $this->services[$name];

            foreach ($service->getAliases() as $alias) {
                unset($this->services[$alias]);
            }
        }
        return $service;
    }


    /**
     * Check whether a dependency with the specified name is registered using {@link \ArrayAccess} syntax.
     *
     * {@inheritDoc}
     */
    public function offsetExists($name): bool {
        return $this->has($name);
    }


    /**
     * {@inheritDoc}
     *
     * Resolve a named dependency and return its implementation using {@link \ArrayAccess} syntax. This method always returns
     * the same instance.
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    public function offsetGet($name): object {
        return $this->get($name);
    }


    /**
     * Register a dependency in the container using {@link \ArrayAccess} syntax.
     *
     * @param  mixed         $name       - dependency identifier
     * @param  string|object $definition - a class name, an instance or a Closure acting as an instance factory
     *
     * @return void
     */
    public function offsetSet($name, $definition): void {
        $this->set($name, $definition);
    }


    /**
     * Remove a dependency from the container using {@link \ArrayAccess} syntax.
     *
     * {@inheritDoc}
     */
    public function offsetUnset($name): void {
        $this->remove($name);
    }
}
