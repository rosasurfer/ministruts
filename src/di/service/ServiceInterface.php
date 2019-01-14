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
     * @param  bool $existing [optional] - whether to return an existing instance (default: yes)
     *
     * @return object
     */
    public function resolve($existing = true);
}
