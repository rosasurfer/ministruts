<?php
namespace rosasurfer\core\di\service;

use rosasurfer\core\di\ContainerException;

use Psr\Container\NotFoundExceptionInterface;


/**
 * ServiceNotFoundException
 */
class ServiceNotFoundException extends ContainerException implements NotFoundExceptionInterface {
}
