<?php
namespace rosasurfer\ministruts\core\assert;


/**
 * InvalidValueException
 *
 * An assertion-specific InvalidValueException.
 */
class InvalidValueException extends \rosasurfer\ministruts\core\exception\InvalidValueException {

    use FailedAssertionTrait;
}
