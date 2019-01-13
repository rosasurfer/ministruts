<?php
namespace rosasurfer\di\service;

use rosasurfer\exception\ClassNotFoundException;
use rosasurfer\exception\IllegalTypeException;

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

    /** @var string|object */
    protected $definition;

    /** @var object */
    protected $instance;


    /**
     * Constructor
     *
     * @param  string        $name       - service name
     * @param  string|object $definition - service definition
     */
    public function __construct($name, $definition) {
        $this->name       = $name;
        $this->definition = $definition;
    }


    /**
     * {@inheritdoc}
     */
    public function getName() {
        return $this->name;
    }


    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return $this->definition;
    }


    /**
     * {@inheritdoc}
     */
    public function resolve($shared = true) {
        if ($shared && $this->instance)
            return $this->instance;

        $definition = $this->definition;
        $instance = null;

        if (is_string($definition)) {                       // strings must be a class name without parameters
            if (!is_class($definition)) throw new ClassNotFoundException('Cannot resolve service "'.$this->name.'" (unknown class "'.$definition.'")');
            $instance = new $definition();
        }
        else if (is_object($definition)) {                  // objects may be a Closure or an already resolved instance
            $instance = $definition instanceof \Closure ? $definition() : $definition;
        }
        else throw new IllegalTypeException('Cannot resolve service "'.$this->name.'" (illegal definition type: '.getType($definition).')');

        if ($shared)
            $this->instance = $instance;
        return $instance;
    }
}
