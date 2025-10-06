<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use Exception;
use ReflectionProperty;
use Throwable;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;
use rosasurfer\ministruts\phpstan\UserTypes as PHPStanUserTypes;

/**
 * Base class for all "rosasurfer" exceptions. Provides some additional convenient helpers.
 *
 * @phpstan-import-type STACKFRAME from PHPStanUserTypes
 */
class RosasurferException extends Exception implements RosasurferExceptionInterface {

    use RosasurferExceptionTrait, ObjectTrait, DiAwareTrait;

    /**
     * Modify the location related properties of a {@link Throwable}.
     *
     * @param  Throwable $exception       - exception to modify
     * @param  array[]   $trace           - stacktrace
     * @param  string    $file [optional] - filename of the error location (default: unchanged)
     * @param  int       $line [optional] - line number of the error location (default: unchanged)
     *
     * @return void
     *
     * @phpstan-param list<STACKFRAME> $trace
     *
     * @see \rosasurfer\ministruts\phpstan\STACKFRAME
     */
    public static function modifyException(Throwable $exception, array $trace, string $file = '', int $line = 0): void {
        // Throwable is either Error or Exception
        $className = get_class($exception);
        while ($parent = get_parent_class($className)) {
            $className = $parent;
        }

        $traceProperty = new ReflectionProperty($className, 'trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($exception, $trace);

        if (func_num_args() > 2) {
            $fileProperty = new ReflectionProperty($className, 'file');
            $fileProperty->setAccessible(true);
            $fileProperty->setValue($exception, $file);
        }

        if (func_num_args() > 3) {
            $lineProperty = new ReflectionProperty($className, 'line');
            $lineProperty->setAccessible(true);
            $lineProperty->setValue($exception, $line);
        }
    }
}
