<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core;

use rosasurfer\ministruts\core\di\DiAwareTrait;

/**
 * Base class of all "rosasurfer/ministruts" classes. Other classes may use {@link \rosasurfer\ministruts\core\ObjectTrait}
 * and/or {@link \rosasurfer\ministruts\core\di\DiAwareTrait} to provide the same functionality.
 */
class CObject {

    use ObjectTrait, DiAwareTrait;

    /**
     * Return a readable version of the instance.
     *
     * @return string
     */
    public function __toString(): string {
        return print_r($this, true);
    }
}
