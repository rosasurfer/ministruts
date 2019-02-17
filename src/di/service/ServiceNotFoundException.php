<?php
namespace rosasurfer\di\service;

use rosasurfer\di\ContainerException;

use Psr\Container\NotFoundExceptionInterface;


/**
 * ServiceNotFoundException
 */
class ServiceNotFoundException extends ContainerException implements NotFoundExceptionInterface {
}
