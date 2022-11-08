<?php
namespace rosasurfer\core\error;

use rosasurfer\Application;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\log\Logger;

use function rosasurfer\echoPre;
use function rosasurfer\ini_get_bool;
use function rosasurfer\strLeftTo;
use function rosasurfer\true;

use const rosasurfer\CLI;
use const rosasurfer\ERROR_LOG_DEFAULT;
use const rosasurfer\L_ERROR;
use const rosasurfer\L_FATAL;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\L_WARN;
use const rosasurfer\NL;
use const rosasurfer\MB;


/**
 * A global handler for internal PHP errors and uncatched exceptions.
 */
class ErrorHandler extends StaticClass {


    /** @var int - ignore internal PHP errors */
    const ERRORS_IGNORE = 1;

    /** @var int - log internal PHP errors */
    const ERRORS_LOG = 2;

    /** @var int - convert internal PHP errors to ErrorExceptions and throw them back */
    const ERRORS_EXCEPTION = 4;

    /** @var int - ignore exceptions */
    const EXCEPTIONS_IGNORE = 8;

    /** @var int - catch and log exceptions */
    const EXCEPTIONS_CATCH = 16;


    /** @var int - the configured error handling mode */
    protected static $errorHandlingMode = 0;

    /** @var callable - a previously active error handler (if any) */
    protected static $prevErrorHandler;

    /** @var bool - the configured exception handling status */
    protected static $exceptionHandling = false;

    /** @var callable - a previously active exception handler (if any) */
    protected static $prevExceptionHandler;

    /** @var bool - whether the script is in the shutdown phase */
    protected static $inShutdown = false;

    /** @var string - RegExp for detecting out-of-memory errors */
    protected static $oomRegExp = '/^Allowed memory size of ([0-9]+) bytes exhausted/';


    /**
     * Setup error handling.
     *
     * @param  int $mode - error handling mode: [ERRORS_IGNORE | ERRORS_LOG | ERRORS_EXCEPTION]
     */
    public static function setupErrorHandling($mode) {
        if (!in_array($mode, [self::ERRORS_IGNORE, self::ERRORS_LOG, self::ERRORS_EXCEPTION])) return;

        if ($mode == self::ERRORS_IGNORE) {
            static::$errorHandlingMode = 0;
        }
        else {
            static::$errorHandlingMode = $mode;
            static::$prevErrorHandler  = set_error_handler(__CLASS__ . '::handleError');
            static::setupShutdownHandler();                     // handle fatal runtime errors during script shutdown
        }
    }


    /**
     * Setup exception handling.
     *
     * @param  int $mode - exception handling mode: [EXCEPTIONS_IGNORE | EXCEPTIONS_CATCH]
     */
    public static function setupExceptionHandling($mode) {
        if (!in_array($mode, [self::EXCEPTIONS_IGNORE, self::EXCEPTIONS_CATCH])) return;
        static::$exceptionHandling = ($mode != self::EXCEPTIONS_IGNORE);

        if (static::$exceptionHandling) {
            static::$prevExceptionHandler = set_exception_handler(__CLASS__ . '::handleException');
            static::setupShutdownHandler();                     // setup handling of exceptions during script shutdown
        }
    }


    /**
     * Setup a script shutdown handler.
     */
    protected static function setupShutdownHandler() {
        static $handlerRegistered = false;
        static $oomEmergencyMemory;                             // memory block freed when handling out-of-memory errors

        // The following function should be the very first function on the shutdown function stack.
        if (!$handlerRegistered) {
            register_shutdown_function(function() use (&$oomEmergencyMemory) {
                /**
                 * Flag to detect entering of the script's shutdown phase to be capable of handling destructor exceptions
                 * during shutdown in a different way. Otherwise destructor exceptions will cause fatal errors.
                 *
                 * @link  http://php.net/manual/en/language.oop5.decon.php
                 * @see   ErrorHandler::handleDestructorException()
                 */
                self::$inShutdown = true;
                echoPre('shutdown: '.__METHOD__);

                /**
                 * If regular PHP error handling is enabled catch and handle fatal runtime errors.
                 *
                 * @link  https://github.com/bugsnag/bugsnag-laravel/issues/226
                 * @link  https://gist.github.com/dominics/61c23f2ded720d039554d889d304afc9
                 */
                if (self::$errorHandlingMode) {
                    $oomEmergencyMemory = $match = null;                        // release the reserved memory, meant to be used by preg_match()
                    $error = error_get_last();
                    if ($error && $error['type']==E_ERROR && preg_match(self::$oomRegExp, $error['message'], $match)) {
                        ini_set('memory_limit', (int)$match[1] + 10*MB);        // allocate memory for the regular handler

                        $currentHandler = set_error_handler(function() {});     // handle the error regularily
                        restore_error_handler();
                        $currentHandler && call_user_func($currentHandler, ...array_values($error));
                    }
                }
            });
            $oomEemergencyMemory = str_repeat('*', 1*MB);                       // reserve some extra memory to survive OOM errors
            $handlerRegistered = true;
        }
    }


    /**
     * A handler for internal PHP errors.
     *
     * Errors are handled if covered by the currently active error reporting level. Errors of levels E_DEPRECATED, E_USER_DEPRECATED,
     * E_USER_NOTICE and E_USER_WARNING are never converted to PHP exceptions and script execution continues normally.
     *
     * All other errors are handled according to the configured error handling mode (either logged or converted to PHP exceptions and
     * thrown back).
     *
     * @param  int                  $level              - error severity level
     * @param  string               $message            - error message
     * @param  string               $file               - name of file where the error occurred
     * @param  int                  $line               - line of file where the error occurred
     * @param  array<string, mixed> $symbols [optional] - symbol table at the point of the error
     *
     * @return bool - TRUE,  if the error was successfully handled.
     *                FALSE, if the error shall be processed as if no error handler was installed.
     */
    public static function handleError($level, $message, $file, $line, array $symbols = null) {
        echoPre('ErrorHandler::handleError()  '.static::errorLevelToStr($level).': '.$message);
        //echoPre('ErrorHandler::handleError()  '.static::errorLevelToStr($level).': '.$message.', in '.$file.', line '.$line);

        // ignore suppressed errors and errors not covered by the current reporting level
        $reportingLevel = error_reporting();
        if (!static::$errorHandlingMode) return false;
        if (!$reportingLevel)            return false;                          // the @ operator was specified
        if (!($reportingLevel & $level)) return true;                           // the error is not covered by the active reporting level

        $message = strLeftTo($message, ' (this will throw an Error in a future version of PHP)', -1);

        $context = [];
        $context['file'] = $file;
        $context['line'] = $line;

        // wrap all errors in the matching PHPError exception
        switch ($level) {
            case E_PARSE            : $exception = new PHPParseError      ($message, 0, $level, $file, $line); break;
            case E_COMPILE_WARNING  : $exception = new PHPCompileWarning  ($message, 0, $level, $file, $line); break;
            case E_COMPILE_ERROR    : $exception = new PHPCompileError    ($message, 0, $level, $file, $line); break;
            case E_CORE_WARNING     : $exception = new PHPCoreWarning     ($message, 0, $level, $file, $line); break;
            case E_CORE_ERROR       : $exception = new PHPCoreError       ($message, 0, $level, $file, $line); break;
            case E_STRICT           : $exception = new PHPStrict          ($message, 0, $level, $file, $line); break;
            case E_DEPRECATED       : $exception = new PHPDeprecated      ($message, 0, $level, $file, $line); break;
            case E_NOTICE           : $exception = new PHPNotice          ($message, 0, $level, $file, $line); break;
            case E_WARNING          : $exception = new PHPWarning         ($message, 0, $level, $file, $line); break;
            case E_ERROR            : $exception = new PHPError           ($message, 0, $level, $file, $line); break;
            case E_RECOVERABLE_ERROR: $exception = new PHPRecoverableError($message, 0, $level, $file, $line); break;
            case E_USER_DEPRECATED  : $exception = new PHPUserDeprecated  ($message, 0, $level, $file, $line); break;
            case E_USER_NOTICE      : $exception = new PHPUserNotice      ($message, 0, $level, $file, $line); break;
            case E_USER_WARNING     : $exception = new PHPUserWarning     ($message, 0, $level, $file, $line); break;
            case E_USER_ERROR       : $exception = new PHPUserError       ($message, 0, $level, $file, $line); break;

            default                 : $exception = new PHPUnknownError    ($message, 0, $level, $file, $line);
        }

        // handle it according to the error handling mode
        if (static::$errorHandlingMode == self::ERRORS_LOG) {
            Logger::log($exception, L_ERROR, $context);

            if (static::$prevErrorHandler) {                                    // chain a previous error handler
                call_user_func(static::$prevErrorHandler, ...func_get_args());  // a possibly static handler must be invoked by call_user_func()
            }
            return true;
        }

        /**
         * Handle cases where throwing an exception is not possible or not allowed.
         *
         * Fatal out-of-memory errors:
         * ---------------------------
         */
        if (self::$inShutdown && $level==E_ERROR && preg_match(self::$oomRegExp, $message)) {
            return true(Logger::log($message, L_FATAL, $context));              // logging the error is sufficient as there is no stacktrace anyway
        }

        /**
         * Errors triggered by require() or require_once():
         * ------------------------------------------------
         * Problem:  PHP errors triggered by require() or require_once() are non-catchable errors and do not follow regular
         *           application flow. PHP terminates the script after leaving the error handler, thrown exceptions are
         *           ignored. This termination is intended behaviour and the main difference to include() and include_once().
         * Solution: Manually call the exception handler.
         *
         * @see  http://stackoverflow.com/questions/25584494/php-set-exception-handler-not-working-for-error-thrown-in-set-error-handler-cal
         */
        $trace = $exception->getBetterTrace();
        if ($trace) {                                                           // after a fatal error the trace may be empty
            $function = DebugHelper::getFQFunctionName($trace[0]);
            if ($function=='require' || $function=='require_once') {
                $currentHandler = set_exception_handler(function() {});
                restore_exception_handler();
                $currentHandler && call_user_func($currentHandler, $exception); // We MUST use call_user_func() as a static handler cannot be invoked dynamically.
                return (bool)$currentHandler;                                   // PHP will terminate the script anyway
            }
        }

        // throw back everything else
        throw $exception;
    }


    /**
     * A handler for uncatched exceptions. The exception is sent to the default logger with loglevel L_FATAL.
     * After the handler returns PHP terminates the script.
     *
     * @param  \Exception|\Throwable $exception - the unhandled exception (PHP5) or throwable (PHP7)
     */
    public static function handleException($exception) {
        echoPre('ErrorHandler::handleException');
        if (!static::$exceptionHandling) return;

        $context = [
            'class'               => __CLASS__,
            'file'                => $exception->getFile(),     // If the location is not preset the logger will resolve this
            'line'                => $exception->getLine(),     // exception handler as the originating location.
            'unhandled-exception' => true,                      // flag to signal origin
        ];

        // Exceptions thrown from the exception handler itself will not be passed back to the handler again but instead
        // terminate the script with an uncatchable fatal error. To prevent this they are handled explicitely.
        $second = null;
        try {
            Assert::throwable($exception);
            Logger::log($exception, L_FATAL, $context);         // log with the highest level
        }
        catch (\Throwable $second) {}
        catch (\Exception $second) {}

        if ($second)  {
            // secondary exception: the application is crashing, last try to log
            $indent = ' ';
            $msg2  = '[FATAL] Unhandled '.trim(DebugHelper::composeBetterMessage($second)).NL;
            $file  = $second->getFile();
            $line  = $second->getLine();
            $msg2 .= $indent.'in '.$file.' on line '.$line.NL.NL;
            $msg2 .= $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $msg2 .= DebugHelper::getBetterTraceAsString($second, $indent);

            // primary (the causing) exception
            if (isset($context['cliMessage'])) {
                $msg1 = $context['cliMessage'];
                if (isset($context['cliExtra']))
                    $msg1 .= $context['cliExtra'];
            }
            else {
                $msg1  = $indent.'Unhandled '.trim(DebugHelper::composeBetterMessage($exception)).NL;
                $file  = $exception->getFile();
                $line  = $exception->getLine();
                $msg1 .= $indent.'in '.$file.' on line '.$line.NL.NL;
                $msg1 .= $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
                $msg1 .= DebugHelper::getBetterTraceAsString($exception, $indent);
            }

            $msg  = $msg2.NL;
            $msg .= $indent.'caused by'.NL;
            $msg .= $msg1;
            $msg  = str_replace(chr(0), '\0', $msg);            // replace NUL bytes which mess up the logfile

            if (CLI) echo $msg.NL;                              // full second exception
            error_log(trim($msg), ERROR_LOG_DEFAULT);
        }

        if (!CLI) {                                             // web interface: prevent an empty page
            try {
                if (Application::isAdminIP() || ini_get_bool('display_errors')) {
                    if ($second) {                              // full second exception, full log location
                        echoPre($second);
                        echoPre('error log: '.(strlen($errorLog=ini_get('error_log')) ? $errorLog : 'web server'));
                    }
                }
                else echoPre('application error (see error log)');
            }
            catch (\Throwable $third) { echoPre('application error (see error log)'); }
            catch (\Exception $third) { echoPre('application error (see error log)'); }
        }
        else {                                                  // CLI: set a non-zero exit code
            exit(1);
        }
    }


    /**
     * Manually called handler for exceptions occurring in object destructors.
     *
     * Attempting to throw an exception from a destructor during script shutdown causes a fatal error which is not catchable
     * by an installed error handler. Therefore this method must be called manually from object destructors if an exception
     * occurred. If the script is in the shutdown phase the exception is passed on to the regular exception handler and the
     * script is terminated. If the script is not in the shutdown phase this method ignores the exception and regular
     * exception handling takes over. For an example see this package's README file.
     *
     * @param  \Exception|\Throwable $exception - exception (PHP5) or throwable (PHP7)
     *
     * @return \Exception|\Throwable - the same exception or throwable
     *
     * @link   http://php.net/manual/en/language.oop5.decon.php
     */
    public static function handleDestructorException($exception) {
        if (self::$inShutdown) {
            $currentHandler = set_exception_handler(function() {});
            restore_exception_handler();

            if ($currentHandler) {
                call_user_func($currentHandler, $exception);    // We MUST use call_user_func() as a static handler cannot be invoked dynamically.
                exit(1);                                        // Calling exit() is the only way to prevent the immediately following
            }                                                   // non-catchable fatal error. However, calling exit() in a destructor will
        }                                                       // also prevent execution of any remaining shutdown routines.
        return $exception;
    }


    /**
     * Explicitly called handler for exceptions raised in object::__toString() methods. This allows regular handling of
     * exceptions thrown from object::__toString() which in PHP < 7.4 is not possible due to an internal PHP design issue.
     *
     * PHP < 7.4 behaviour:
     *  PHP Fatal error:  Method object::__toString() must not throw an exception in {file} on {line}.
     *
     * @param  \Exception|\Throwable $exception - exception (PHP5) or throwable (PHP7)
     *
     * @see  https://bugs.php.net/bug.php?id=53648
     * @see  https://wiki.php.net/rfc/tostring_exceptions
     * @see  https://github.com/symfony/symfony/blob/1c110fa1f7e3e9f5daba73ad52d9f7e843a7b3ff/src/Symfony/Component/Debug/ErrorHandler.php#L457-L489
     */
    public static function handleToStringException($exception) {
        echoPre('ErrorHandler::handleToStringException');
        $currentHandler = set_exception_handler(function() {});
        restore_exception_handler();

        if ($currentHandler) {
            call_user_func($currentHandler, $exception);        // We MUST use call_user_func() as a static handler cannot be invoked dynamically.
            exit(1);                                            // Calling exit() is the only way to prevent the immediately following
        }                                                       // non-catchable fatal error.
    }


    /**
     * Return a readable representation of an error reporting level.
     *
     * @param  int $level - error reporting level
     *
     * @return string
     */
    public static function errorLevelToStr($level) {
        Assert::int($level);

        $levels = [
            E_ERROR             => 'E_ERROR',                   //     1
            E_WARNING           => 'E_WARNING',                 //     2
            E_PARSE             => 'E_PARSE',                   //     4
            E_NOTICE            => 'E_NOTICE',                  //     8
            E_CORE_ERROR        => 'E_CORE_ERROR',              //    16
            E_CORE_WARNING      => 'E_CORE_WARNING',            //    32
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',           //    64
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',         //   128
            E_USER_ERROR        => 'E_USER_ERROR',              //   256
            E_USER_WARNING      => 'E_USER_WARNING',            //   512
            E_USER_NOTICE       => 'E_USER_NOTICE',             //  1024
            E_STRICT            => 'E_STRICT',                  //  2048
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',       //  4096
            E_DEPRECATED        => 'E_DEPRECATED',              //  8192
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',         // 16384
        ];

        if      (!$level)                                                       $levels = ['0'];                        //     0
        else if (($level &  E_ALL)                  ==  E_ALL)                  $levels = ['E_ALL'];                    // 32767
        else if (($level & (E_ALL & ~E_DEPRECATED)) == (E_ALL & ~E_DEPRECATED)) $levels = ['E_ALL & ~E_DEPRECATED'];    // 24575
        else {
            foreach ($levels as $key => $value) {
                if ($level & $key) continue;
                unset($levels[$key]);
            }
        }
        return join('|', $levels);
    }
}
