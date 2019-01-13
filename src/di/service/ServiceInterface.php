<?php
namespace rosasurfer\di\service;

use rosasurfer\di\Di;


/**
 * ServiceInterface
 *
 * An interface implemented by services to be registered in a {@link Di} container.
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
     * @param  bool $shared [optional] - whether to return an already existing instance (default: yes)
     *
     * @return object
     */
    public function resolve($shared = true);
}
