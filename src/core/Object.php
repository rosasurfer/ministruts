<?php
namespace rosasurfer\core;

use rosasurfer\di\DiAwareTrait;


/**
 * Super class for all "rosasurfer" classes. Other classes may directly use {@link ObjectTrait} and/or {@link \rosasurfer\di\DiAwareTrait}
 * to achieve the same functionality.
 */
class Object {

    use ObjectTrait, DiAwareTrait;


    /**
     * Return a human-readable version of the instance.
     *
     * @_param  int $levels - how many levels of an object graph to recurse into
     *                        (default: all)
     * @return string
     */
    public function __toString(/*$levels=PHP_INT_MAX*/) {
        /*
        // TODO
        if (func_num_args()) {
            $levels = func_get_arg(0);
            if ($levels != PHP_INT_MAX) {
            }
        }
        */
        return print_r($this, true);
    }
}
