<?php
namespace rosasurfer\di;

use rosasurfer\core\Object;
use rosasurfer\di\service\ServiceInterface;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\di\service\Service;
use rosasurfer\Application;


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


    /** @var DiInterface - the current default instance of the application */
    protected static $default;

    /** @var ServiceInterface[] - a list of registered services */
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
            $instance = $this->services[$name]->resolve($shared=true);
        return $instance;
    }


    /**
     * {@inheritdoc}
     */
    public function getNew($name) {
        $instance = null;
        if (isSet($this->services[$name]))
            $instance = $this->services[$name]->resolve($shared=false);
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
     * Return the default dependency injection container of the {@link Application}. This is the instance previously set
     * with {@link Di::setDefault()}.
     *
     * @return DiInterface
     */
    public static function getDefault() {
        return self::$default;
    }


    /**
     * Set a new default dependency injection container for the {@link Application}.
     *
     * @param  DiInterface $di
     *
     * @return DiInterface - the previously registered default container
     */
    public static function setDefault(DiInterface $di) {
        $previous = self::$default;
        self::$default = $di;
        return $previous;
    }


    /**
     * Reset the default dependency injection container used by the {@link Application}.
     */
    public static function reset() {
        self::$default = null;
    }
}
