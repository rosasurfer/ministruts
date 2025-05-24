<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use Exception;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;

/**
 * Base class for all "rosasurfer" exceptions. Provides some additional convenient helpers.
 */
class RosasurferException extends Exception implements RosasurferExceptionInterface {

    use RosasurferExceptionTrait, ObjectTrait, DiAwareTrait;
}
