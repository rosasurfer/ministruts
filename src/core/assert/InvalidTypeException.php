<?php
namespace rosasurfer\core\assert;


/**
 * InvalidTypeException
 *
 * An assertion-specific InvalidTypeException.
 */
class InvalidTypeException extends \rosasurfer\core\exception\InvalidTypeException {

    use FailedAssertionTrait;
}
