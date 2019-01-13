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
     * @param  string        $name
     * @param  string|object $definition
     *
     * @return ServiceInterface
     */
    public function set($name, $definition);


    /**
     * Return the last dependency injection container registered with the application.
     *
     * @return self
     */
    public static function getDefault();


    /**
     * Set a new default dependency injection container for the application.
     *
     * @param  self $di
     */
    public static function setDefault(self $di);


    /**
     * Reset the default dependency injection container used by the application.
     */
    public static function reset();
}
