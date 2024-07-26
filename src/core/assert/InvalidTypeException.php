<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\assert;


/**
 * InvalidTypeException
 *
 * An assertion-specific InvalidTypeException.
 */
class InvalidTypeException extends \rosasurfer\ministruts\core\exception\InvalidTypeException {

    use FailedAssertionTrait;
}
