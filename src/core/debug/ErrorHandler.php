<?php
namespace rosasurfer\core\debug;

use rosasurfer\Application;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\error\PHPCompileError;
use rosasurfer\core\exception\error\PHPCompileWarning;
use rosasurfer\core\exception\error\PHPCoreError;
use rosasurfer\core\exception\error\PHPCoreWarning;
use rosasurfer\core\exception\error\PHPError;
use rosasurfer\core\exception\error\PHPNotice;
use rosasurfer\core\exception\error\PHPParseError;
use rosasurfer\core\exception\error\PHPRecoverableError;
use rosasurfer\core\exception\error\PHPStrict;
use rosasurfer\core\exception\error\PHPUnknownError;
use rosasurfer\core\exception\error\PHPUserError;
use rosasurfer\core\exception\error\PHPWarning;
use rosasurfer\log\Logger;
use rosasurfer\util\PHP;

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
 * Gobal error handling
 */
class ErrorHandler extends StaticClass {


    /** @var int - error handling mode in which regular PHP errors are logged */
    const LOG_ERRORS = 1;

    /** @var int - error handling mode in which regular PHP errors are converted to exceptions and thrown back */
    const THROW_EXCEPTIONS = 2;


    /** @var int - the mode the error handler is configured for, can be either LOG_ERRORS or THROW_EXCEPTIONS */
    private static $errorMode;

    /** @var bool - whether the script is in the shutdown phase */
    private static $inShutdown = false;

    /** @var string - RegExp for detecting out-of-memory errors */
    private static $oomRegExp = '/^Allowed memory size of ([0-9]+) bytes exhausted/';


    /**
     * Setup global error handling.
     *
     * @param  int $mode - type of error handling: self::LOG_ERRORS | self::THROW_EXCEPTIONS
     */
    public static function setupErrorHandling($mode) {
        if     ($mode === self::LOG_ERRORS      ) self::$errorMode = self::LOG_ERRORS;
        elseif ($mode === self::THROW_EXCEPTIONS) self::$errorMode = self::THROW_EXCEPTIONS;
        else                                      return;

        set_error_handler(__CLASS__.'::handleError', error_reporting());
        self::setupShutdownHandler();                           // handle fatal runtime errors during script shutdown
    }


    /**
     * Setup global exception handling.
     */
    public static function setupExceptionHandling() {
        set_exception_handler(__CLASS__.'::handleException');
        self::setupShutdownHandler();                           // handle destructor exceptions during script shutdown
    }


    /**
     * Setup a script shutdown handler.
     */
    private static function setupShutdownHandler() {
        static $handlerRegistered = false;
        static $oomEmergencyMemory;                                                 // memory block freed when handling out-of-memory errors

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

                /**
                 * If regular PHP error handling is enabled catch and handle fatal runtime errors.
                 *
                 * @link  https://github.com/bugsnag/bugsnag-laravel/issues/226
                 * @link  https://gist.github.com/dominics/61c23f2ded720d039554d889d304afc9
                 */
                if (self::$errorMode) {
                    $oomEmergencyMemory = null;                                 // release the reserved memory, meant to be used by preg_match()
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
     * Global handler for traditional PHP errors.
     *
     * Errors are handled only if covered by the currently configured error reporting level. Errors of the levels
     * E_DEPRECATED, E_USER_DEPRECATED, E_USER_NOTICE and E_USER_WARNING are always logged and script execution continues
     * normally. All other errors are logged according to the configured error handling mode. Either they are logged and
     * script exceution continues normally, or they are wrapped in a PHPError exception and thrown back.
     *
     * @param  int    $level              - PHP error severity level
     * @param  string $message            - error message
     * @param  string $file               - name of file where the error occurred
     * @param  int    $line               - line of file where the error occurred
     * @param  array  $context [optional] - symbols of the point where the error occurred (variable scope at error trigger time)
     *
     * @return bool - TRUE,  if the error was successfully handled.
     *                FALSE, if the error shall be processed as if no error handler was installed.
     *                The error handler must return FALSE to populate the internal PHP variable <tt>$php_errormsg</tt>.
     *
     * @throws PHPError
     */
    public static function handleError($level, $message, $file, $line, array $context = null) {
        //echoPre(DebugHelper::errorLevelToStr($level).': $message='.$message.', $file='.$file.', $line='.$line);

        // Ignore suppressed errors and errors not covered by the current reporting level.
        $reportingLevel = error_reporting();
        if (!$reportingLevel)            return false;     // the @ operator was specified
        if (!($reportingLevel & $level)) return true;      // the error is not covered by current reporting level

        $message = strLeftTo($message, ' (this will throw an Error in a future version of PHP)', -1);

        $logContext         = [];
        $logContext['file'] = $file;
        $logContext['line'] = $line;

        // Process errors according to their severity level.
        switch ($level) {
            // log non-critical errors and continue normally
            case E_DEPRECATED     : return true(Logger::log($message, L_INFO,   $logContext));
            case E_USER_DEPRECATED: return true(Logger::log($message, L_INFO,   $logContext));
            case E_USER_NOTICE    : return true(Logger::log($message, L_NOTICE, $logContext));
            case E_USER_WARNING   : return true(Logger::log($message, L_WARN,   $logContext));
        }

        // Wrap everything else in the matching PHPError exception.
        switch ($level) {
            case E_PARSE            : $exception = new PHPParseError      ($message, $code=null, $severity=$level, $file, $line); break;
            case E_COMPILE_WARNING  : $exception = new PHPCompileWarning  ($message, $code=null, $severity=$level, $file, $line); break;
            case E_COMPILE_ERROR    : $exception = new PHPCompileError    ($message, $code=null, $severity=$level, $file, $line); break;
            case E_CORE_WARNING     : $exception = new PHPCoreWarning     ($message, $code=null, $severity=$level, $file, $line); break;
            case E_CORE_ERROR       : $exception = new PHPCoreError       ($message, $code=null, $severity=$level, $file, $line); break;
            case E_STRICT           : $exception = new PHPStrict          ($message, $code=null, $severity=$level, $file, $line); break;
            case E_NOTICE           : $exception = new PHPNotice          ($message, $code=null, $severity=$level, $file, $line); break;
            case E_WARNING          : $exception = new PHPWarning         ($message, $code=null, $severity=$level, $file, $line); break;
            case E_ERROR            : $exception = new PHPError           ($message, $code=null, $severity=$level, $file, $line); break;
            case E_RECOVERABLE_ERROR: $exception = new PHPRecoverableError($message, $code=null, $severity=$level, $file, $line); break;
            case E_USER_ERROR       : $exception = new PHPUserError       ($message, $code=null, $severity=$level, $file, $line); break;

            default                 : $exception = new PHPUnknownError    ($message, $code=null, $severity=$level, $file, $line);
        }

        // Handle the error according to the configuration.
        if (self::$errorMode == self::LOG_ERRORS) {
            return true(Logger::log($exception, L_ERROR, $logContext));
        }

        /**
         * Handle cases where throwing an exception is not possible or not allowed.
         *
         * Fatal out-of-memory errors:
         * ---------------------------
         */
        if (self::$inShutdown && $level==E_ERROR && preg_match(self::$oomRegExp, $message)) {
            return true(Logger::log($message, L_FATAL, $logContext));           // logging the error is sufficient as there is no stacktrace anyway
        }

        /**
         * Errors triggered by require() or require_once():
         * ------------------------------------------------
         * Problem:  PHP errors triggered by require() or require_once() are non-catchable errors and do not follow regular
         *           application flow. PHP terminates the script after leaving the error handler, thrown exceptions are
         *           ignored. This termination is intended behavior and the main difference to include() and include_once().
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
                $currentHandler && call_user_func($currentHandler, $exception); // a possibly static handler can only be invoked by call_user_func()
                return (bool)$currentHandler;                                   // PHP will terminate the script anyway
            }
        }

        // throw back everything else
        throw $exception;
    }


    /**
     * Global handler for otherwise unhandled exceptions.
     *
     * The exception is sent to the default logger with loglevel L_FATAL. After the handler returns PHP will terminate
     * the script.
     *
     * @param  \Exception|\Throwable $exception - the unhandled exception (PHP5) or throwable (PHP7)
     */
    public static function handleException($exception) {
        $context = [
            'class'               => __CLASS__,
            'file'                => $exception->getFile(),   // If the location is not preset the logger will resolve this
            'line'                => $exception->getLine(),   // exception handler as the originating location.
            'unhandled-exception' => true,                    // flag to signal origin
        ];

        // Exceptions thrown from the exception handler itself will not be passed back to the handler again but instead
        // terminate the script with an uncatchable fatal error. To prevent this they are handled explicitely.
        $second = null;
        try {
            Assert::throwable($exception);
            Logger::log($exception, L_FATAL, $context);       // log with the highest level
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
            $msg  = str_replace(chr(0), '?', $msg);     // replace NUL bytes which mess up the logfile

            if (CLI) echo $msg.NL;                      // full second exception
            error_log(trim($msg), ERROR_LOG_DEFAULT);
        }

        // web interface: prevent an empty page
        if (!CLI) {
            try {
                if (Application::isAdminIP() || ini_get_bool('display_errors')) {
                    if ($second) {                          // full second exception, full log location
                        echoPre($second);
                        echoPre('error log: '.(strlen($errorLog=ini_get('error_log')) ? $errorLog : 'web server'));
                    }
                }
                else echoPre('application error (see error log)');
            }
            catch (\Throwable $third) { echoPre('application error (see error log)'); }
            catch (\Exception $third) { echoPre('application error (see error log)'); }
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
                call_user_func($currentHandler, $exception);    // a possibly static handler can only be invoked by call_user_func()
                exit(1);                                        // Calling exit() is the only way to prevent the immediately following
            }                                                   // non-catchable fatal error. However, calling exit() in a destructor will
        }                                                       // also prevent execution of any remaining shutdown routines.
        return $exception;
    }


    /**
     * Manually called handler for exceptions occurring in object::__toString() methods. This allows regular handling of
     * exceptions thrown from object::__toString() which otherwise is not possible due to an internal PHP design issue.
     *
     * Current PHP behavior:
     * PHP Fatal error:  Method object::__toString() must not throw an exception in {file} on {line}.
     *
     * @param  \Exception|\Throwable $exception - exception (PHP5) or throwable (PHP7)
     *
     * @link   https://bugs.php.net/bug.php?id=53648
     */
    public static function handleToStringException($exception) {
        $currentHandler = set_exception_handler(function() {});
        restore_exception_handler();

        if ($currentHandler) {
            call_user_func($currentHandler, $exception);        // a possibly static handler can only be invoked by call_user_func()
            exit(1);                                            // Calling exit() is the only way to prevent the immediately following
        }                                                       // non-catchable fatal error.
    }
}
