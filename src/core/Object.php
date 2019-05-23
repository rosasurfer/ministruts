<?php
namespace rosasurfer\core;

use rosasurfer\di\DiAwareTrait;


/**
 * Parent class of all "rosasurfer" classes. Other classes may directly use {@link ObjectTrait} and/or {@link DiAwareTrait}
 * to implement the same functionality.
 */
class Object {

    use ObjectTrait, DiAwareTrait;


    /**
     * Return a human-readable version of the instance.
     *
     * @return string
     */
    public function __toString() {
        return print_r($this, true);
    }
}
