<?php
namespace rosasurfer\di\service;

use rosasurfer\core\exception\ClassNotFoundException;
use rosasurfer\core\exception\IllegalTypeException;

use function rosasurfer\is_class;


/**
 * Service
 *
 * Represents a service registered in the service container.
 *
 * <pre>
 *  $service = new Service('request', 'rosasurfer\\ministruts\\Request');
 *  $request = $service->resolve();
 * </pre>
 */
class Service implements ServiceInterface {


    /** @var string */
    protected $name;

    /** @var string[] */
    protected $aliases;

    /** @var string|object */
    protected $definition;

    /** @var object */
    protected $instance;


    /**
     * Constructor
     *
     * @param  string        $name       - service name
     * @param  string|object $definition - a class name, an instance or a Closure acting as an instance factory
     */
    public function __construct($name, $definition) {
        $this->name       = $name;
        $this->aliases[]  = $name;
        $this->definition = $definition;
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * {@inheritdoc}
     *
     * @return string|object $definition - a class name, an instance or a Closure acting as an instance factory
     */
    public function getDefinition() {
        return $this->definition;
    }


    /**
     * Return the service's alias names.
     *
     * @return string[] - list of aliases (including the original name)
     */
    public function getAliases() {
        return $this->aliases;
    }


    /**
     * Add an alias name for the service.
     *
     * @param  string $name - alias name
     *
     * @return $this
     */
    public function addAlias($name) {
        if (!in_array($name, $this->aliases))
            $this->aliases[] = $name;
        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @param  bool  $factory    [optional] - whether to return a new instance (default: no)
     * @param  array $parameters [optional] - additional parameters of a factory call (default: none)
     *
     * @return object
     */
    public function resolve($factory=false, $parameters=[]) {
        if (!$factory && $this->instance)
            return $this->instance;

        $instance   = null;
        $definition = $this->definition;
        !$factory && $parameters = [];

        if (is_string($definition)) {                       // plain strings are class names without parameters
            if (!is_class($definition)) throw new ClassNotFoundException('Cannot resolve service "'.$this->name.'" (unknown class "'.$definition.'")');
            $instance = new $definition(...$parameters);
        }
        else if (is_object($definition)) {                  // objects may be a \Closure or an already resolved instance
            $instance = $definition instanceof \Closure ? $definition(...$parameters) : $definition;
        }
        else throw new IllegalTypeException('Cannot resolve service "'.$this->name.'" (illegal definition type: '.gettype($definition).')');

        if (!$factory)
            $this->instance = $instance;
        return $instance;
    }
}
