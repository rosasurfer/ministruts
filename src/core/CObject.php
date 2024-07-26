<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core;

use rosasurfer\ministruts\core\di\DiAwareTrait;


/**
 * Base class of all "rosasurfer" classes. Other classes may use {@link ObjectTrait} and/or {@link \rosasurfer\ministruts\core\di\DiAwareTrait}
 * directly to provide the same functionality.
 *
 * Note: Since PHP 7.2 "object" is a keyword and can't be used as a class name.
 */
class CObject {

    use ObjectTrait, DiAwareTrait;


    /**
     * Return a readable version of the instance.
     *
     * @return string
     */
    public function __toString() {
        return print_r($this, true);
    }
}
