<?php
namespace rosasurfer\core\assert;


/**
 * InvalidArgumentException
 *
 * An assertion-specific InvalidArgumentException.
 */
class InvalidArgumentException extends \rosasurfer\core\exception\InvalidArgumentException {

    use FailedAssertionTrait;
}
