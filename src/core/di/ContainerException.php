<?php
namespace rosasurfer\ministruts\core\di;

use rosasurfer\ministruts\core\exception\RuntimeException;

use Psr\Container\ContainerExceptionInterface;


/**
 * NotFoundException
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface {
}
