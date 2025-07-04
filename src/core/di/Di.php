<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di;

use Throwable;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\di\service\Service;
use rosasurfer\ministruts\core\di\service\ServiceInterface as IService;
use rosasurfer\ministruts\core\di\service\ServiceNotFoundException;

/**
 * Di
 *
 * A class that implements standard dependency injection/location of services and is itself a container for them.
 *
 * The definition of a service does not specify a design principle of the component (i.e. service locator or factory).
 * The design principle is determined at runtime from the called DI resolver method.
 *
 * <pre>
 *  $di = new Di();                                         // creating a new container
 *  $di = $this->di();                                      // getting the default container in a class context
 *  $di = Application::getDi();                             // getting the default container in a non-class context
 *  if (!$di) die('Service container not available');
 *
 *  // defining a parameterless service using a string
 *  $di->set('request', 'rosasurfer\\ministruts\\struts\\Request');
 *
 *  // defining a parameterized service using an anonymous function
 *  $di->set('tile', function(...$args) {
 *      return new \rosasurfer\ministruts\struts\Tile(...$args);
 *  });
 *
 *  $request = $di->get('request');                         // resolving a shared instance using the service locator pattern
 *  $tile    = $di->create('tile', ...$args);               // resolving a new instance using the factory pattern
 * </pre>
 */
class Di extends CObject implements DiInterface {

    /** @var IService[] - list of registered services */
    protected array $services = [];


    /**
     * Create a new instance and optionally load service definitions.
     *
     * @param  ?string $configDir [optional] - directory to load service definitions from (default: no loading)
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
     * Load and register service definitions.
     *
     * @param  string $configDir - directory to load service definitions from
     *
     * @return bool - whether service definitions have been found and successfully processed
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
    public function create(string $name, ...$args): object {
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
    public function get(string $name): object {
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
    public function set($name, $definition, array $aliases = []) {
        $service = new Service($name, $definition);

        foreach ($aliases as $alias) {
            $service->addAlias($alias);
        }
        $this->addService($service);
        return $definition;
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
     * Check whether a service with the specified name is registered using {@link \ArrayAccess} syntax.
     *
     * {@inheritDoc}
     */
    public function offsetExists($name): bool {
        return $this->has($name);
    }


    /**
     * {@inheritDoc}
     *
     * Resolve a named service and return its implementation using {@link \ArrayAccess} syntax. This method always returns
     * the same instance.
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    public function offsetGet($name): object {
        return $this->get($name);
    }


    /**
     * Register a service in the container using {@link \ArrayAccess} syntax.
     *
     * @param  mixed         $name       - service identifier
     * @param  string|object $definition - a class name, an instance or a Closure acting as an instance factory
     *
     * @return void
     */
    public function offsetSet($name, $definition): void {
        $this->set($name, $definition);
    }


    /**
     * Remove a service from the container using {@link \ArrayAccess} syntax.
     *
     * {@inheritDoc}
     */
    public function offsetUnset($name): void {
        $this->remove($name);
    }
}
