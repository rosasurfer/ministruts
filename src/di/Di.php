<?php
namespace rosasurfer\di;

use rosasurfer\core\Object;
use rosasurfer\di\service\Service;
use rosasurfer\di\service\ServiceInterface as IService;
use rosasurfer\di\service\ServiceNotFoundException;
use rosasurfer\exception\IllegalTypeException;


/**
 * Di
 *
 * A class that implements standard dependency injection/location of services and is itself a container for them.
 *
 * The definition of a service does not specify a type (service locator or factory pattern). Instead the type is determined
 * at runtime from the used DI resolver.
 *
 * <pre>
 *  $di = new Di();                                         // creating a new container
 *  $di = $this->di();                                      // getting the default container in a class context
 *  $di = Application::getDi();                             // getting the default container in a non-class context
 *
 *  // defining a parameterless service using a string
 *  $di->set('request', 'rosasurfer\\ministruts\\Request');
 *
 *  // defining a parameterized service using an anonymous function
 *  $di->set('tile', function(...$args) {
 *      return new \rosasurfer\ministruts\Tile(...$args);
 *  });
 *
 *  $request = $di->get('request');                         // resolving a shared instance using the service locator pattern
 *  $tile    = $di->create('tile', ...$args);               // resolving a new instance using the factory pattern
 * </pre>
 */
class Di extends Object implements DiInterface {


    /** @var IService[] - a list of registered services */
    protected $services = [];


    /**
     * Constructor
     */
    public function __construct() {
        // catch parent::__construct() calls from subclasses
    }


    /**
     * Load custom service definitions.
     *
     * @param  string $configDir - directory to load service definitions from
     *
     * @return bool - whether a custom service definitions has been found and successfully loaded
     */
    protected function loadCustomServices($configDir) {
        if (!is_string($configDir)) throw new IllegalTypeException('Illegal type of parameter $configDir: '.gettype($configDir));
        if (!is_file($file = $configDir.'/services.php'))
            return false;

        foreach (include($file) as $name => $definition) {
            $this->set($name, $definition);
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
     * @deprecated
     */
    public function isService($name) {
        return $this->has($name);
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
        catch (\Exception $ex) {
            throw new ContainerException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }


    /**
     * {@inheritdoc}
     *
     * @param  string   $name
     * @param  array ...$params - variable list of custom parameters
     *
     * @return object
     */
    public function create($name, ...$params) {
        if (!isset($this->services[$name])) throw new ServiceNotFoundException('Service "'.$name.'" not found.');
        try {
            return $this->services[$name]->resolve(true, $params);
        }
        catch (\Exception $ex) {
            throw new ContainerException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }


    /**
     * {@inheritdoc}
     *
     * @param  string        $name       - service identifier
     * @param  string|object $definition - a class name, an instance or a Closure acting as an instance factory
     *
     * @return string|object - the same definition
     */
    public function set($name, $definition) {
        $this->services[$name] = new Service($name, $definition);
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
            unset($this->services[$name]);
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
    public function offsetExists($name) {
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
    public function offsetGet($name) {
        return $this->get($name);
    }


    /**
     * Register a service in the container using {@link \ArrayAccess} syntax.
     *
     * @param  string        $name       - service identifier
     * @param  string|object $definition - a class name, an instance or a Closure acting as an instance factory
     */
    public function offsetSet($name, $definition) {
        $this->set($name, $definition);
    }


    /**
     * Remove a service from the container using {@link \ArrayAccess} syntax.
     *
     * @param  string $name - service identifier
     */
    public function offsetUnset($name) {
        $this->remove($name);
    }
}
