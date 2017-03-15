<?php
namespace rosasurfer\util;

use rosasurfer\core\ObjectTrait as TObject;


/**
 * Extension version of \DateTime.
 */
class DateTime extends \DateTime {

    use TObject;


    /**
     * Return a human-readable version of the instance.
     *
     * @return string
     */
    public function __toString() {
        return $this->format('l, d-M-Y H:i:s O (T)');   // Monday, 13-Mar-2017 13:19:59 +0200 (EET)
    }
}
