<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di;

use rosasurfer\ministruts\core\exception\RuntimeException;

use Psr\Container\ContainerExceptionInterface;


/**
 * ContainerException
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface {
}
