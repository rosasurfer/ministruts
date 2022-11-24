<?php
namespace rosasurfer\core\error;

use rosasurfer\Application;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\log\Logger;

use function rosasurfer\echof;
use function rosasurfer\ini_get_bool;
use function rosasurfer\normalizeEOL;
use function rosasurfer\simpleClassName;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;
use function rosasurfer\true;

use const rosasurfer\CLI;
use const rosasurfer\ERROR_LOG_DEFAULT;
use const rosasurfer\L_ERROR;
use const rosasurfer\L_FATAL;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\L_WARN;
use const rosasurfer\MB;
use const rosasurfer\NL;


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
    protected static $inScriptShutdown = false;

    /** @var string - RegExp for detecting out-of-memory errors */
    protected static $oomRegExp = '/^Allowed memory size of ([0-9]+) bytes exhausted/';

    /** @var string - memory block reserved for handling out-of-memory errors */
    protected static $oomEmergencyMemory;


    /**
     * Setup error handling.
     *
     * @param  int $mode - error handling mode: [ERRORS_IGNORE | ERRORS_LOG | ERRORS_EXCEPTION]
     */
    public static function setupErrorHandling($mode) {
        if (!in_array($mode, [self::ERRORS_IGNORE, self::ERRORS_LOG, self::ERRORS_EXCEPTION])) return;

        if ($mode == self::ERRORS_IGNORE) {
            self::$errorHandlingMode = 0;
        }
        else {
            self::$errorHandlingMode = $mode;
            self::$prevErrorHandler  = set_error_handler(__CLASS__.'::handleError');
            self::setupShutdownHandler();
        }
    }


    /**
     * Setup exception handling.
     *
     * @param  int $mode - exception handling mode: [EXCEPTIONS_IGNORE | EXCEPTIONS_CATCH]
     */
    public static function setupExceptionHandling($mode) {
        if (!in_array($mode, [self::EXCEPTIONS_IGNORE, self::EXCEPTIONS_CATCH])) return;
        self::$exceptionHandling = ($mode != self::EXCEPTIONS_IGNORE);

        if (self::$exceptionHandling) {
            self::$prevExceptionHandler = set_exception_handler(__CLASS__.'::handleException');
            self::setupShutdownHandler();
        }
    }


    /**
     * Setup a script shutdown handler to handle fatal errors during shutdown. The callback should be
     * first on the shutdown function stack.
     */
    protected static function setupShutdownHandler() {
        static $handlerRegistered = false;

        if (!$handlerRegistered) {
            register_shutdown_function(__CLASS__.'::onScriptShutdown');
            self::$oomEmergencyMemory = str_repeat('*', 1*MB);          // allocate some memory for OOM errors
            $handlerRegistered = true;
        }
    }


    /**
     * A handler for internal PHP errors. Errors are handled if covered by the currently active error reporting level. They are either
     * logged or converted to {@link PHPError} exceptions and thrown back.
     *
     * Errors of level E_DEPRECATED, E_USER_DEPRECATED, E_USER_NOTICE or E_USER_WARNING are never thrown back as exceptions.
     *
     * @param  int                  $level              - error severity level
     * @param  string               $message            - error message
     * @param  string               $file               - name of file where the error occurred
     * @param  int                  $line               - line of file where the error occurred
     * @param  array<string, mixed> $symbols [optional] - symbol table at the point of the error
     *
     * @return bool - TRUE  if the error was handled and PHP should call error_clear_last()
     *                FALSE if the error was not handled and PHP should not call error_clear_last()
     *
     * @throws PHPError
     */
    public static function handleError($level, $message, $file, $line, array $symbols = null) {
        //echof('ErrorHandler::handleError()  '.self::errorLevelToStr($level).': '.$message.', in '.$file.', line '.$line);
        if (!self::$errorHandlingMode) return false;

        // anonymous function to chain a previously active handler
        $args = func_get_args();
        $prevErrorHandler = function() use ($args) {                        // a possibly static handler must be invoked with call_user_func()
            self::$prevErrorHandler && call_user_func(self::$prevErrorHandler, ...$args);
            return true;                                                    // tell PHP to call error_clear_last()
        };

        // ignore suppressed errors and errors not covered by the current reporting level
        $reportingLevel = error_reporting();
        if (!$reportingLevel)            return $prevErrorHandler();        // the @ operator was specified
        if (!($reportingLevel & $level)) return $prevErrorHandler();        // the error is not covered by the active reporting level

        // convert error to a PHPError exception
        $message = 'PHP '.self::errorLevelDescr($level).': '.strLeftTo($message, ' (this will throw an Error in a future version of PHP)', -1);
        $error = new PHPError($message, 0, $level, $file, $line);
        $trace = self::removeFrames($error->getTrace(), $file, $line);
        self::setNewTrace($error, $trace);                                  // let the stacktrace point to the trigger statement

        // handle the error accordingly
        $alwaysLog = ($level & (E_DEPRECATED|E_USER_DEPRECATED|E_USER_NOTICE|E_USER_WARNING));

        if (self::$errorHandlingMode == self::ERRORS_LOG || $alwaysLog) {   // only log the error
        switch ($level) {
                case E_DEPRECATED:
                case E_USER_DEPRECATED: $logLevel = L_INFO;   break;
                case E_USER_NOTICE:     $logLevel = L_NOTICE; break;
                case E_USER_WARNING:    $logLevel = L_WARN;   break;
                default:                $logLevel = L_ERROR;
            }
            Logger::log($error, $logLevel, [
                'class' => isset($trace[0]['class']) ? $trace[0]['class'] : '',
                'file'  => $error->getFile(),
                'line'  => $error->getLine(),
            ]);
            return $prevErrorHandler();
        }

        // throw back everything as an exception
        // There are rare cases were throwing an exception from the error handler causes even more errors.

        // fatal out-of-memory errors
        if (self::$inScriptShutdown && $level==E_ERROR && preg_match(self::$oomRegExp, $message)) {
            $context = [
                'file'            => $file,
                'line'            => $line,
                'unhandled-error' => true,
            ];
            return true(Logger::log($message, L_FATAL, $context));          // logging the message is sufficient as there is no stacktrace anyway
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
        $trace = $error->getBetterTrace();
        if ($trace) {                                                           // after a fatal error the trace may be empty
            $function = self::getFrameMethod($trace[0]);
            if ($function=='require' || $function=='require_once') {
                $currentHandler = set_exception_handler(function() {});
                restore_exception_handler();
                $currentHandler && call_user_func($currentHandler, $error);     // We MUST use call_user_func() as a static handler cannot be invoked dynamically.
                return (bool)$currentHandler;                                   // PHP will terminate the script anyway
            }
        }

        // now throw back everything else
        throw $error;
    }


    /**
     * A handler for uncatched exceptions. After the handler returns PHP terminates the script.
     *
     * @param  \Exception|\Throwable $exception - the unhandled exception (PHP5) or throwable (PHP7)
     */
    public static function handleException($exception) {
        //echof('ErrorHandler::handleException()  '.$exception->getMessage());
        if (!self::$exceptionHandling) return;

        // Exceptions thrown from the exception handler itself will not be passed back to the handler but
        // cause an uncatchable fatal error. To prevent this they are handled explicitly.
        $secondEx = null;
        try {
            Logger::log($exception, L_FATAL, [
                'file'            => $exception->getFile(),
                'line'            => $exception->getLine(),
                'unhandled-error' => true,
            ]);
        }
        catch (\Throwable $secondEx) {}
        catch (\Exception $secondEx) {}

        if ($secondEx)  {
            // secondary exception: the logger itself crashed, last chance to log
            $indent = ' ';
            $msg2  = '[FATAL] Unhandled '.trim(self::getBetterMessage($secondEx)).NL;
            $file2 = $secondEx->getFile();
            $line2 = $secondEx->getLine();
            $msg2 .= $indent.'in '.$file2.' on line '.$line2.NL.NL;
            $msg2 .= $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $msg2 .= self::getBetterTraceAsString($secondEx, $indent);

            // primary (the causing) exception
            $msg1  = $indent.'Unhandled '.trim(self::getBetterMessage($exception)).NL;
            $file1 = $exception->getFile();
            $line1 = $exception->getLine();
            $msg1 .= $indent.'in '.$file1.' on line '.$line1.NL.NL;
            $msg1 .= $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $msg1 .= self::getBetterTraceAsString($exception, $indent);

            $msg  = $msg2.NL;
            $msg .= $indent.'caused by'.NL;
            $msg .= $msg1;
            $msg  = str_replace(chr(0), '\0', $msg);                    // replace NUL bytes which mess up the logfile

            if (CLI) echo $msg.NL;                                      // full second exception
            error_log(trim($msg), ERROR_LOG_DEFAULT);
        }

        if (!CLI) {                                                     // web interface: prevent an empty page
            try {
                if (Application::isAdminIP() || ini_get_bool('display_errors')) {
                    if ($secondEx) {                                    // full second exception, full log location
                        echof($secondEx);
                        echof('error log: '.(strlen($errorLog=ini_get('error_log')) ? $errorLog : 'web server'));
                    }
                }
                else echof('application error (see error log)');
            }
            catch (\Throwable $thirdEx) { echof('application error (see error log)'); }
            catch (\Exception $thirdEx) { echof('application error (see error log)'); }
        }

        // chain a previously active exception handler
        if (self::$prevExceptionHandler) {
            call_user_func(self::$prevExceptionHandler, $exception);    // a possibly static handler must be invoked with call_user_func()
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
        if (self::$inScriptShutdown) {
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
     * A manually called handler for exceptions raised in object::__toString(). It allows regular handling of exceptions thrown
     * from object::__toString() in PHP < 7.4, which is not possible in those versions due to an internal PHP design issue.
     *
     * Behaviour PHP < 7.4: Fatal error:  Method object::__toString() must not throw an exception in {file} on {line}.
     *
     * @param  \Exception|\Throwable $exception - exception (PHP5) or throwable (PHP7)
     *
     * @see  https://bugs.php.net/bug.php?id=53648
     * @see  https://wiki.php.net/rfc/tostring_exceptions
     * @see  https://github.com/symfony/symfony/blob/1c110fa1f7e3e9f5daba73ad52d9f7e843a7b3ff/src/Symfony/Component/Debug/ErrorHandler.php#L457-L489
     */
    public static function handleToStringException($exception) {
        //echof('ErrorHandler::handleToStringException()  '.$exception->getMessage());
        $currentHandler = set_exception_handler(function() {});
        restore_exception_handler();

        if ($currentHandler) {
            call_user_func($currentHandler, $exception);        // We MUST use call_user_func() as a static handler cannot be invoked dynamically.
            exit(1);                                            // Calling exit() is the only way to prevent the immediately following
        }                                                       // non-catchable fatal error.
    }


    /**
     * A handler executed when the script shuts down.
     *
     * @return mixed
     */
    public static function onScriptShutdown() {
        echof('ErrorHandler::onScriptShutdown()');
        self::$inScriptShutdown = true;

        // Handle destructor exceptions during shutdown differently. Otherwise such exceptions will cause fatal errors.
        //
        // @link  http://php.net/manual/en/language.oop5.decon.php
        // @see   self::handleDestructorException()

        // If error handling is enabled handle fatal runtime errors.
        //
        // @link  https://github.com/bugsnag/bugsnag-laravel/issues/226
        // @link  https://gist.github.com/dominics/61c23f2ded720d039554d889d304afc9
        if (self::$errorHandlingMode) {
            self::$oomEmergencyMemory = $match = null;                  // release the reserved memory, meant to be used by preg_match()
            $error = error_get_last();
            if ($error && $error['type']==E_ERROR && preg_match(self::$oomRegExp, $error['message'], $match)) {
                ini_set('memory_limit', (int)$match[1] + 10*MB);        // try to allocate some more memory for the regular handler

                $currentHandler = set_error_handler(function() {});     // handle the error regularily
                restore_error_handler();
                $currentHandler && call_user_func($currentHandler, ...array_values($error));
            }
        }
    }


    /**
     * Return the description of an error reporting level.
     *
     * @param  int $level - single error reporting level
     *
     * @return string
     */
    public static function errorLevelDescr($level) {
        Assert::int($level);

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
        return isset($levels[$level]) ? $levels[$level] : '(unknown)';
    }


    /**
     * Return a readable representation of an error reporting flag.
     *
     * @param  int $flag - combination of error reporting levels
     *
     * @return string
     */
    public static function errorLevelToStr($flag) {
        Assert::int($flag);

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

        if      (!$flag)                                                       $levels = ['0'];                         //     0
        else if (($flag &  E_ALL)                  ==  E_ALL)                  $levels = ['E_ALL'];                     // 32767
        else if (($flag & (E_ALL & ~E_DEPRECATED)) == (E_ALL & ~E_DEPRECATED)) $levels = ['E_ALL & ~E_DEPRECATED'];     // 24575
        else {
            foreach ($levels as $key => $value) {
                if ($flag & $key) continue;
                unset($levels[$key]);
            }
        }
        return join('|', $levels);
    }


    /**
     * Return the message of an exception in a more readable way. Same as {@link IRosasurferException::getBetterMessage()} except that this
     * method can be used with all PHP exceptions.
     *
     * @param  \Exception|\Throwable $exception         - any exception
     * @param  string                $indent [optional] - indent all lines by the specified value (default: no indentation)
     *
     * @return string - message
     */
    public static function getBetterMessage($exception, $indent = '') {
        Assert::throwable($exception, '$exception');

        $message = trim($exception->getMessage());

        if ($exception instanceof PHPError) {
            $type = '';
        }
        else {
            $class     = get_class($exception);
            $namespace = strtolower(strLeftTo($class, '\\', -1, true, ''));
            $basename  = simpleClassName($class);
            $type      = $namespace.$basename;
            $message   = (strlen($message) ? ': ':'').$message;

            if ($exception instanceof \ErrorException) {            // a PHP error exception not created by the framework
                $type .= '('.self::errorLevelDescr($exception->getSeverity()).')';
            }
        }
        $message = $type.$message;

        if (strlen($indent)) {
            $message = str_replace(NL, NL.$indent, normalizeEOL($message));
        }
        return $indent.$message;
    }


    /**
     * Takes a regular PHP stacktrace and adjusts it to be more readable. Same as {@link IRosasurferException::getBetterTrace()} except
     * that this method can be used with all PHP exceptions.
     *
     * @param  array  $trace           - regular PHP stacktrace
     * @param  string $file [optional] - name of the file where the stacktrace was generated
     * @param  int    $line [optional] - line of the file where the stacktrace was generated
     *
     * @return array - adjusted stacktrace
     *
     * @example
     * before: frame locations point to the called statement
     * <pre>
     *  require_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *  include_once()  # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *  include()       # line 26, file: /var/www/phalcon/vokuro/public/index.php
     *  {main}
     * </pre>
     *
     * after: frame locations point to the calling statement
     * <pre>
     *  require_once()             [php]
     *  include_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *  include()       # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *  {main}          # line 26, file: /var/www/phalcon/vokuro/public/index.php
     * </pre>
     */
    public static function getBetterTrace(array $trace, $file='unknown', $line=0) {
        // check if the stacktrace is already adjusted
        if (isset($trace[0]['__ministruts_adjusted__'])) return $trace;

        // fix a missing first line if $file matches (e.g. with \SimpleXMLElement)
        if ($file!='unknown' && $line) {
            if (isset($trace[0]['file']) && $trace[0]['file']==$file) {
                if (isset($trace[0]['line']) && $trace[0]['line']===0) {
                    $trace[0]['line'] = $line;
                }
            }
        }

        // append a frame for the main script
        $trace[] = ['function' => '{main}'];

        // move fields FILE and LINE to the end by one position
        for ($i=sizeof($trace); $i--;) {
            if (isset($trace[$i-1]['file'])) $trace[$i]['file'] = $trace[$i-1]['file'];
            else                       unset($trace[$i]['file']);

            if (isset($trace[$i-1]['line'])) $trace[$i]['line'] = $trace[$i-1]['line'];
            else                       unset($trace[$i]['line']);

            $trace[$i]['__ministruts_adjusted__'] = true;
        }

        // add location details from parameters to frame[0] only if they differ from the old values (now in frame[1])
        if (!isset($trace[1]['file'], $trace[1]['line']) || $trace[1]['file']!=$file || $trace[1]['line']!=$line) {
            $trace[0]['file'] = $file;                          // test with:
            $trace[0]['line'] = $line;                          // \SQLite3::enableExceptions(true|false);
        }                                                       // \SQLite3::exec($invalid_sql);
        else {
            unset($trace[0]['file'], $trace[0]['line']);        // otherwise delete them
        }

        // remove the last frame (the one appended for the main script) if it now points to an unknown location (the PHP core)
        $size = sizeof($trace);
        !isset($trace[$size-1]['file']) && \array_pop($trace);

        return $trace;

        // TODO: fix wrong stack frames originating from calls to virtual static functions
        //
        // phalcon\mvc\Model::__callStatic()                  [php-phalcon]
        // vokuro\models\Users::findFirstByEmail() # line 27, file: F:\Projects\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
        // vokuro\auth\Auth->check()               # line 27, file: F:\Projects\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
    }


    /**
     * Return the stacktrace of an exception in a more readable way as a string. The returned string contains nested exceptions.
     * Same as {@link IRosasurferException::getBetterTraceAsString()} except that this method can be used with all PHP exceptions.
     *
     * @param  \Exception|\Throwable $exception         - any exception (PHP5) or throwable (PHP7)
     * @param  string                $indent [optional] - indent the resulting lines by the specified value
     *                                                    (default: no indentation)
     * @return string - readable stacktrace
     */
    public static function getBetterTraceAsString($exception, $indent = '') {
        Assert::throwable($exception, '$exception');

        if ($exception instanceof IRosasurferException) $trace = $exception->getBetterTrace();
        else                                            $trace = self::getBetterTrace($exception->getTrace(), $exception->getFile(), $exception->getLine());
        $result = self::formatTrace($trace, $indent);

        if ($cause = $exception->getPrevious()) {
            // recursively add stacktraces of nested exceptions
            $message = trim(self::getBetterMessage($cause, $indent));
            $result .= NL.$indent.'caused by'.NL.$indent.$message.NL.NL;
            $result .= self::{__FUNCTION__}($cause, $indent);                 // recursion
        }
        return $result;
    }


    /**
     * Return a formatted and more readable version of a stacktrace.
     *
     * @param  array  $trace             - stacktrace
     * @param  string $indent [optional] - indent formatted lines by this value (default: no indenting)
     *
     * @return string
     */
    public static function formatTrace(array $trace, $indent = '') {
        $config  = self::di('config');
        $appRoot = $config ? $config['app.dir.root'] : null;
        $result  = '';

        $size = sizeof($trace);
        $callLen = $lineLen = 0;

        for ($i=0; $i < $size; $i++) {               // align FILE and LINE
            $frame = &$trace[$i];

            $call = self::getFrameMethod($frame, true);

            if ($call!='{main}' && !strEndsWith($call, '{closure}'))
                $call.='()';
            $callLen = max($callLen, strlen($call));
            $frame['call'] = $call;

            $frame['line'] = isset($frame['line']) ? ' # line '.$frame['line'].',' : '';
            $lineLen = max($lineLen, strlen($frame['line']));

            if (isset($frame['file']))                 $frame['file'] = ' file: '.(!$appRoot ? $frame['file'] : strRightFrom($frame['file'], $appRoot.DIRECTORY_SEPARATOR, 1, false, $frame['file']));
            elseif (strStartsWith($call, 'phalcon\\')) $frame['file'] = ' [php-phalcon]';
            else                                       $frame['file'] = ' [php]';
        }
        if ($appRoot) {
            $trace[] = ['call'=>'', 'line'=>'', 'file'=>' file base: '.$appRoot];
            $i++;
        }

        for ($i=0; $i < $size; $i++) {
            $result .= $indent.str_pad($trace[$i]['call'], $callLen).' '.str_pad($trace[$i]['line'], $lineLen).$trace[$i]['file'].NL;
        }
        return $result;
    }


    /**
     * Return a stack frame's full method name similar to the constant __METHOD__. Used for generating a formatted stacktrace.
     *
     * @param  array $frame                - frame
     * @param  bool  $nsToLower [optional] - whether to return the namespace part in lower case (default: unmodified)
     *
     * @return string - method name (without trailing parentheses)
     */
    public static function getFrameMethod(array $frame, $nsToLower = false) {
        $class = $function = '';

        if (isset($frame['function'])) {
            $function = $frame['function'];

            if (isset($frame['class'])) {
                $class = $frame['class'];
                if ($nsToLower && is_int($pos=strrpos($class, '\\'))) {
                    $class = strtolower(substr($class, 0, $pos)).substr($class, $pos);
                }
                $class = $class.$frame['type'];
            }
            elseif ($nsToLower && is_int($pos=strrpos($function, '\\'))) {
                $function = strtolower(substr($function, 0, $pos)).substr($function, $pos);
            }
        }
        return $class.$function;
    }


    /**
     * Remove all frames from a stacktrace following the specified file and line (used to remove frames of this error handler from a PHP
     * error converted to an exception).
     *
     * @param array  $trace - stacktrace to process
     * @param string $file  - filename where the error was triggered
     * @param int    $line  - line number where the error was triggered
     *
     * @return array
     */
    private static function removeFrames(array $trace, $file, $line) {
        $result = $trace;
        $size = sizeof($trace);

        for ($i=0; $i < $size; $i++) {
            if (isset($trace[$i]['file'], $trace[$i]['line']) && $trace[$i]['file']===$file && $trace[$i]['line']===$line) {
                $result = array_slice($trace, $i+1);
                break;
            }
        }
        return $result;
    }


    /**
     * Set the stacktrace of an {@link \Exception}.
     *
     * @param  \Exception $exception - exception to modify
     * @param  array      $trace     - new stacktrace
     */
    private static function setNewTrace(\Exception $exception, array $trace) {
        static $property = null;
        if (!$property) {
            $property = new \ReflectionProperty(\Exception::class, 'trace');
            $property->setAccessible(true);
        }
        $property->setValue($exception, $trace);
    }
}
