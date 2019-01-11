<?php
namespace rosasurfer\di;

use rosasurfer\exception\UnimplementedFeatureException;


/**
 * A trait adding the behaviour "dependency injection awareness" to any class. Used to access the currently registered
 * dependency injection and service container. Any class can easily be made dependency aware.
 */
trait DiAwareTrait {


    /**
     * Return the currently registered dependency injection and service container.
     *
     * @return void
     */
    public static function di() {
        throw new UnimplementedFeatureException(__METHOD__);
    }
}
