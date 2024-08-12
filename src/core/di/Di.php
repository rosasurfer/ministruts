<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di;

use Throwable;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
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
    protected $services = [];


    /**
     * Create a new instance and optionally load service definitions.
     *
     * @param  ?string $configDir [optional] - directory to load service definitions from (default: no loading)
     */
    public function __construct($configDir = null) {
        if (isset($configDir)) {
            Assert::string($configDir);
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
    protected function addService(IService $service) {
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
    protected function loadServices($configDir) {
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
     * {@inheritdoc}
     *
     * @param  string $name - service identifier
     *
     * @return bool
     */
    public function has($name) {
        return isset($this->services[$name]);
    }


    /**
     * {@inheritdoc}
     *
     * @param  string   $name
     * @param  mixed ...$args - instantiation arguments
     *
     * @return object
     */
    public function create($name, ...$args) {
        if (!isset($this->services[$name])) throw new ServiceNotFoundException('Service "'.$name.'" not found.');
        try {
            return $this->services[$name]->resolve(true, $args);
        }
        catch (Throwable $ex) {
            throw new ContainerException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $name
     *
     * @return object
     */
    public function get($name) {
        if (!isset($this->services[$name])) throw new ServiceNotFoundException('Service "'.$name.'" not found.');
        try {
            return $this->services[$name]->resolve(false);
        }
        catch (Throwable $ex) {
            throw new ContainerException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }


    /**
     * {@inheritdoc}
     *
     * @param  string        $name               - service identifier
     * @param  string|object $definition         - a class name, an instance or a \Closure acting as an instance factory
     * @param  string[]      $aliases [optional] - service identifier aliases (default: none)
     *
     * @return string|object - the same definition
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
     * {@inheritdoc}
     *
     * @param  string $name - service identifier
     *
     * @return IService|null - the removed service wrapper or NULL if no such service was found
     */
    public function remove($name) {
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
     * @param  mixed $name
     *
     * @return bool
     */
    public function offsetExists($name): bool {
        return $this->has($name);
    }


    /**
     * Resolve a named service and return its implementation using {@link \ArrayAccess} syntax. This method always returns
     * the same instance.
     *
     * @param  string $name
     *
     * @return object
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($name) {
        return $this->get($name);
    }


    /**
     * Register a service in the container using {@link \ArrayAccess} syntax.
     *
     * @param  string        $name       - service identifier
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
     * @param  string $name - service identifier
     *
     * @return void
     */
    public function offsetUnset($name): void {
        $this->remove($name);
    }
}
