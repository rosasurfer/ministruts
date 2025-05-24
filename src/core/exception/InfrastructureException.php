<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

/**
 * Exception to mark errors of the infrastructure, ie. database, network or socket errors.
 */
class InfrastructureException extends RuntimeException {
}
