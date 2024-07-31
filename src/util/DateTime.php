<?php
namespace rosasurfer\util;

use rosasurfer\core\ObjectTrait;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\ErrorHandler;
use rosasurfer\di\DiAwareTrait;


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
            Assert::string($value);                                             // Ensure __toString() returns a string as otherwise...
        }                                                                       // PHP <7.4 will trigger a non-catchable fatal error.
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }
        catch (\Exception $ex) { ErrorHandler::handleToStringException($ex); }  // @phpstan-ignore catch.alreadyCaught (PHP5 compatibility)

        return $value;
    }
}
