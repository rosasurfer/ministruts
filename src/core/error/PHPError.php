<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\error;

use ErrorException;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;
use rosasurfer\ministruts\core\exception\ExceptionInterface as RosasurferException;
use rosasurfer\ministruts\core\exception\ExceptionTrait as RosasurferExceptionTrait;

/**
 * An exception representing a PHP error. Provides some convenient helpers.
 */
class PHPError extends ErrorException implements RosasurferException {

    use RosasurferExceptionTrait, ObjectTrait, DiAwareTrait;

    /**
     * Create a new instance. Parameters are identical to the built-in PHP {@link \ErrorException} but stronger typed.
     *
     * @param  string $message  - error description
     * @param  int    $code     - error identifier, usually an application id
     * @param  int    $severity - PHP error reporting level of the error
     * @param  string $file     - the name of the file where the error occurred
     * @param  int    $line     - the line number in the file where the error occurred
     */
    public function __construct(string $message, int $code, int $severity, string $file, int $line) {
        parent::__construct($message, $code, $severity, $file, $line, null);
    }
}
