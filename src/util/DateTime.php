<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\di\DiAwareTrait;
use rosasurfer\ministruts\core\error\ErrorHandler;


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
        $value = '';
        try {
            $value = $this->format('l, d-M-Y H:i:s O (T)');                     // Monday, 13-Mar-2017 13:19:59 +0200 (EET)
            Assert::string($value);
        }                                                                       // Ensure __toString() doesn't throw an exception as otherwise
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }  // PHP < 7.4 will trigger a non-catchable fatal error.
        return $value;                                                          // @see  https://bugs.php.net/bug.php?id=53648
    }
}
