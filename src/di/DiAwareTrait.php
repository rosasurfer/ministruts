<?php
namespace rosasurfer\di;

use rosasurfer\exception\UnimplementedFeatureException;


/**
 * A trait providing access to the currently registered dependency injection container.
 * All classes extending {@link Object} are dependency aware.
 */
trait DiAwareTrait {


    /**
     * Return the currently registered dependency injection container.
     *
     * @return null
     */
    public static function di() {
        throw new UnimplementedFeatureException(__METHOD__);
    }
}
