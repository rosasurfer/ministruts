<?php
namespace rosasurfer\core\assert;


/**
 * IllegalTypeException
 *
 * An assertion-specific IllegalTypeException.
 */
class IllegalTypeException extends \rosasurfer\core\exception\IllegalTypeException {

    use FailedAssertionTrait;
}
