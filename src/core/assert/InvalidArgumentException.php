<?php
namespace rosasurfer\core\assert;

use rosasurfer\core\assert\FailedAssertionExceptionInterface as IFailedAssertionException;


/**
 * InvalidArgumentException
 *
 * An assertion-specific {@link \rosasurfer\core\exception\InvalidArgumentException}.
 */
class InvalidArgumentException extends \rosasurfer\core\exception\InvalidArgumentException implements IFailedAssertionException {

    use FailedAssertionTrait;
}
