<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;


/**
 * Extended version of \DateTime.
 */
class DateTime extends \DateTime {

    use ObjectTrait, DiAwareTrait;


    /**
     * Return a human-readable version of the instance.
     *
     * @return string
     */
    public function __toString() {
        return $this->format('l, d-M-Y H:i:s O (T)');           // e.g. "Monday, 13-Mar-2017 13:19:59 +0200 (EET)"
    }
}
