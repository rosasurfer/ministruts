<?php
namespace rosasurfer\di;

use rosasurfer\core\Object;
use rosasurfer\di\service\Service;
use rosasurfer\di\service\ServiceInterface as IService;
use rosasurfer\exception\IllegalTypeException;


/**
 * Di
 *
 * A class that implements standard dependency injection/location of services and is itself a container for them.
 *
 * <pre>
 *  $di = new Di();
 *
 *  // using a string definition
 *  $di->set('request', 'rosasurfer\\ministruts\\Request');
 *
 *  // using an anonymous function
 *  $di->set('request', function() {
 *      return new \rosasurfer\ministruts\Request();
 *  });
 *
 *  $request = $di->getRequest();
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

        $definitions = include($file);

        foreach ($definitions as $name => $definition) {
            $this->set($name, $definition);
        }
        return true;
    }


    /**
     * {@inheritdoc}
     */
    public function get($name) {
        $instance = null;
        if (isSet($this->services[$name]))
            $instance = $this->services[$name]->resolve();
        return $instance;
    }


    /**
     * {@inheritdoc}
     */
    public function factory($name) {
        $instance = null;
        if ($this->isService($name))
            $instance = $this->services[$name]->resolve($existing=false);
        return $instance;
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
