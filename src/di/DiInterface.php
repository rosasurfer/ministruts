<?php
namespace rosasurfer\di;

use rosasurfer\di\service\ServiceInterface;


/**
 * DiInterface
 *
 * Interface to be implemented by dependency injection/service locator containers.
 */
interface DiInterface {


    /**
     * Register a service in the service container.
     *
     * @param  string        $name       - service identifier
     * @param  string|object $definition - a service class name, a service instance or a \Closure acting as an
     *                                     instance factory
     * @return ServiceInterface
     */
    public function set($name, $definition);
}
