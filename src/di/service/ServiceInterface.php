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
     * @param  string        $name              - service name
     * @param  string|object $definition        - service definition
     * @param  bool          $shared [optional] - whether the service is shared (default: yes)
     */
    public function __construct($name, $definition, $shared = true);


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
     * Whether the service is shared.
     *
     * @return bool
     */
    public function isShared();


    /**
     * Resolve the service.
     *
     * @return object
     */
    public function resolve();
}
