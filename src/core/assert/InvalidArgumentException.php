<?php
namespace rosasurfer\core\assert;


/**
 * InvalidArgumentException
 */
class InvalidArgumentException extends \rosasurfer\core\exception\InvalidArgumentException {

    use FailedAssertionTrait;
}
