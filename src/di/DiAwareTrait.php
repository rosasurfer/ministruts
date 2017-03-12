<?php
namespace rosasurfer\di;

use rosasurfer\exception\UnimplementedFeatureException;


/**
 * A trait providing access to the currently registered dependency injection container.
 * Thus every class can easily be made dependency aware.
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
