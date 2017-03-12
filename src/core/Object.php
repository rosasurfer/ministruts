<?php
namespace rosasurfer\core;

use rosasurfer\di\DiAwareTrait;


/**
 * Super class for all "rosasurfer" classes. Other classes may directly use {@link ObjectTrait} and/or {@link DiAwareTrait}
 * to achieve the same functionality.
 */
class Object {
    use ObjectTrait, DiAwareTrait;
}
