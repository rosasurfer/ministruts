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
 *  $di = $this->di();                                      // getting the default container inside of a class context
 *  $di = Application::getDi();                             // getting the default container outside of a class context
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
    protected $services;


    /**
     * Constructor
     */
    public function __construct() {
    }


    /**
     * Load custom service definitions.
     *
     * @param  string $configDir - directory to load service definitions from
     *
     * @return bool - whether custom service definitions have been found and successfully loaded
     */
    protected function loadCustomServices($configDir) {
        if (!is_string($configDir)) throw new IllegalTypeException('Illegal type of parameter $configDir: '.getType($configDir));
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
     * @throws ServiceNotFoundException if the service is unknown
     */
    public function get($name) {
        if (isSet($this->services[$name]))
            return $this->services[$name]->resolve($factory=false);
        throw new ServiceNotFoundException('Service "'.$name.'" not found.');
    }


    /**
     * {@inheritdoc}
     *
     * @throws ServiceNotFoundException if the service is unknown
     */
    public function create($name, ...$params) {
        if ($this->isService($name))
            return $this->services[$name]->resolve($factory=true, $params);
        throw new ServiceNotFoundException('Service "'.$name.'" not found.');
    }


    /**
     * {@inheritdoc}
     */
    public function set($name, $definition) {
        $service = new Service($name, $definition);
        $this->services[$name] = $service;
        return $service;
    }


    /**
     * {@inheritdoc}
     */
    public function remove($name) {
        $service = null;
        if ($this->isService($name)) {
            $service = $this->services[$name];
            unset($this->services[$name]);
        }
        return $service;
    }


    /**
     * {@inheritdoc}
     */
    public function isService($name) {
        return isSet($this->services[$name]);
    }


    /**
     * Check whether a service with the specified name is registered using {@link \ArrayAccess} syntax.
     *
     * @param  mixed $name
     *
     * @return bool
     */
    public function offsetExists($name) {
        return $this->isService($name);
    }


    /**
     * Resolve a named service and return its implementation using {@link \ArrayAccess} syntax.
     *
     * @param  string $name
     *
     * @return object
     */
    public function offsetGet($name) {
        return $this->get($name);
    }


    /**
     * Register a service in the container using {@link \ArrayAccess} syntax.
     *
     * @param  string        $name       - service identifier
     * @param  string|object $definition - a service class name, a service instance or a \Closure acting as an instance
     *                                     factory
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
