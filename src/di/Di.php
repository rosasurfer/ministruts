<?php
namespace rosasurfer\di;

use rosasurfer\core\Object;
use rosasurfer\di\service\ServiceInterface;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\di\service\Service;


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


    /** @var DiInterface - the latest instance registered as the default DI */
    protected static $default;

    /** @var ServiceInterface[] - list of registered services */
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
     * @return bool - whether custom service definitions have been successfully loaded
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
    public function set($name, $definition) {
        $service = new Service($name, $definition);
        $this->services[$name] = $service;
        return $service;
    }


    /**
     * {@inheritdoc}
     */
    public static function getDefault() {
        return self::$default;
    }


    /**
     * {@inheritdoc}
     */
    public static function setDefault(DiInterface $di) {
        self::$default = $di;
    }


    /**
     * {@inheritdoc}
     */
    public static function reset() {
        self::$default = null;
    }
}
