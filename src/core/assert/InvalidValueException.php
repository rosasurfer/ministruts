<?php
namespace rosasurfer\core\assert;


/**
 * InvalidValueException
 *
 * An assertion-specific InvalidValueException.
 */
class InvalidValueException extends \rosasurfer\core\exception\InvalidValueException {

    use FailedAssertionTrait;
}
