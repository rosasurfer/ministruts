<?php
namespace rosasurfer\di;


/**
 * DiInterface
 *
 * Interface to be implemented by dependency injection/service locator containers.
 */
interface DiInterface {


    /**
     * Return the last dependency injection container registered with the application or the default implementation if none
     * was yet registered.
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
