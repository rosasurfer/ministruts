<?php
namespace rosasurfer\debug;

use rosasurfer\Application;
use rosasurfer\core\StaticClass;
use rosasurfer\exception\error\PHPCompileError;
use rosasurfer\exception\error\PHPCompileWarning;
use rosasurfer\exception\error\PHPCoreError;
use rosasurfer\exception\error\PHPCoreWarning;
use rosasurfer\exception\error\PHPError;
use rosasurfer\exception\error\PHPNotice;
use rosasurfer\exception\error\PHPParseError;
use rosasurfer\exception\error\PHPRecoverableError;
use rosasurfer\exception\error\PHPStrict;
use rosasurfer\exception\error\PHPUnknownError;
use rosasurfer\exception\error\PHPUserError;
use rosasurfer\exception\error\PHPWarning;
use rosasurfer\log\Logger;
use rosasurfer\util\PHP;

use function rosasurfer\echoPre;
use function rosasurfer\ini_get_bool;
use function rosasurfer\true;

use const rosasurfer\CLI;
use const rosasurfer\ERROR_LOG_DEFAULT;
use const rosasurfer\L_ERROR;
use const rosasurfer\L_FATAL;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\L_WARN;
use const rosasurfer\NL;


/**
 * Gobal error handling
 */
class ErrorHandler extends StaticClass {


    /** @var int - error handling mode in which regular PHP errors are logged */
    const LOG_ERRORS = 1;

    /** @var int - error handling mode in which regular PHP errors are converted to exceptions and thrown back */
    const THROW_EXCEPTIONS = 2;


    /** @var callable - the registered error handler */
    private static $errorHandler;

    /** @var int - the mode the error handler is configured for, can be either self::LOG_ERRORS or self::THROW_EXCEPTIONS */
    private static $errorMode;

    /** @var callable - the registered exception handler */
    private static $exceptionHandler;

    /** @var bool - whether or not the script is in the shutdown phase */
    private static $inShutdown;


    /**
     * Get the registered error handler (if any).
     *
     * @return callable
     */
    public static function getErrorHandler() {
        return self::$errorHandler;
    }


    /**
     * Get the registered exception handler (if any).
     *
     * @return callable
     */
    public static function getExceptionHandler() {
        return self::$exceptionHandler;
    }


    /**
     * Whether or not the script is in the shutdown phase.
     *
     * @return bool
     */
    public static function isInShutdown() {
        return (bool) self::$inShutdown;
    }


    /**
     * Setup global error handling.
     *
     * @param  int $mode - mode the error handler to setup for
     *                     can be either self::LOG_ERRORS or self::THROW_EXCEPTIONS
     *
     * @return mixed - Returns a string containing the previously defined error handler (if any). If the passed $mode
     *                 parameter is invalid or if the built-in error handler was active NULL is returned. If the previous
     *                 error handler was a class method an indexed array with the class and the method name is returned.
     */
    public static function setupErrorHandling($mode) {
        if     ($mode === self::LOG_ERRORS      ) self::$errorMode = self::LOG_ERRORS;
        elseif ($mode === self::THROW_EXCEPTIONS) self::$errorMode = self::THROW_EXCEPTIONS;
        else                                      return null;

        return set_error_handler(self::$errorHandler=__CLASS__.'::handleError', error_reporting());
    }


    /**
     * Setup global exception handling.
     *
     * @return callable|null - Returns the name of the previously defined exception handler, or NULL if no previous handler
     *                         was defined or an error occurred.
     */
    public static function setupExceptionHandling() {
        $previous = set_exception_handler(self::$exceptionHandler=__CLASS__.'::handleException');

        /**
         * Detect entering of the script's shutdown phase to be capable of handling destructor exceptions during shutdown
         * differently and avoid otherwise fatal errors. Should be the very first function on the shutdown function stack.
         *
         * @see  http://php.net/manual/en/language.oop5.decon.php
         * @see  ErrorHandler::handleDestructorException()
         */
        register_shutdown_function(function() {
            self::$inShutdown = true;
        });
        return $previous;
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
        //echoPre(__METHOD__.'()  '.DebugHelper::errorLevelToStr($level).': $message='.$message.', $file='.$file.', $line='.$line);

        // (1) Ignore suppressed errors and errors not covered by the current reporting level.
        $reportingLevel = error_reporting();
        if (!$reportingLevel)            return false;     // the @ operator was specified
        if (!($reportingLevel & $level)) return true;      // the error is not covered by current reporting level

        $logContext         = [];
        $logContext['file'] = $file;
        $logContext['line'] = $line;

        // (2) Process errors according to their severity level.
        switch ($level) {
            // log non-critical errors and continue normally
            case E_DEPRECATED     : return true(Logger::log($message, L_INFO,   $logContext));
            case E_USER_DEPRECATED: return true(Logger::log($message, L_INFO,   $logContext));
            case E_USER_NOTICE    : return true(Logger::log($message, L_NOTICE, $logContext));
            case E_USER_WARNING   : return true(Logger::log($message, L_WARN,   $logContext));
        }

        // (3) Wrap everything else in the matching PHPError exception.
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

        // (4) Handle the error according to the error configuration.
        if (self::$errorMode == self::LOG_ERRORS) {
            Logger::log($exception, L_ERROR, $logContext);
            return true;
        }

        // (5) Handle cases where throwing an exception is not possible or not allowed.

        /**
         * Errors triggered by require() or require_once()
         *
         * Problem:  PHP errors triggered by require() or require_once() are non-catchable errors and do not follow regular
         *           application flow. PHP terminates the script after leaving the error handler, thrown exceptions are
         *           ignored. This termination is intended behaviour and the main difference to include() and include_once().
         * Solution: Manually call the exception handler.
         *
         * @see  http://stackoverflow.com/questions/25584494/php-set-exception-handler-not-working-for-error-thrown-in-set-error-handler-cal
         */
        $trace = $exception->getBetterTrace();
        if ($trace) {                                                   // after a FATAL error the trace may be empty
            $function = DebugHelper::getFQFunctionName($trace[0]);
            if ($function=='require' || $function=='require_once') {
                call_user_func(self::$exceptionHandler, $exception);
                return true;                                            // PHP will terminate the script anyway
            }
        }

        // (6) Throw back everything else.
        throw $exception;
    }


    /**
     * Global handler for otherwise unhandled exceptions.
     *
     * The exception is sent to the default logger with loglevel L_FATAL. After the handler returns PHP will terminate
     * the script.
     *
     * @param  \Exception $exception - the unhandled exception
     */
    public static function handleException(\Exception $exception) {
        $context = [];
        $second  = null;

        try {
            $context['class'    ] = __CLASS__;
            $context['file'     ] = $exception->getFile();  // If the location is not preset the logger will resolve the
            $context['line'     ] = $exception->getLine();  // exception handler as the originating location.
            $context['unhandled'] = true;

            Logger::log($exception, L_FATAL, $context);     // log with the highest level
        }

        // Exceptions thrown from within the exception handler will not be passed back to the handler again. Instead the
        // script terminates with an uncatchable fatal error.
        catch (\Exception $second) {
            $indent = ' ';                                  // the application is crashing, last try to log

            // secondary exception
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
            $msg  = str_replace(chr(0), '?', $msg);         // replace NUL bytes which mess up the logfile

            if (CLI)                                        // full second exception
                echo $msg.NL;
            error_log(trim($msg), ERROR_LOG_DEFAULT);
        }

        // web: prevent an empty page
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
            catch (\Exception $third) {
                echoPre('application error (see error log)');
            }
        }
    }


    /**
     * Manually called handler for exceptions occurring in object destructors.
     *
     * Attempting to throw an exception from a destructor during script shutdown causes a fatal error. Therefore this method
     * has to be called manually from object destructors if an exception occurred. If the script is in the shutdown phase
     * the exception is passed on to the regular exception handler and the script is terminated. If the script is currently
     * not in the shutdown phase this method ignores the exception. For an example see this package's README.
     *
     * @param  \Exception $exception
     *
     * @return \Exception - the same exception
     *
     * @link   http://php.net/manual/en/language.oop5.decon.php
     */
    public static function handleDestructorException(\Exception $exception) {
        if (self::isInShutdown()) {
            self::handleException($exception);
            exit(1);                                                // exit and signal the error

            // Calling exit() is the only way to prevent the immediately following non-catchable fatal error.
            // However, calling exit() in a destructor will also prevent any remaining shutdown routines from executing.
            // @see above link
        }
        return $exception;
    }
}
