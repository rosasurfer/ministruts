<?php
namespace rosasurfer\di;


/**
 * A trait adding the behaviour "dependency injection awareness" to any class. Used to access the currently registered
 * default dependency service container. Any class can easily be made dependency aware.
 */
trait DiAwareTrait {


    /**
     * Return the currently registered default dependency injection container.
     *
     * @return DiInterface
     */
    public function di() {
        return Di::getDefault();
    }
}
