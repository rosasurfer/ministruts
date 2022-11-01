<?php
namespace rosasurfer\core;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\ErrorHandler;
use rosasurfer\core\di\DiAwareTrait;


/**
 * Base class of all "rosasurfer" classes. Other classes may use {@link ObjectTrait} and/or {@link DiAwareTrait} directly to provide the
 * same functionality.
 *
 * Note: Since PHP 7.2 "object" is a keyword and can't be used as a class name.
 */
class CObject {

    use ObjectTrait, DiAwareTrait;


    /**
     * Return a human-readable version of the instance.
     *
     * @return string
     */
    public function __toString() {
        try {
            $value = print_r($this, true);
            Assert::string($value);                             // Ensure __toString() returns a string as otherwise...
            return $value;                                      // PHP will trigger a non-catchable fatal error.
        }
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }
        catch (\Exception $ex) { ErrorHandler::handleToStringException($ex); }
    }
}
