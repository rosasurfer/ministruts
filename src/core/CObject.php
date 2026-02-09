<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core;

/**
 * Base class of all "rosasurfer/ministruts" classes. Other classes may use {@link \rosasurfer\ministruts\core\ObjectTrait}
 * to provide the same functionality.
 */
class CObject {

    use ObjectTrait;

    /**
     * Return a readable version of the instance.
     *
     * @return string
     */
    public function __toString(): string {
        return print_r($this, true);
    }
}
