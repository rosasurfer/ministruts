<?php
namespace rosasurfer\core\assert;


/**
 * InvalidArgumentException
 */
class InvalidArgumentException extends \rosasurfer\exception\InvalidArgumentException {

    use FailedAssertionTrait;
}
