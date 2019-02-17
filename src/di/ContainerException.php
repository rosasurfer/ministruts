<?php
namespace rosasurfer\di;

use rosasurfer\exception\RuntimeException;

use Psr\Container\ContainerExceptionInterface;


/**
 * NotFoundException
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface {
}
