<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use ErrorException;
use ReflectionProperty;
use Throwable;

use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\error\PHPError;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;
use rosasurfer\ministruts\phpstan\UserTypes as PHPStanUserTypes;

use function rosasurfer\ministruts\normalizeEOL;

use const rosasurfer\ministruts\NL;

/**
 * A trait adding the behavior of a {@link rosasurfer\ministruts\core\exception\Exception} to any custom {@link Throwable}.
 *
 * @phpstan-import-type STACKFRAME from PHPStanUserTypes
 */
trait ExceptionTrait {

    /**
     * Prepend a message to the exception's existing message. Used to enrich the exception with additional data.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function prependMessage(string $message): self {
        $this->message = $message.$this->message;
        return $this;
    }


    /**
     * Append a message to the exception's existing message. Used to enrich the exception with additional data.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function appendMessage(string $message): self {
        $this->message .= $message;
        return $this;
    }


    /**
     * Set the error code of an exception. Used to enrich the exception with additional data.
     * Ignored if the error code is already set.
     *
     * @param  int $code
     *
     * @return $this
     */
    public function setCode(int $code): self {
        if (!isset($this->code)) {
            $this->code = $code;
        }
        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function __toString(): string {
        return Exception::getVerboseMessage($this);
    }


    /**
     * Return a more verbose version of a {@link Throwable}'s message. The resulting message has the classname of the throwable
     * and in case of {@link \ErrorException}s also the severity level of the error prepended to the original message.
     *
     * @param  Throwable      $throwable         - throwable
     * @param  string         $indent [optional] - indent all lines by the specified value (default: no indentation)
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - message
     */
    public static function getVerboseMessage(Throwable $throwable, string $indent = '', ?ContentFilter $filter = null): string {
        $message = trim($throwable->getMessage());
        if ($filter) {
            $message = $filter->filterString($message);
        }

        if (!$throwable instanceof PHPError) {              // PHP errors are verbose enough
            $class = get_class($throwable);
            if ($throwable instanceof ErrorException) {     // a PHP error not created by this ErrorHandler
                $class .= '('.ErrorHandler::errorLevelDescr($throwable->getSeverity()).')';
            }
            $message = $class.($message=='' ? '' : ": $message");
        }

        if ($indent != '') {
            $message = str_replace(NL, NL.$indent, normalizeEOL($message));
        }
        return $indent.$message;
    }


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
