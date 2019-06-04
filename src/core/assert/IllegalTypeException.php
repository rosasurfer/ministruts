<?php
namespace rosasurfer\core\assert;

use rosasurfer\core\assert\FailedAssertionExceptionInterface as IFailedAssertionException;


/**
 * IllegalTypeException
 *
 * An assertion-specific {@link \rosasurfer\core\exception\IllegalTypeException}.
 */
class IllegalTypeException extends \rosasurfer\core\exception\IllegalTypeException implements IFailedAssertionException {

    use FailedAssertionTrait;
}
