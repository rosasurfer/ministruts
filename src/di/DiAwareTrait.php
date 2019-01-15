<?php
namespace rosasurfer\di;

use rosasurfer\Application;


/**
 * A trait adding the behaviour "dependency injection awareness" to any class. Used to access the default dependency container
 * of the {@link Application}. Any class can easily be made dependency aware.
 */
trait DiAwareTrait {


    /**
     * Return the default dependency injection container of the {@link Application}.
     *
     * @return DiInterface
     */
    protected static function di() {
        return Application::getDi();
    }
}
