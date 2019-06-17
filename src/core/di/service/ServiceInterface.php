<?php
namespace rosasurfer\core\di\service;


/**
 * ServiceInterface
 *
 * An interface implemented by services to be registered in a dependency injection container.
 */
interface ServiceInterface {


    /**
     * Constructor
     *
     * @param  string        $name       - service name
     * @param  string|object $definition - service definition
     */
    public function __construct($name, $definition);


    /**
     * Return the service name.
     *
     * @return string
     */
    public function getName();


    /**
     * Return the service definition.
     *
     * @return string|object $definition - a class name, an instance or a Closure acting as an instance factory
     */
    public function getDefinition();


    /**
     * Return the service's alias names.
     *
     * @return string[] - list of aliases (including the original name)
     */
    public function getAliases();


    /**
     * Add an alias name for the service.
     *
     * @param  string $name - alias name
     *
     * @return $this
     */
    public function addAlias($name);


    /**
     * Resolve the service.
     *
     * @param  bool  $factory    [optional] - whether to return a new instance (default: no)
     * @param  array $parameters [optional] - additional parameters of a factory call (default: none)
     *
     * @return object
     */
    public function resolve($factory=false, $parameters=[]);
}
