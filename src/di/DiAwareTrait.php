<?php
namespace rosasurfer\di;

use rosasurfer\core\Object;


/**
 * Interface for dependency injection aware classes. A custom class extending {@link Object} automatically becomes DI aware.
 * All framework classes inherit from it.
 */
interface DiAwareInterface {


    /**
     * Return the currently registered dependency injection container.
     *
     * @return Object
     */
    public static function di();
}
