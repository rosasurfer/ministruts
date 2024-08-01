<?php
namespace rosasurfer\core;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\ErrorHandler;
use rosasurfer\di\DiAwareTrait;


/**
 * Parent class of all "rosasurfer" classes. Other classes may directly use {@link ObjectTrait} and/or {@link \rosasurfer\di\DiAwareTrait}
 * to implement the same functionality.
 *
 * Note: Since PHP 7.2 "object" is a keyword and can't be used as a class name.
 */
class CObject {

    use ObjectTrait, DiAwareTrait;


    /**
     * Return a human-readable version of the instance. See {@link ErrorHandler::handleToStringException()}
     * for a description of the special exception handling.
     *
     * @return string
     */
    public function __toString() {
        $value = '';

        try {
            $value = print_r($this, true);
            Assert::string($value);
        }                                                                       // Ensure __toString() returns a string as otherwise...
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }  // PHP may trigger a non-catchable fatal error.
        catch (\Exception $ex) { ErrorHandler::handleToStringException($ex); }  // @phpstan-ignore catch.alreadyCaught (PHP5 compatibility)

        return $value;
    }
}
