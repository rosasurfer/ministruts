<?php
namespace rosasurfer\di;

use rosasurfer\exception\UnimplementedFeatureException;


/**
 * A trait adding the behaviour "DI awareness" to any class. Used to access the currently registered dependency injection
 * container. Any class can easily be made dependency aware.
 */
trait DiAwareTrait {


    /**
     * Return the currently registered dependency injection container.
     *
     * @return void
     */
    public static function di() {
        throw new UnimplementedFeatureException(__METHOD__);
    }
}
