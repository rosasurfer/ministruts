<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use ErrorException;
use ReflectionProperty;
use Throwable;

use rosasurfer\ministruts\core\error\PHPError;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;
use rosasurfer\ministruts\phpstan\UserTypes as PHPStanUserTypes;
use rosasurfer\ministruts\util\PHP;
use rosasurfer\ministruts\util\Trace;

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
        return static::toString($this);
    }


    /**
     * Return a formatted and more verbose version of an exceptions's string representation. Contains infos about nested exceptions.
     *
     * @param  Throwable      $exception         - exception
     * @param  string         $indent [optional] - indent all lines by the specified value (default: no indentation)
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - string representation ending with EOL
     */
    public static function toString(Throwable $exception, string $indent = '', ?ContentFilter $filter = null): string {
        $str  = static::getVerboseMessage($exception, $indent).NL;
        $str .= $indent.'in '.$exception->getFile().' on line '.$exception->getLine().NL;
        $str .= NL;
        $str .= $indent.'Stacktrace:'.NL;
        $str .= $indent.'-----------'.NL;
        $str .= Trace::convertTraceToString($exception, $indent);
        return $str;
    }


    /**
     * Return a more verbose version of a {@link Throwable}'s message. The resulting message has either the severity level of
     * an {@link \ErrorException} or the {@link \Exception}'s classname prepended.
     *
     * @param  Throwable      $exception         - exception
     * @param  string         $indent [optional] - indent all lines by the specified value (default: no indentation)
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - message not ending with EOL
     */
    public static function getVerboseMessage(Throwable $exception, string $indent = '', ?ContentFilter $filter = null): string {
        $message = trim($exception->getMessage());
        if ($filter) {
            $message = $filter->filterString($message);
        }

        if (!$exception instanceof PHPError) {              //  PHPError::getMessage() already returns the verbose message
            $class = get_class($exception);
            if ($exception instanceof ErrorException) {     // a built-in PHP error not created by our ErrorHandler
                $class .= '('.PHP::errorLevelDescr($exception->getSeverity()).')';
            }
            $message = $class.($message=='' ? '' : ': ').$message;
        }

        if ($indent != '') {
            $message = str_replace(NL, NL.$indent, normalizeEOL($message));
        }
        return $indent.$message;
    }


    /**
     * Patch stacktrace and error location of a {@link Throwable}. Used for removing frames from an exeption's stacktrace, so the
     * location properties point to the user-land code which triggered the exception.
     *
     * @param  Throwable $exception       - the exception to modify
     * @param  array[]   $trace           - new stacktrace
     * @param  string    $file [optional] - new filename of the error location (default: unchanged)
     * @param  int       $line [optional] - new line number of the error location (default: unchanged)
     *
     * @return void
     *
     * @phpstan-param list<STACKFRAME> $trace
     *
     * @see \rosasurfer\ministruts\phpstan\STACKFRAME
     */
    public static function patchStackTrace(Throwable $exception, array $trace, string $file = '', int $line = 0): void {
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
