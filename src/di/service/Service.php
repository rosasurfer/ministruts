<?php
namespace rosasurfer\di\service;

use rosasurfer\exception\RuntimeException;

use function rosasurfer\is_class;


/**
 * Service
 *
 * Represents a service in the services container.
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

    /** @var bool */
    protected $shared;

    /** @var object */
    protected $sharedInstance;


    /**
     * Constructor
     *
     * @param  string        $name              - service name
     * @param  string|object $definition        - service definition
     * @param  bool          $shared [optional] - whether the service is shared (default: yes)
     */
    public function __construct($name, $definition, $shared = true) {
        $this->name       = $name;
        $this->definition = $definition;
        $this->shared     = $shared;
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
    public function isShared() {
        return $this->shared;
    }


    /**
     * {@inheritdoc}
     */
    public function resolve() {
        if ($this->shared && $this->sharedInstance)
            return $this->sharedInstance;

        $instance = null;
        $definition = $this->definition;

        if (is_string($definition)) {                       // strings must be a class name without parameters
            if (!is_class($definition)) throw new RuntimeException('Service "'.$this->name.'" cannot be resolved, definition: "'.$definition.'" (string)');
            $instance = new $definition();
        }
        else if (is_object($definition)) {                  // objects may be a Closure or an already resolved instance
            $instance = $definition instanceof \Closure ? $definition() : $definition;
        }
        else throw new RuntimeException('Service "'.$this->name.'" cannot be resolved, definition type: '.getType($definition));

        if ($this->shared) $this->sharedInstance = $instance;
        return $instance;
    }
}
