<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\service;

use Closure;

use rosasurfer\ministruts\core\exception\ClassNotFoundException;


/**
 * Service
 *
 * Represents a service registered in the service container.
 *
 * <pre>
 *  $service = new Service('request', 'rosasurfer\\ministruts\\struts\\Request');
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

    /** @var ?object */
    protected $instance = null;


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
     * {@inheritdoc}
     *
     * @return string[] - list of aliases (including the original name)
     */
    public function getAliases() {
        return $this->aliases;
    }


    /**
     * {@inheritdoc}
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
     * @param  bool    $factory [optional] - whether to return a new instance (default: no)
     * @param  mixed[] $args [optional]    - additional instantiation arguments of a factory call (default: none)
     *
     * @return object
     */
    public function resolve($factory=false, $args=[]) {
        if (!$factory && $this->instance) {
            return $this->instance;
        }

        $instance = null;
        $definition = $this->definition;
        if (!$factory) $args = [];

        if (is_string($definition)) {                       // a string is a class name without parameters
            if (!class_exists($definition)) throw new ClassNotFoundException("Cannot resolve service \"$this->name\" (unknown class \"$definition\")");
            $instance = new $definition(...$args);
        }
        elseif (is_object($definition)) {                   // objects may be a Closure or an already resolved instance
            $instance = $definition instanceof Closure ? $definition(...$args) : $definition;
        }

        if (!$factory) {
            $this->instance = $instance;
        }
        return $instance;
    }
}
