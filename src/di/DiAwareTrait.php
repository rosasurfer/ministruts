<?php
namespace rosasurfer\di;

use rosasurfer\exception\UnimplementedFeatureException;


/**
 * A trait providing access to the currently registered dependency container.
 * All classes extending {@link Object} are DI aware.
 */
trait DiAwareTrait {


    /**
     * Return the currently registered dependency injection container.
     *
     * @return null
     */
    public static function di() {
        //echoPre(__TRAIT__);
        throw new UnimplementedFeatureException(__METHOD__);
    }
}
