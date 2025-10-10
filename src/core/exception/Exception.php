<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;

/**
 * Base class for all "rosasurfer" exceptions. Provides some convenient helpers.
 */
class Exception extends \Exception implements ExceptionInterface {

    use ExceptionTrait, ObjectTrait, DiAwareTrait;
}
