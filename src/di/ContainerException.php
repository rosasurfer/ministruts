<?php
namespace rosasurfer\di;

use rosasurfer\core\exception\RuntimeException;
use Psr\Container\ContainerExceptionInterface;


/**
 * NotFoundException
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface {
}
