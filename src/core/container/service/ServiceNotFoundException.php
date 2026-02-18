<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\container\service;

use rosasurfer\ministruts\core\container\ContainerException;

use Psr\Container\NotFoundExceptionInterface;

/**
 * ServiceNotFoundException
 */
class ServiceNotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
