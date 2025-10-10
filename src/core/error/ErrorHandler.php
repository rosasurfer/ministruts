<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\error;

use LibXMLError;
use Throwable;

use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\exception\Exception;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\log\Logger;
use rosasurfer\ministruts\util\Trace;

use function rosasurfer\ministruts\preg_match;
use function rosasurfer\ministruts\strLeftTo;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\ERROR_LOG_DEFAULT;
use const rosasurfer\ministruts\KB;
use const rosasurfer\ministruts\L_ERROR;
use const rosasurfer\ministruts\L_FATAL;
use const rosasurfer\ministruts\L_INFO;
use const rosasurfer\ministruts\L_NOTICE;
use const rosasurfer\ministruts\L_WARN;
use const rosasurfer\ministruts\MB;
use const rosasurfer\ministruts\NL;
use const rosasurfer\ministruts\WINDOWS;

/**
 * A handler for unhandled PHP errors and exceptions.
 */
class ErrorHandler extends StaticClass {

    /** ignore PHP errors and exceptions */
    public const MODE_IGNORE = 1;

    /** log PHP errors and exceptions */
    public const MODE_LOG = 2;

    /** convert PHP errors to exceptions, log both */
    public const MODE_EXCEPTION = 3;


    /** @var ?int - the configured error reporting level */
    protected static ?int $reportingLevel = null;

    /** @var int - the configured error handling mode */
    protected static int $errorHandling = 0;

    /** @var ?callable - a previously active error handler (if any) */
    protected static $prevErrorHandler = null;

    /** @var bool - the configured exception handling status */
    protected static bool $exceptionHandling = false;

    /** @var ?callable - a previously active exception handler (if any) */
    protected static $prevExceptionHandler = null;

    /** @var bool - whether the script is in the shutdown phase */
    protected static bool $inShutdown = false;

    /** @var ?string - memory block reserved for handling out-of-memory errors */
    protected static ?string $oomEmergencyMemory = null;


    /**
     * Setup error handling.
     *
     * @param  ?int $level - error reporting level
     * @param  int  $mode  - error handling mode, one of [MODE_IGNORE | MODE_LOG | MODE_EXCEPTION]
     *
     * @return void
     */
    public static function setupErrorHandling(?int $level, int $mode): void {
        if ($mode < self::MODE_IGNORE || $mode > self::MODE_EXCEPTION) {
            throw new InvalidValueException('Invalid parameter $mode: '.$mode);
        }

        switch ($mode) {
            case self::MODE_IGNORE:
                self::$errorHandling = 0;
                self::$exceptionHandling = false;
                break;

            case self::MODE_LOG:
            case self::MODE_EXCEPTION:
                self::$errorHandling = $mode;
                self::$prevErrorHandler = set_error_handler(__CLASS__.'::handleError');

                self::$exceptionHandling = true;
                self::$prevExceptionHandler = set_exception_handler(__CLASS__.'::handleException');

                if (isset($level)) {
                    self::$reportingLevel = $level;
                    error_reporting($level);
                }
                break;
        }
        self::setupShutdownHandler();                               // always setup a shutdown hook
    }


    /**
     * Setup a script shutdown handler to handle errors not passed to a registered error handler.
     * The callback should be first on the shutdown function stack.
     *
     * @return void
     */
    protected static function setupShutdownHandler(): void {
        static $handlerRegistered = false;

        if (!$handlerRegistered) {
            register_shutdown_function(__CLASS__.'::onShutdown');
            self::$oomEmergencyMemory = str_repeat('*', 500*KB);    // allocate some memory for OOM handling
            $handlerRegistered = true;
        }
    }


    /**
     * A handler for PHP errors. Errors are handled if covered by the active error reporting level. They are either
     * logged or converted to {@link PHPError} exceptions and thrown back.
     *
     * Errors of level E_DEPRECATED, E_USER_DEPRECATED, E_USER_NOTICE or E_USER_WARNING are logged only.
     *
     * @param  int     $level              - error severity level
     * @param  string  $message            - error message
     * @param  string  $file               - name of file where the error occurred
     * @param  int     $line               - line of file where the error occurred
     * @param  mixed[] $symbols [optional] - symbol table at the point of the error (removed since PHP8.0)
     *
     * @return bool - TRUE  if the error was handled and PHP should call error_clear_last();
     *                FALSE if the error was not handled and PHP should not call error_clear_last()
     *
     * @throws PHPError
     */
    public static function handleError(int $level, string $message, string $file, int $line, array $symbols = []): bool {
        //\rosasurfer\ministruts\echof('ErrorHandler::handleError(inShutdown='.(int)self::$inShutdown.') '.self::errorLevelToStr($level).': '.$message.', in '.$file.', line '.$line);
        if (!self::$errorHandling) return false;

        // anonymous function to chain a previously active handler
        $args = func_get_args();
        $prevErrorHandler = static function() use ($args) {
            if (self::$prevErrorHandler) {
                (self::$prevErrorHandler)(...$args);
            }
            return true;                                                    // tell PHP to call error_clear_last()
        };

        // ignore suppressed errors and errors not covered by the active reporting level
        $reportingLevel = self::$reportingLevel ?? error_reporting();
        if (!$reportingLevel)            return $prevErrorHandler();        // the @ operator was specified (since PHP8 some errors can't be silenced anymore)
        if (!($reportingLevel & $level)) return $prevErrorHandler();        // the error is not covered by the active reporting level

        // convert error to a PHPError exception
        $message = strLeftTo($message, ' (this will throw an Error in a future version of PHP)', -1);
        $exception = new PHPError($message, 0, $level, $file, $line);
        $trace = Trace::unwindTraceToLocation($exception->getTrace(), $file, $line);
        Exception::modifyException($exception, $trace);           // let the stacktrace point to the trigger statement

        // handle the error accordingly
        $neverThrow = (bool)($level & (E_DEPRECATED | E_USER_DEPRECATED | E_USER_NOTICE | E_USER_WARNING));

        if ($neverThrow || self::$errorHandling == self::MODE_LOG) {
            // only log the error
            switch ($level) {
                case E_NOTICE           : $logLevel = L_NOTICE; break;
                case E_WARNING          : $logLevel = L_WARN;   break;
                case E_ERROR            : $logLevel = L_ERROR;  break;
                case E_RECOVERABLE_ERROR: $logLevel = L_ERROR;  break;
                case E_CORE_WARNING     : $logLevel = L_WARN;   break;
                case E_CORE_ERROR       : $logLevel = L_ERROR;  break;
                case E_COMPILE_WARNING  : $logLevel = L_WARN;   break;
                case E_COMPILE_ERROR    : $logLevel = L_ERROR;  break;
                case E_PARSE            : $logLevel = L_ERROR;  break;
                case E_USER_NOTICE      : $logLevel = L_NOTICE; break;
                case E_USER_WARNING     : $logLevel = L_WARN;   break;
                case E_USER_ERROR       : $logLevel = L_ERROR;  break;
                case E_USER_DEPRECATED  : $logLevel = L_INFO;   break;
                case E_DEPRECATED       : $logLevel = L_INFO;   break;
                case E_STRICT           : $logLevel = L_NOTICE; break;

                default: $logLevel = L_ERROR;
            }

            // catch recursive exceptions
            try {
                Logger::log($exception, $logLevel, ['error-handler' => true]);
                // chain a previously active handler
                return $prevErrorHandler();
            }
            catch (Throwable $next) {
                self::handleRecursiveException($exception, $next);
            }
            return false;
        }

        // Problem: (1) Compile time errors can't be thrown back as exceptions. An installed exception handler is ignored for:
        //              E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING
        //          (2) require() is identical to include() except upon failure it will also produce a fatal E_COMPILE_ERROR error.
        //
        //          If those errors show up here the error handler was called manually.
        //
        // Solution: Call the exception handler manually.
        $cantThrow = (bool)($level & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING));
        if ($cantThrow) {
            $exception->prependMessage('PHP '.self::errorLevelDescr($level).': ');
            $currentHandler = set_exception_handler(null);
            restore_exception_handler();
            if ($currentHandler) {
                ($currentHandler)($exception);
            }
            return true;
        }

        // throw back everything else
        throw $exception->prependMessage('PHP Error: ');
    }


    /**
     * A handler for uncatched exceptions. After the handler returns PHP terminates the script.
     *
     * @param  Throwable $exception - the unhandled throwable
     *
     * @return void
     */
    public static function handleException(Throwable $exception): void {
        //\rosasurfer\ministruts\echof('ErrorHandler::handleException(inShutdown='.(int)self::$inShutdown.') '.$exception->getMessage());
        if (!self::$exceptionHandling) return;

        // catch exceptions thrown by logger or chained exception handler
        try {
            Logger::log($exception, L_FATAL, ['error-handler' => true]);

            // chain a previously active handler
            if (self::$prevExceptionHandler) {
                (self::$prevExceptionHandler)($exception);
            }
        }
        catch (Throwable $next) {
            self::handleRecursiveException($exception, $next);
        }
    }


    /**
     * Process recursive exceptions. Called if {@link Logger} or chained exception handlers throw exceptions by themself.
     * In such a case both exceptions are logged to the default log with only limited details.
     *
     * Note: If this method is reached something in the error handling went seriously wrong. Therefore this method should
     *       use only minimal external dependencies.
     *
     * @param  Throwable $first - primary exception
     * @param  Throwable $next  - next exception
     *
     * @return void
     */
    private static function handleRecursiveException(Throwable $first, Throwable $next): void {
        //\rosasurfer\ministruts\echof('ErrorHandler::handleRecursiveException(inShutdown='.(int)self::$inShutdown.') '.$next->getMessage());
        try {
            $indent = ' ';
            $msg  = trim(Exception::getVerboseMessage($first, $indent));
            $msg  = $indent.($first instanceof PHPError ? '':'[FATAL] Unhandled ').$msg.NL;
            $msg .= $indent.'in '.$first->getFile().' on line '.$first->getLine().NL;
            $msg .= NL;
            $msg .= $indent.'Stacktrace:'.NL;
            $msg .= $indent.'-----------'.NL;
            $msg .= Trace::convertTraceToString($first, $indent);
            $msg .= NL;
            $msg .= NL;
            $msg .= $indent.'followed by'.NL;
            $msg .= $indent.trim(Exception::getVerboseMessage($next, $indent)).NL;
            $msg .= $indent.'in '.$next->getFile().' on line '.$next->getLine().NL;
            $msg .= NL;
            $msg .= $indent.'Stacktrace:'.NL;
            $msg .= $indent.'-----------'.NL;
            $msg .= Trace::convertTraceToString($next, $indent);
            $msg .= NL;

            // log everything to the system logger (never throws an error)
            $msg = str_replace(chr(0), '\0', $msg);         // replace NUL bytes which would mess up the logfile
            if (WINDOWS) {
                $msg = str_replace(NL, PHP_EOL, $msg);
            }
            error_log($msg, ERROR_LOG_DEFAULT);

            if (CLI) {
                echo $msg;
            }
            else {
                // TODO
            }
        }
        catch (Throwable $ex) {}                            // intentionally eat it
    }


    /**
     * Manually called handler for exceptions occurring in object destructors.
     *
     * Attempting to throw an exception from a destructor during script shutdown causes a fatal error which is not catchable
     * by an installed error handler. Therefore this method must be called manually from destructors if an exception occurred.
     * If the script is in the shutdown phase the exception is passed on to the regular exception handler and the script is
     * terminated. If the script is not in the shutdown phase this method does nothing.
     *
     * For an example see this package's README file.
     *
     * @param  Throwable $exception
     *
     * @return Throwable - the same exception
     *
     * @link  https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.destructor
     */
    public static function handleDestructorException(Throwable $exception): Throwable {
        if (self::$inShutdown) {
            $currentHandler = set_exception_handler(static function(): void {});
            restore_exception_handler();

            if ($currentHandler) {
                ($currentHandler)($exception);      // Calling exit() is the only way to prevent the immediately following
                exit(1);                            // non-catchable fatal error. However, calling exit() in a destructor will
            }                                       // also prevent execution of any remaining shutdown logic.
        }
        return $exception;
    }


    /**
     * A handler executed when the script shuts down.
     *
     * @return void
     */
    public static function onShutdown(): void {
        self::$inShutdown = true;

        // Errors showing up here haven't been passed to an installed custom error handler. That's compile time
        // errors (E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING) or fatal
        // runtime errors (e.g. "out-of-memory").
        //
        // @see  https://www.php.net/manual/en/function.set-error-handler.php

        $error = error_get_last();
        if ($error) {
            // release the reserved memory, so preg_match() survives a potential OOM condition
            self::$oomEmergencyMemory = $match = null;

            if (preg_match('/^Allowed memory size of ([0-9]+) bytes exhausted/', $error['message'], $match)) {
                // we have an OOM error, widen the current limit to survive regular error handling
                ini_set('memory_limit', (string)((int)$match[1] + 10*MB));
            }

            // call the active error handler manually
            $currentHandler = set_error_handler(null);
            restore_error_handler();
            if ($currentHandler) {
                ($currentHandler)(...array_values($error));
            }
        }
    }


    /**
     * Return the description of a single error reporting level.
     *
     * @param  int $level - single error reporting level
     *
     * @return string
     */
    public static function errorLevelDescr(int $level): string {
        static $levels = [
            E_ERROR             => 'Error',                     //     1
            E_WARNING           => 'Warning',                   //     2
            E_PARSE             => 'Parse Error',               //     4
            E_NOTICE            => 'Notice',                    //     8
            E_CORE_ERROR        => 'Core Error',                //    16
            E_CORE_WARNING      => 'Core Warning',              //    32
            E_COMPILE_ERROR     => 'Compile Error',             //    64
            E_COMPILE_WARNING   => 'Compile Warning',           //   128
            E_USER_ERROR        => 'User Error',                //   256
            E_USER_WARNING      => 'User Warning',              //   512
            E_USER_NOTICE       => 'User Notice',               //  1024
            E_STRICT            => 'Strict',                    //  2048
            E_RECOVERABLE_ERROR => 'Recoverable Error',         //  4096
            E_DEPRECATED        => 'Deprecated',                //  8192
            E_USER_DEPRECATED   => 'User Deprecated',           // 16384
        ];
        return $levels[$level] ?? '(unknown)';
    }


    /**
     * Return a string representation of the passed error reporting levels.
     *
     * @param  int  $flags              - error reporting levels
     * @param  bool $combine [optional] - whether to combine or list multiple levels (default: list)
     *
     * @return string
     */
    public static function errorLevelToStr(int $flags, bool $combine = false): string {
        // ordered by real-world priorities
        $allLevels = [
            E_PARSE             => 'E_PARSE',                   //     4
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',           //    64
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',         //   128
            E_CORE_ERROR        => 'E_CORE_ERROR',              //    16
            E_CORE_WARNING      => 'E_CORE_WARNING',            //    32
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',       //  4096
            E_ERROR             => 'E_ERROR',                   //     1
            E_WARNING           => 'E_WARNING',                 //     2
            E_NOTICE            => 'E_NOTICE',                  //     8
            E_STRICT            => 'E_STRICT',                  //  2048
            E_DEPRECATED        => 'E_DEPRECATED',              //  8192
            E_USER_ERROR        => 'E_USER_ERROR',              //   256
            E_USER_WARNING      => 'E_USER_WARNING',            //   512
            E_USER_NOTICE       => 'E_USER_NOTICE',             //  1024
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',         // 16384
        ];

        $set = $notset = [];

        foreach ($allLevels as $level => $description) {
            if ($flags & $level) {
                $set[] = $description;
            }
            else {
                $notset[] = $description;
            }
        }

        if ($combine) {
            if (sizeof($set) < sizeof($notset)) {
                $result = $set ? join(' | ', $set) : '0';
            }
            else {
                $result = join(' & ~', ['E_ALL', ...$notset]);
            }
        }
        else {
            $result = $set ? join(', ', $set) : '0';
        }
        return $result;
    }


    /**
     * Return a readable representation of the specified LibXML errors.
     *
     * @param  LibXMLError[] $errors - array of LibXML errors, e.g. as returned by <tt>libxml_get_errors()</tt>
     * @param  string[]      $lines  - the XML causing the errors split by line
     *
     * @return string - readable error representation or an empty string if parameter $errors is empty
     */
    public static function libxmlErrorsToStr(array $errors, array $lines): string {
        $msg = '';

        foreach ($errors as $error) {
            $msg .= 'line '.$error->line.': ';

            switch ($error->level) {
                case LIBXML_ERR_NONE:
                    break;
                case LIBXML_ERR_WARNING:
                    $msg .= 'parser warning';
                    break;
                case LIBXML_ERR_ERROR:
                    // @see  https://gnome.pages.gitlab.gnome.org/libxml2/devhelp/libxml2-xmlerror.html#xmlParserErrors
                    switch ($error->code) {
                        case 201:
                        case 202:
                        case 203:
                        case 204:
                        case 205:
                            $msg .= 'namespace error';
                            break 2;
                    }
                default:
                    $msg .= 'parser error';
            }
            $msg .= ': '.trim($error->message)             .NL;
            $msg .= ($lines[$error->line - 1] ?? '')       .NL;
            $msg .= str_repeat(' ', $error->column - 1).'^'.NL.NL;
        }

        return trim($msg);
    }
}
