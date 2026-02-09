<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\container;

use rosasurfer\ministruts\core\exception\RuntimeException;

use Psr\Container\ContainerExceptionInterface;

/**
 * ContainerException
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface {
}
