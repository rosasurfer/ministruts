<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

/**
 * Exception to mark errors caused by non-synchronized modifications of shared resources.
 */
class ConcurrentModificationException extends RuntimeException {
}
