<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\error;

use BadMethodCallException;
use ErrorException;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;
use rosasurfer\ministruts\core\exception\ExceptionInterface as RosasurferException;
use rosasurfer\ministruts\core\exception\ExceptionTrait as RosasurferExceptionTrait;
use rosasurfer\ministruts\util\PHP;

/**
 * An exception representing a regular PHP error. Can only be created by the {@link ErrorHandler}.
 */
class PHPError extends ErrorException implements RosasurferException {

    use RosasurferExceptionTrait, ObjectTrait, DiAwareTrait;

    /**
     * Create a new instance. A PHP error can't have nested errors/exceptions.
     *
     * @param  string $message  - error description
     * @param  int    $code     - error identifier, usually an application id
     * @param  int    $severity - PHP error reporting level of the error
     * @param  string $file     - the name of the file where the error occurred
     * @param  int    $line     - the line number in the file where the error occurred
     */
    public function __construct(string $message, int $code, int $severity, string $file, int $line) {
        $stacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $class    = $stacktrace[1]['class'] ?? '';
        $function = $stacktrace[1]['function'] ?? '';

        // check instantiation context
        if (strcasecmp("{$class}::{$function}", __NAMESPACE__.'\ErrorHandler::handleError')) {
            throw new BadMethodCallException("PHPError instantiation is restricted to the error handler.");
        }

        // add hint to non-critical errors
        switch ($severity) {
            case E_USER_NOTICE:
            case E_USER_WARNING:
            case E_USER_ERROR:
            case E_USER_DEPRECATED:
            case E_DEPRECATED:
            case E_STRICT:
                $prefix = PHP::errorLevelDescr($severity);
                $message = $prefix.($message=='' ? '' : ": $message");
                break;
        }

        parent::__construct($message, $code, $severity, $file, $line, null);
    }
}
