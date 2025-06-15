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
    protected string $name;

    /** @var string[] */
    protected array $aliases;

    /** @var string|object */
    protected $definition;

    /** @var ?object */
    protected ?object $instance = null;


    /**
     * Constructor
     *
     * @param  string        $name       - service name
     * @param  string|object $definition - a class name, an instance or a Closure acting as an instance factory
     */
    public function __construct(string $name, $definition) {
        $this->name       = $name;
        $this->aliases[]  = $name;
        $this->definition = $definition;
    }


    /**
     * {@inheritDoc}
     */
    public function getName(): string {
        return $this->name;
    }


    /**
     * {@inheritDoc}
     */
    public function getDefinition() {
        return $this->definition;
    }


    /**
     * {@inheritDoc}
     */
    public function getAliases(): array {
        return $this->aliases;
    }


    /**
     * {@inheritDoc}
     */
    public function addAlias(string $name): self {
        if (!\in_array($name, $this->aliases, true)) {
            $this->aliases[] = $name;
        }
        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function resolve(bool $factory = false, array $args = []): object {
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
