<?php
namespace rosasurfer\di\service;


/**
 * ServiceInterface
 *
 * An interface implemented by services to be registered in a service container.
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
     * @return mixed
     */
    public function getDefinition();


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
