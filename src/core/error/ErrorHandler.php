<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\error;

use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\log\Logger;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;

use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\ini_get_bool;
use function rosasurfer\ministruts\normalizeEOL;
use function rosasurfer\ministruts\simpleClassName;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strRightFrom;
use function rosasurfer\ministruts\strStartsWith;
use function rosasurfer\ministruts\true;

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


    /** @var int - ignore PHP errors and exceptions */
    const MODE_IGNORE = 1;

    /** @var int - log PHP errors and exceptions */
    const MODE_LOG = 2;

    /** @var int - convert PHP errors to exceptions, log both */
    const MODE_EXCEPTION = 3;


    /** @var int - the configured error handling mode */
    protected static $errorHandling = 0;

    /** @var ?callable - a previously active error handler (if any) */
    protected static $prevErrorHandler = null;

    /** @var bool - the configured exception handling status */
    protected static $exceptionHandling = false;

    /** @var ?callable - a previously active exception handler (if any) */
    protected static $prevExceptionHandler = null;

    /** @var bool - whether the script is in the shutdown phase */
    protected static $inShutdown = false;

    /** @var ?string - memory block reserved for handling out-of-memory errors */
    protected static $oomEmergencyMemory = null;


    /**
     * Setup error handling.
     *
     * @param  int $level - error reporting level
     * @param  int $mode  - error handling mode, one of [MODE_IGNORE | MODE_LOG | MODE_EXCEPTION]
     *
     * @return void
     */
    public static function setupErrorHandling(int $level, int $mode) {
        if ($level < self::MODE_IGNORE || $level > self::MODE_EXCEPTION) {
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

                error_reporting($level);
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
    protected static function setupShutdownHandler() {
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
     * Errors of level E_DEPRECATED, E_USER_DEPRECATED, E_USER_NOTICE or E_USER_WARNING are just logged and never thrown back.
     *
     * @param  int                  $level              - error severity level
     * @param  string               $message            - error message
     * @param  string               $file               - name of file where the error occurred
     * @param  int                  $line               - line of file where the error occurred
     * @param  array<string, mixed> $symbols [optional] - symbol table at the point of the error
     *
     * @return bool - TRUE  if the error was handled and PHP should call error_clear_last();
     *                FALSE if the error was not handled and PHP should not call error_clear_last()
     *
     * @throws PHPError
     */
    public static function handleError($level, $message, $file, $line, array $symbols = []) {
        //echof('ErrorHandler::handleError()  '.self::errorLevelToStr($level).': '.$message.', in '.$file.', line '.$line);
        if (!self::$errorHandling) return false;

        // anonymous function to chain a previously active handler
        $args = func_get_args();
        $prevErrorHandler = function() use ($args) {
            if (self::$prevErrorHandler) {
                (self::$prevErrorHandler)(...$args);
            }
            return true;                                                    // tell PHP to call error_clear_last()
        };

        // ignore suppressed errors and errors not covered by the active reporting level
        $reportingLevel = error_reporting();                                // since PHP8 some errors are not silenceable anymore
        if (!$reportingLevel)            return $prevErrorHandler();        // the @ operator was specified
        if (!($reportingLevel & $level)) return $prevErrorHandler();        // the error is not covered by the active reporting level

        // convert error to a PHPError exception
        $message = strLeftTo($message, ' (this will throw an Error in a future version of PHP)', -1);
        $error = new PHPError($message, 0, $level, $file, $line);
        $trace = self::shiftStackFramesByLocation($error->getTrace(), $file, $line);
        self::setExceptionProperties($error, $trace);                       // let the stacktrace point to the trigger statement

        // handle the error accordingly
        $alwaysLog = ($level & (E_DEPRECATED | E_USER_DEPRECATED | E_USER_NOTICE | E_USER_WARNING));

        if (self::$errorHandling == self::MODE_LOG || $alwaysLog) {         // only log the error
            $error->prependMessage('PHP ' . self::errorLevelDescr($level) . ': ');
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
            Logger::log($error, $logLevel, [
                'file'          => $file,
                'line'          => $line,
                'error-handler' => true,
            ]);
            return $prevErrorHandler();
        }

        // throw back the error as an exception

        /**
         * TODO: There are rare cases were throwing an exception from the error handler causes even more errors.
         *
         * Errors triggered by require() or require_once():
         * ------------------------------------------------
         * Problem:  PHP errors triggered by require() or require_once() are non-catchable errors and do not follow regular
         *           application flow. PHP terminates the script after leaving the error handler, thrown exceptions are
         *           ignored. This termination is intended behavior and the main difference to include() and include_once().
         * Solution: Manually call the exception handler.
         *
         * @see  https://stackoverflow.com/questions/25584494/php-set-exception-handler-not-working-for-error-thrown-in-set-error-handler-cal
         */
        //$trace = $error->getBetterTrace();
        //if ($trace) {                                                           // after a fatal error the trace may be empty
        //    $function = self::getFrameMethod($trace[0]);
        //    if ($function=='require' || $function=='require_once') {
        //        $currentHandler = set_exception_handler(function() {});
        //        restore_exception_handler();
        //        $currentHandler && call_user_func($currentHandler, $error);     // a possibly static handler must be invoked with call_user_func()
        //        return (bool)$currentHandler;                                   // PHP will terminate the script anyway.
        //    }
        //}

        // now throw back everything else
        throw $error->prependMessage('PHP Error: ');
    }


    /**
     * A handler for uncatched exceptions. After the handler returns PHP terminates the script.
     *
     * @param \Throwable $exception - the unhandled throwable
     *
     * @return void
     */
    public static function handleException(\Throwable $exception) {
        if (!self::$exceptionHandling) return;
        //echof('ErrorHandler::handleException()  '.$exception->getMessage());

        // prevent exceptions to be thrown from the handler itself (causes uncatchable fatal errors)
        try {
            Logger::log($exception, L_FATAL, [
                'file'          => $exception->getFile(),
                'line'          => $exception->getLine(),
                'error-handler' => true,
            ]);

            // chain a previously active handler
            if (self::$prevExceptionHandler) {
                (self::$prevExceptionHandler)($exception);
            }
        }
        catch (\Throwable $second) {
            self::handleExceptionOnException($exception, $second);
        }
    }


    /**
     * Process exceptions thrown inside the exception handler. Called if {@link Logger} or other chained exception handlers fail
     * and throw exceptions by themself. In such a case both exceptions are logged to the default log with only limited details.
     *
     * ATTENTION: If this method is reached something in the previous error handling went seriously wrong. Therefore this method
     *            should better not rely on external dependencies.
     *
     * @param  \Throwable $first  - primary exception
     * @param  \Throwable $second - secondary exception
     *
     * @return void
     */
    private static function handleExceptionOnException(\Throwable $first, \Throwable $second): void {
        try {
            // last chance to log something
            $indent = ' ';
            $msg1  = trim(ErrorHandler::getVerboseMessage($first, $indent));
            $msg1  = $indent.'[FATAL] Unhandled '.$msg1.NL.$indent.'in '.$first->getFile().' on line '.$first->getLine().NL.NL;
            $msg1 .= $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $msg1 .= ErrorHandler::getAdjustedTraceAsString($first, $indent);

            $msg2  = trim(ErrorHandler::getVerboseMessage($second, $indent));
            $msg2  = $indent.$msg2.NL.$indent.'in '.$second->getFile().' on line '.$second->getLine().NL.NL;
            $msg2 .= $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $msg2 .= ErrorHandler::getAdjustedTraceAsString($second, $indent);

            $msg  = $msg1 . NL;
            $msg .= $indent . 'followed by' . NL;
            $msg .= $msg2;

            // log everything to the system logger (never throws an error)
            $msg = str_replace(chr(0), '\0', $msg);                             // replace NUL bytes which mess up the logfile
            if (WINDOWS) {
                $msg = str_replace(NL, PHP_EOL, $msg);
            }
            if (CLI) {
                echo $msg;
            }
            error_log($msg, ERROR_LOG_DEFAULT);
        }
        catch (\Throwable $ex) {}                                               // intentionally eat it
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
     * @param  \Throwable $exception
     *
     * @return \Throwable - the same exception
     *
     * @link   https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.destructor
     */
    public static function handleDestructorException($exception) {
        if (self::$inShutdown) {
            $currentHandler = set_exception_handler(function() {});
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
    public static function onScriptShutdown() {
        self::$inShutdown = true;

        // Errors showing up here (e.g. "out-of-memory" errors) haven't been passed to an installed error handler.
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
     * Return the description of an error reporting level.
     *
     * @param  int $level - single error reporting level
     *
     * @return string
     */
    public static function errorLevelDescr(int $level) {
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
     * Return a readable representation of an error reporting flag.
     *
     * @param  int $flag - combination of error reporting levels
     *
     * @return string
     */
    public static function errorLevelToStr(int $flag): string {
        // ordered by human-readable priorities (for conversion to string)
        $allLevels = [
            E_NOTICE            => 'E_NOTICE',                  //     8
            E_WARNING           => 'E_WARNING',                 //     2
            E_ERROR             => 'E_ERROR',                   //     1
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',       //  4096
            E_CORE_WARNING      => 'E_CORE_WARNING',            //    32
            E_CORE_ERROR        => 'E_CORE_ERROR',              //    16
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',         //   128
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',           //    64
            E_PARSE             => 'E_PARSE',                   //     4
            E_USER_NOTICE       => 'E_USER_NOTICE',             //  1024
            E_USER_WARNING      => 'E_USER_WARNING',            //   512
            E_USER_ERROR        => 'E_USER_ERROR',              //   256
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',         // 16384
            E_DEPRECATED        => 'E_DEPRECATED',              //  8192
            E_STRICT            => 'E_STRICT',                  //  2048
        ];

        $setLevels = $notsetLevels = [];

        foreach ($allLevels as $level => $description) {
            if ($flag & $level) {
                $setLevels[] = $description;
            }
            else {
                $notsetLevels[] = $description;
            }
        }

        if (sizeof($setLevels) < sizeof($notsetLevels)) {
            $result = $setLevels ? join(' | ', $setLevels) : '0';
        }
        else {
            $result = join(' & ~', ['E_ALL', ...$notsetLevels]);
        }
        return $result;
    }


    /**
     * Return a readable representation of the specified LibXML errors.
     *
     * @param  \LibXMLError[] $errors - array of LibXML errors, e.g. as returned by <tt>libxml_get_errors()</tt>
     * @param  string[]       $lines  - the XML causing the errors split by line
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
                    };
                    // no break
                default:
                    $msg .= 'parser error';
            }
            $msg .= ': '.trim($error->message)             .NL;
            $msg .= ($lines[$error->line - 1] ?? '')       .NL;
            $msg .= str_repeat(' ', $error->column - 1).'^'.NL.NL;
        }

        return trim($msg);
    }


    /**
     * Return a more verbose version of a {@link \Throwable}'s message. The resulting message has the classname of the throwable
     * and in case of {@link \ErrorException}s also the severity level of the error prepended to the original message.
     *
     * @param  \Throwable     $throwable         - throwable
     * @param  string         $indent [optional] - indent all lines by the specified value (default: no indentation)
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - message
     */
    public static function getVerboseMessage(\Throwable $throwable, string $indent = '', ContentFilter $filter = null): string {
        $message = trim($throwable->getMessage());
        if ($filter) {
            $message = $filter->filterString($message);
        }

        if (!$throwable instanceof PHPError) {              // PHP errors are verbose enough
            $class = get_class($throwable);
            if ($throwable instanceof \ErrorException) {    // a PHP error not created by this ErrorHandler
                $class .= '('.self::errorLevelDescr($throwable->getSeverity()).')';
            }
            $message = $class.(strlen($message) ? ': ':'').$message;
        }

        if (strlen($indent)) {
            $message = str_replace(NL, NL.$indent, normalizeEOL($message));
        }
        return $indent.$message;
    }


    /**
     * Takes a regular PHP stacktrace and adjusts it to be more readable.
     *
     * @param  array<string[]> $trace           - regular PHP stacktrace
     * @param  string          $file [optional] - name of the file where the stacktrace was generated
     * @param  int             $line [optional] - line of the file where the stacktrace was generated
     *
     * @return array<string[]> - adjusted stacktrace
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
    public static function getAdjustedTrace(array $trace, string $file = 'unknown', int $line = 0): array {
        // check if the stacktrace is already adjusted
        if (isset($trace[0]['__ministruts_adjusted__'])) {
            return $trace;
        }

        // fix a missing first line if $file matches (e.g. with \SimpleXMLElement)
        if ($file != 'unknown' && $line) {
            if (($trace[0]['file'] ?? null)==$file && ($trace[0]['line'] ?? 0)==0) {
                $trace[0]['line'] = $line;
            }
        }

        // append a frame for the main script
        $trace[] = ['function' => '{main}'];

        // move fields FILE and LINE to the end by one position
        for ($i = sizeof($trace); $i--;) {
            if (isset($trace[$i-1]['file'])) $trace[$i]['file'] = $trace[$i-1]['file'];
            else                       unset($trace[$i]['file']);

            if (isset($trace[$i-1]['line'])) $trace[$i]['line'] = $trace[$i-1]['line'];
            else                       unset($trace[$i]['line']);

            $trace[$i]['__ministruts_adjusted__'] = '1';
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
        if (!isset($trace[$size-1]['file'])) {
            array_pop($trace);
        }
        return $trace;

        // TODO: fix wrong stack frames originating from calls to virtual static functions
        //
        // phalcon\mvc\Model::__callStatic()                  [php-phalcon]
        // vokuro\models\Users::findFirstByEmail() # line 27, file: F:\Projects\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
        // vokuro\auth\Auth->check()               # line 27, file: F:\Projects\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
    }


    /**
     * Return the adjusted stacktrace of an exception as a string. The returned string contains infos about nested exceptions.
     *
     * @param  \Throwable     $throwable         - any throwable
     * @param  string         $indent [optional] - indent the resulting lines by the specified value (default: no indentation)
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - readable stacktrace
     */
    public static function getAdjustedTraceAsString(\Throwable $throwable, string $indent = '', ContentFilter $filter = null): string {
        $trace  = self::getAdjustedTrace($throwable->getTrace(), $throwable->getFile(), $throwable->getLine());
        $result = self::formatStackTrace($trace, $indent, $filter);

        if ($cause = $throwable->getPrevious()) {
            // recursively add stacktraces of nested exceptions
            $message = trim(self::getVerboseMessage($cause, $indent, $filter));
            $result .= NL.$indent.'caused by'.NL.$indent.$message.NL.NL;
            $result .= self::getAdjustedTraceAsString($cause, $indent, $filter);
        }
        return $result;
    }


    /**
     * Return a formatted and more readable version of a stacktrace.
     *
     * @param  array<string[]> $trace             - stacktrace
     * @param  string          $indent [optional] - indent formatted lines by this value (default: no indenting)
     * @param  ?ContentFilter  $filter [optional] - the content filter to apply (default: none)
     *
     * @return string
     */
    public static function formatStackTrace(array $trace, string $indent = '', ContentFilter $filter = null): string {
        // TODO: if the trace contains frame arguments moderate the arguments using a provided filter

        $config  = self::di('config');
        $appRoot = $config ? $config['app.dir.root'] : null;
        $result  = '';
        $size    = sizeof($trace);
        $callLen = $lineLen = 0;

        for ($i=0; $i < $size; $i++) {              // align FILE and LINE
            /** @var array{file?:string, line?:string, class:string, function:string, type:string} $frame */
            $frame = &$trace[$i];

            $call = self::getStackFrameMethod($frame, true);
            if ($call != '{main}' && !strEndsWith($call, '{closure}')) {
                $call .= '()';
            }
            $callLen = max($callLen, strlen($call));
            $frame['call'] = $call;

            $frame['line'] = isset($frame['line']) ? ' # line '.$frame['line'].',' : '';
            $lineLen = max($lineLen, strlen($frame['line']));

            if (isset($frame['file'])) {
                $frame['file'] = ' file: '.(!$appRoot ? $frame['file'] : strRightFrom($frame['file'], $appRoot.DIRECTORY_SEPARATOR, 1, false, $frame['file']));
            }
            elseif (strStartsWith($call, 'phalcon\\')) {
                $frame['file'] = ' [php-phalcon]';
            }
            else {
                $frame['file'] = ' [php]';
            }
        }

        if ($appRoot) {
            $trace[] = ['call'=>'', 'line'=>'', 'file'=>' file base: '.$appRoot];
            $i++;
        }

        for ($i=0; $i < $size; $i++) {
            $call = $trace[$i]['call'];
            $file = $trace[$i]['file'];
            $line = $trace[$i]['line'];
            $result .= $indent.str_pad($call, $callLen).' '.str_pad($line, $lineLen).$file.NL;
        }
        return $result;
    }


    /**
     * Return a stack frame's full method name similar to the constant __METHOD__. Used for generating a formatted stacktrace.
     *
     * @param  string[] $frame                - stack frame
     * @param  bool     $nsToLower [optional] - whether to return the namespace part in lower case (default: unmodified)
     *
     * @return string - method name without trailing parentheses
     */
    public static function getStackFrameMethod(array $frame, bool $nsToLower = false): string {
        $class = $function = '';

        if (isset($frame['function'])) {
            $function = $frame['function'];

            if (isset($frame['class'])) {
                $class = $frame['class'];
                if ($nsToLower && is_int($pos = strrpos($class, '\\'))) {
                    $class = strtolower(substr($class, 0, $pos)).substr($class, $pos);
                }
                $class = $class.$frame['type'];
            }
            elseif ($nsToLower && is_int($pos = strrpos($function, '\\'))) {
                $function = strtolower(substr($function, 0, $pos)).substr($function, $pos);
            }
        }
        return $class.$function;
    }


    /**
     * Shift all frames from the beginning of a stacktrace up to and including the specified file and line.
     * Effectively, this brings the stacktrace in line with the specified file location.
     *
     * @param array<string[]> $trace - stacktrace to process
     * @param string          $file  - filename where an error was triggered
     * @param int             $line  - line number where an error was triggered
     *
     * @return array<string[]>
     */
    public static function shiftStackFramesByLocation(array $trace, string $file, int $line) {
        $result = $trace;
        $size = sizeof($trace);

        for ($i = 0; $i < $size; $i++) {
            if (isset($trace[$i]['file'], $trace[$i]['line']) && $trace[$i]['file'] == $file && $trace[$i]['line'] == $line) {
                $result = array_slice($trace, $i + 1);
                break;
            }
        }
        return $result;
    }


    /**
     * Shift all frames from the beginning of a stacktrace pointing to the specified method.
     *
     * @param  \Exception $exception - exception to modify
     * @param  string     $method    - method name
     *
     * @return int - number of removed frames
     */
    public static function shiftStackFramesByMethod(\Exception $exception, string $method): int {
        $trace  = $exception->getTrace();
        $size   = sizeof($trace);
        $file   = $exception->getFile();
        $line   = $exception->getLine();
        $method = strtolower($method);
        $count  = 0;

        while ($size > 0) {
            if (isset($trace[0]['function'])) {
                if (strtolower($trace[0]['function']) == $method) {
                    $frame = array_shift($trace);
                    $file = $frame['file'] ?? 'Unknown';
                    $line = $frame['line'] ?? 0;
                    $size--;
                    $count++;
                    continue;
                }
            }
            break;
        }

        self::setExceptionProperties($exception, $trace, $file, $line);
        return $count;
    }


    /**
     * Set the properties of an {@link \Exception}.
     *
     * @param  \Exception      $exception       - exception to modify
     * @param  array<string[]> $trace           - stacktrace
     * @param  string          $file [optional] - filename of the error location (default: unchanged)
     * @param  int             $line [optional] - line number of the error location (default: unchanged)
     *
     * @return void
     */
    private static function setExceptionProperties(\Exception $exception, array $trace, string $file = '', int $line = 0) {
        static $traceProperty = null;
        if (!$traceProperty) {
            $traceProperty = new \ReflectionProperty(\Exception::class, 'trace');
            $traceProperty->setAccessible(true);
        }
        static $fileProperty = null;
        if (!$fileProperty) {
            $fileProperty = new \ReflectionProperty(\Exception::class, 'file');
            $fileProperty->setAccessible(true);
        }
        static $lineProperty = null;
        if (!$lineProperty) {
            $lineProperty = new \ReflectionProperty(\Exception::class, 'line');
            $lineProperty->setAccessible(true);
        }

        $traceProperty->setValue($exception, $trace);
        if (func_num_args() > 2) $fileProperty->setValue($exception, $file);
        if (func_num_args() > 3) $lineProperty->setValue($exception, $line);
    }
}
