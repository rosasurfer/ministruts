<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\service;

use rosasurfer\ministruts\core\di\ContainerException;

use Psr\Container\NotFoundExceptionInterface;

/**
 * ServiceNotFoundException
 */
class ServiceNotFoundException extends ContainerException implements NotFoundExceptionInterface {
}
