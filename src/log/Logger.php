<?php
namespace rosasurfer\log;

use rosasurfer\Application;
use rosasurfer\config\ConfigInterface;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\di\proxy\Request;
use rosasurfer\core\error\ErrorHandler;
use rosasurfer\core\error\PHPError;
use rosasurfer\core\exception\IllegalStateException;
use rosasurfer\core\exception\InvalidValueException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\net\NetTools;
use rosasurfer\net\http\CurlHttpClient;
use rosasurfer\net\http\HttpRequest;
use rosasurfer\net\http\HttpResponse;
use rosasurfer\net\mail\Mailer;

use function rosasurfer\echof;
use function rosasurfer\hsc;
use function rosasurfer\ini_get_bool;
use function rosasurfer\ksort_r;
use function rosasurfer\normalizeEOL;
use function rosasurfer\printPretty;
use function rosasurfer\stderr;
use function rosasurfer\stdout;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;
use function rosasurfer\strStartsWithI;

use const rosasurfer\CLI;
use const rosasurfer\ERROR_LOG_DEFAULT;
use const rosasurfer\L_DEBUG;
use const rosasurfer\L_ERROR;
use const rosasurfer\L_FATAL;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\L_WARN;
use const rosasurfer\NL;
use const rosasurfer\WINDOWS;


/**
 * Log a message through a chain of standard log handlers.
 *
 * This is a default logger implementation which is used if no other logger is registered. The logger passes every log message to a chain
 * of handlers. Each handler is enabled/disabled depending on the application environment and the application configuration.
 *
 *  - ErrorLogHandler: Passes a log message to the PHP system logger as configured by the PHP setting "error_log".
 *
 *  - PrintHandler:    Displays a log message on STDOUT/STDERR in CLI mode and as part of the HTTP response in a web context. In CLI mode
 *                     the print handler is always enabled. In a web context the print handler is enabled only for requests from localhost
 *                     and from explicitly white-listed (aka admin) IP addresses. The handler is also enabled if the PHP setting
 *                     "display_errors" is switched on.
 *
 *  - MailHandler:     Sends a log message to the configured mail receivers (email addresses). The handler is enabled if the application
 *                     configuration contains mail receivers for log messages.
 *
 * Loglevel configuration
 * ----------------------
 * Loglevels can be configured for the whole application and per single class. For classes without specific configuration the general
 * application loglevel applies. The default application loglevel is L_NOTICE.
 *
 * @example
 * <pre>
 *  log.level                = warn                             # set general application loglevel to L_WARN
 *  log.level.ClassA         = debug                            # set loglevel for "ClassA" to L_DEBUG
 *  log.level.foo\bar\ClassB = error                            # set loglevel for "foo\bar\ClassB" to L_ERROR
 *
 *  log.mail.receiver = user1@domain.tld, user2@domain.tld      # set mail receivers for the MailHandler
 * </pre>
 *
 *
 * TODO: Logger::resolveCaller() - test with Closure and internal PHP functions
 * TODO: refactor into separate appender classes
 * TODO: implement \Psr\Log\LoggerInterface and remove static crap
 * TODO: support full email addresses as in "Joe Blow <address@domain.tld>"
 */
class Logger extends StaticClass {


    /** @var int - default loglevel if no application loglevel is configured */
    const DEFAULT_LOGLEVEL = L_NOTICE;

    /** @var string[] - valid loglevels and string representations for the message formatters */
    private static $logLevels = [
        L_DEBUG  => 'Debug' ,
        L_INFO   => 'Info'  ,
        L_NOTICE => 'Notice',
        L_WARN   => 'Warn'  ,
        L_ERROR  => 'Error' ,
        L_FATAL  => 'Fatal' ,
    ];

    /** @var int - configured application loglevel */
    private static $appLogLevel = self::DEFAULT_LOGLEVEL;

    /** @var bool - whether the print handler for L_FATAL log messages is enabled */
    private static $printHandlerFatal = false;

    /** @var bool - whether the print handler for non L_FATAL log messages is enabled */
    private static $printHandlerNonFatal = false;

    /** @var int - counter for printed HTML messages */
    private static $printHtmlCounter = 0;

    /** @var bool - whether the mail handler is enabled */
    private static $mailHandler = false;

    /** @var string[] - mail receivers */
    private static $mailReceivers = [];

    /** @var bool - whether the PHP error_log handler is enabled */
    private static $errorLogHandler = true;


    /**
     * Initialize the Logger.
     */
    private static function init() {
        static $initialized = false;
        if ($initialized) return;

        /** @var ConfigInterface $config */
        $config = self::di('config');

        // resolve application default loglevel (fall-back to the built-in default)
        $logLevel = $config->get('log.level', '');
        if (is_array($logLevel))
            $logLevel = isset($logLevel['']) ? $logLevel[''] : '';
        $logLevel = self::logLevelToId($logLevel) ?: self::DEFAULT_LOGLEVEL;
        self::$appLogLevel = $logLevel;

        // mail handler: enabled if mail receivers are configured
        $receivers = [];
        foreach (explode(',', $config->get('log.mail.receiver', '')) as $receiver) {
            if ($receiver = trim($receiver)) {
                if (filter_var($receiver, FILTER_VALIDATE_EMAIL)) {         // silently skip invalid addresses
                    $receivers[] = $receiver;
                }
            }
        }
        self::$mailHandler = (bool) $receivers;
        self::$mailReceivers = $receivers;

        // print handler for L_FATAL log messages: enabled on CLI, local/white-listed web access or if explicitly enabled
        self::$printHandlerFatal = CLI || Application::isAdminIP() || ini_get_bool('display_errors');

        // print handler for non L_FATAL log messages: enabled on CLI, local web access, if explicitly enabled or if the mail handler is disabled
        self::$printHandlerNonFatal = CLI || in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', $_SERVER['SERVER_ADDR']])
                                          || ini_get_bool('display_errors')
                                          || (self::$printHandlerFatal && !self::$mailHandler);

        // PHP error_log handler: enabled if the mail handler is disabled
        self::$errorLogHandler = !self::$mailHandler;

        $initialized = true;
    }


    /**
     * Convert a loglevel description to a loglevel constant.
     *
     * @param  string $value - loglevel description
     *
     * @return int|null - loglevel constant or NULL, if $value is not a valid loglevel description
     */
    public static function logLevelToId($value) {
        Assert::string($value);

        switch (strtolower($value)) {
            case 'debug' : return L_DEBUG;
            case 'info'  : return L_INFO;
            case 'notice': return L_NOTICE;
            case 'warn'  : return L_WARN;
            case 'error' : return L_ERROR;
            case 'fatal' : return L_FATAL;
            default:
                return null;
        }
    }


    /**
     * Resolve the loglevel of the specified class.
     *
     * @param  string $class [optional] - class name
     *
     * @return int - configured loglevel or the application loglevel if no class specific loglevel is configured
     */
    public static function getLogLevel($class = '') {
        Assert::string($class);
        self::init();

        // read the configured class specific loglevels
        static $logLevels = null;
        if ($logLevels === null) {
            /** @var ConfigInterface $config */
            $config = self::di('config');

            $logLevels = $config->get('log.level', []);
            if (is_string($logLevels))
                $logLevels = ['' => $logLevels];                            // only the general application loglevel is configured

            foreach ($logLevels as $className => $level) {
                Assert::string($level, 'config value "log.level.'.$className.'"');

                if ($level == '') {                                         // classes with empty values fall back to the application loglevel
                    unset($logLevels[$className]);
                }
                else {
                    $logLevel = self::logLevelToId($level);
                    if (!$logLevel) throw new InvalidValueException('Invalid configuration value for "log.level.'.$className.'" = '.$level);
                    $logLevels[$className] = $logLevel;

                    if (strStartsWith($className, '\\')) {                  // normalize class names: remove leading back slash
                        unset($logLevels[$className]);
                        $className = substr($className, 1);
                        $logLevels[$className] = $logLevel;
                    }
                }
            }
            $logLevels = \array_change_key_case($logLevels, CASE_LOWER);    // normalize class names: lower-case for case-insensitive look-up
        }

        // look-up the loglevel for the specified class
        $class = strtolower($class);
        if (isset($logLevels[$class]))
            return $logLevels[$class];

        // return the general application loglevel if no class specific loglevel is configured
        return self::$appLogLevel;
    }


    /**
     * Log a PHPError.
     *
     * @param PHPError $error
     */
    public static function logPHPError(PHPError $error) {
        echof('Logger::logPHPError()');

        $context = [
            'file'      => $error->getFile(),
            'line'      => $error->getLine(),
            'php-error' => $error,
        ];
        self::log($error, L_ERROR, $context);
    }


    /**
     * Log an unhandled exception.
     *
     * @param  \Exception|\Throwable $exception - exception (PHP5) or throwable (PHP7)
     */
    public static function logUnhandledException($exception) {
        echof('Logger::logUnhandledException()');

        $context = [
            'file'                => $exception->getFile(),
            'line'                => $exception->getLine(),
            'unhandled-exception' => $exception,
        ];
        self::log($exception, L_FATAL, $context);                           // log with the highest level
    }


    /**
     * Log a message or an exception.
     *
     * @param  string|object $loggable           - a string or an object implementing <tt>__toString()</tt>
     * @param  int           $level              - loglevel
     * @param  array         $context [optional] - logging context with additional data
     *
     * @return bool - success status
     */
    public static function log($loggable, $level, array $context = []) {
        self::init();

        // detect and handle a failing logger
        $logException = null;
        try {
            // detect and block recursive calls (should we instead block duplicate messages?)
            static $isActive = false;
            if ($isActive) throw new IllegalStateException('Detected recursive call of '.__METHOD__.'(), aborting...');
            $isActive = true;

            // validate parameters
            if (!is_string($loggable)) {
                Assert::hasMethod($loggable, '__toString', '$loggable');
                if (!$loggable instanceof \Throwable && !$loggable instanceof \Exception) {
                    $loggable = (string) $loggable;
                }
            }
            Assert::int($level, '$level');
            if (!isset(self::$logLevels[$level])) throw new InvalidValueException('Invalid parameter $level: '.$level.' (not a loglevel)');

            // filter messages below the active loglevel
            $filtered = false;
            if ($level != L_FATAL) {                                            // L_FATAL is never filtered
                !isset($context['class']) && self::resolveCaller($context);     // resolve the calling class and check its loglevel
                $filtered = $level < self::getLogLevel($context['class']);      // message is below the active loglevel
            }

            // filter "headers already sent" errors triggered by a previously printed message
            if (!$filtered && !CLI && self::$printHtmlCounter && is_object($loggable)) {
                $filtered = (bool)preg_match('/- headers already sent (by )?\(output started at /', $loggable->getMessage());
            }

            // invoke all active log handlers
            if (!$filtered) {
                if ($level == L_FATAL) $printHandler = 'printHandlerFatal';
                else                   $printHandler = 'printHandlerNonFatal';

                self::$errorLogHandler && self::invokeErrorLogHandler($loggable, $level, $context);
                self::${$printHandler} && self::invokePrintHandler   ($loggable, $level, $context);
                self::$mailHandler     && self::invokeMailHandler    ($loggable, $level, $context);
            }
            $isActive = false;                                                  // unlock the section
        }
        catch (\Throwable $logException) {}
        catch (\Exception $logException) {}

        if ($logException) {
            // If the call comes from the internal exception handler failed logging is already handled. If the call comes
            // from user-land make sure the message doesn't get lost and is logged to the PHP default error log.
            if (!isset($context['unhandled-exception'])) {
                $file = isset($context['file']) ? $context['file'] : '';
                $line = isset($context['line']) ? $context['line'] : '';
                $msg  = 'PHP ['.strtoupper(self::$logLevels[$level]).'] '.$loggable.PHP_EOL.' in '.$file.' on line '.$line;
                error_log(trim($msg), ERROR_LOG_DEFAULT);
            }
            throw $logException;
        }
        return true;
    }


    /**
     * Pass a log message to the PHP system logger via "error_log()".
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception
     * @param  int                          $level    - loglevel
     * @param  array                        $context  - reference to the log context with additional data
     */
    private static function invokeErrorLogHandler($loggable, $level, array &$context) {
        echof('Logger::invokeErrorLogHandler()  error_log="'.ini_get('error_log').'"');
        !isset($context['cliMessage']) && self::composeCliMessage($loggable, $level, $context);

        $msg = ' '.$context['cliMessage'];
        $msg = str_replace(chr(0), '\0', $msg);                             // replace NUL bytes which mess up the logfile
        $msg = rtrim($msg).PHP_EOL.PHP_EOL.str_repeat('-', 140);

        error_log($msg, ERROR_LOG_DEFAULT);

        // ini_get('error_log') controls whether a message is sent to syslog, a file or the SAPI logger
        // --------------------------------------------------------------------------------------------
        // (empty)     : Errors are sent to the SAPI logger, e.g. the Appache error log or STDERR in CLI mode (default).
        // "syslog"    : Errors are sent to the system logger. On Unix this means syslog(3), and on Windows it means the event log.
        // "<filepath>": Name of a file where errors should be logged.
    }


    /**
     * Display a log message on STDOUT, STDERR or as part of the HTTP response.
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception
     * @param  int                          $level    - loglevel
     * @param  array                        $context  - reference to the log context with additional data
     */
    private static function invokePrintHandler($loggable, $level, array &$context) {
        if (CLI) {
            if (!self::$errorLogHandler || !empty(ini_get('error_log'))) {  // only if the errorLogHandler doesn't print to STDERR
                !isset($context['cliMessage']) && self::composeCliMessage($loggable, $level, $context);
                $message = $context['cliMessage'];

                if (isset($context['php-error']) || isset($context['unhandled-exception'])) stderr($message.PHP_EOL);
                else                                                                        stdout($message.PHP_EOL);
            }
        }
        else {
            !isset($context['htmlMessage']) && self::composeHtmlMessage($loggable, $level, $context);
            echo $context['htmlMessage'].PHP_EOL;
            self::$printHtmlCounter++;
        }
        ob_get_level() && ob_flush();
    }


    /**
     * Send the message to the configured mail receivers.
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception
     * @param  int                          $level    - loglevel
     * @param  array                        $context  - reference to the log context with additional data
     */
    private static function invokeMailHandler($loggable, $level, array &$context) {
        !isset($context['mailSubject'], $context['mailMessage']) && self::composeMailMessage($loggable, $level, $context);
        $subject = $context['mailSubject'];
        $message = $context['mailMessage'];

        /** @var ConfigInterface $config */
        $config  = self::di('config');
        $options = $headers = [];
        $sender  = null;

        if (strlen($name = $config->get('log.mail.profile', ''))) {
            $options = $config->get('mail.profile.'.$name, []);
            $sender  = $config->get('mail.profile.'.$name.'.from', null);
            $headers = $config->get('mail.profile.'.$name.'.headers', []);
        }
        static $mailer; !$mailer && $mailer=Mailer::create($options);

        foreach (self::$mailReceivers as $receiver) {
            $mailer->sendMail($sender, $receiver, $subject, $message, $headers);
        }
    }


    /**
     * Compose a CLI log message and store it under $context['cliMessage'].
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception
     * @param  int                          $level    - loglevel
     * @param  array                        $context  - reference to the log context
     */
    private static function composeCliMessage($loggable, $level, array &$context) {
        !isset($context['file'], $context['line']) && self::resolveCallLocation($context);
        $file = $context['file'];
        $line = $context['line'];

        $cliMessage = null;
        $indent = ' ';

        // compose message
        if (is_string($loggable)) {
            // $loggable is a string
            $msg   = $loggable;
            $lines = explode(NL, normalizeEOL($msg));   // indent multiline messages
            $eoMsg = '';
            if (strEndsWith($msg, NL)) {
                \array_pop($lines);
                $eoMsg = PHP_EOL;
            }
            $msg = join(PHP_EOL.$indent, $lines).$eoMsg;
            $cliMessage = '['.strtoupper(self::$logLevels[$level]).'] '.$msg.PHP_EOL.$indent.'in '.$file.' on line '.$line.PHP_EOL;

            // append an existing context exception
            if (isset($context['exception'])) {
                $exception   = $context['exception'];
                $msg         = $indent.trim(ErrorHandler::getBetterMessage($exception, $indent));
                $cliMessage .= PHP_EOL.$msg.PHP_EOL.PHP_EOL;
                $traceStr    = $indent.'Stacktrace:'.PHP_EOL.$indent.'-----------'.PHP_EOL;
                $traceStr   .= ErrorHandler::getBetterTraceAsString($exception, $indent);
                $cliMessage .= PHP_EOL.$traceStr;
            }
            elseif (isset($context['trace'])) {
                // otherwise append the internal stacktrace
                $traceStr    = $indent.'Stacktrace:'.PHP_EOL.$indent.'-----------'.PHP_EOL;
                $traceStr   .= ErrorHandler::formatTrace($context['trace'], $indent);
                $cliMessage .= PHP_EOL.$traceStr;
            }
        }
        else {
            // $loggable is an exception
            $type = '';
            $msg  = trim(ErrorHandler::getBetterMessage($loggable, $indent));
            if (isset($context['unhandled-exception'])) {
                $type = 'Unhandled ';
                if ($loggable instanceof PHPError) {
                    $msg   = strRightFrom($msg, ':');
                    $type .= 'PHP Error:';
                }
            }
            $cliMessage  = '['.strtoupper(self::$logLevels[$level]).'] '.$type.$msg.PHP_EOL.$indent.'in '.$file.' on line '.$line.PHP_EOL;
            $traceStr    = $indent.'Stacktrace:'.PHP_EOL.$indent.'-----------'.PHP_EOL;
            $traceStr   .= ErrorHandler::getBetterTraceAsString($loggable, $indent);
            $cliMessage .= PHP_EOL.$traceStr;
        }

        $context['cliMessage'] = $cliMessage;
    }


    /**
     * Compose an HTML log message and store it under $context['htmlMessage'].
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception
     * @param  int                          $level    - loglevel
     * @param  array                        $context  - reference to the log context
     */
    private static function composeHtmlMessage($loggable, $level, array &$context) {
        !isset($context['file'], $context['line']) && self::resolveCallLocation($context);
        $file = $context['file'];
        $line = $context['line'];

        // break out of unfortunate HTML tags
        $divId = md5('ministruts');
        $html  = '<a attr1="" attr2=\'\'></a></meta></title></head></script></img></input></select></textarea></label></li></ul></font></pre></tt></code></small></i></b></span></div>';
        $html .= '<div id="'.$divId.'-'.self::$printHtmlCounter.'"
                        align="left"
                        style="display:initial; visibility:initial; clear:both;
                        position:relative; z-index:4294967295; top:initial; left:initial;
                        float:left; width:initial; height:initial
                        margin:0; padding:4px; border-width:0;
                        font:normal normal 12px/normal arial,helvetica,sans-serif; line-height:12px;
                        color:black; background-color:#ccc">';
        $indent = ' ';

        // compose message
        if (is_string($loggable)) {
            // $loggable is a string
            $msg   = $loggable;
            $html .= '<span style="white-space:nowrap"><span style="font-weight:bold">['.strtoupper(self::$logLevels[$level]).']</span> <span style="white-space:pre; line-height:8px">'.nl2br(hsc($msg)).'</span></span><br><br>';
            $html .= 'in <span style="font-weight:bold">'.$file.'</span> on line <span style="font-weight:bold">'.$line.'</span><br>';

            // append an existing context exception
            if (isset($context['exception'])) {
                $exception = $context['exception'];
                $msg       = ErrorHandler::getBetterMessage($exception);
                $html     .= '<br>'.nl2br(hsc($msg)).'<br><br>';
                $traceStr  = $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
                $traceStr .= ErrorHandler::getBetterTraceAsString($exception, $indent);
                $html     .= printPretty($traceStr, true, false);
            }
            elseif (isset($context['trace'])) {
                // otherwise append the internal stacktrace
                $traceStr  = $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
                $traceStr .= ErrorHandler::formatTrace($context['trace'], $indent);
                $html     .= '<span style="clear:both"></span><br>'.printPretty($traceStr, true, false).'<br>';
            }
        }
        else {
            // $loggable is an exception
            $type = null;
            $msg  = trim(ErrorHandler::getBetterMessage($loggable));
            if (isset($context['unhandled-exception'])) {
                $type = 'Unhandled ';
                if ($loggable instanceof PHPError) {
                    $msg   = strRightFrom($msg, ':');
                    $type .= 'PHP Error:';
                }
            }
            $html     .= '<span style="white-space:nowrap"><span style="font-weight:bold">['.strtoupper(self::$logLevels[$level]).']</span> <span style="white-space:pre; line-height:8px">'.nl2br(hsc($type.$msg)).'</span></span><br><br>';
            $html     .= 'in <span style="font-weight:bold">'.$file.'</span> on line <span style="font-weight:bold">'.$line.'</span><br>';
            $traceStr  = $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $traceStr .= ErrorHandler::getBetterTraceAsString($loggable, $indent);
            $html     .= '<span style="clear:both"></span><br>'.printPretty($traceStr, true, false).'<br>';
        }

        // append the current HTTP request
        if (!CLI) {
            $html .= '<br style="clear:both"><br>'.printPretty('Request:'.NL.'--------'.NL.Request::instance(), true, false).'<br>';
        }

        // close the HTML tag and add some JavaScript to ensure it becomes visible                      // id = md5('ministruts')
        $html .= '</div>
                  <script>
                      var bodies = document.getElementsByTagName("body");
                      if (bodies && bodies.length)
                         bodies[0].appendChild(document.getElementById("'.$divId.'-'.self::$printHtmlCounter.'"));
                  </script>';
        // store the HTML tag
        $context['htmlMessage'] = $html;
    }


    /**
     * Compose a mail log message and store it in the passed log context under the keys "mailSubject" and "mailMessage".
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception to log
     * @param  int                          $level    - loglevel of the loggable
     * @param  array                        $context  - reference to the log context
     */
    private static function composeMailMessage($loggable, $level, array &$context) {
        !isset($context['cliMessage']) && self::composeCliMessage($loggable, $level, $context);
        $msg = $context['cliMessage'];
        $location = null;

        // compose message
        if (CLI) {
            $msg     .= NL.NL.'Shell:'.NL.'------'.NL.print_r(ksort_r($_SERVER), true).NL;
            $location = realpath($_SERVER['PHP_SELF']);
        }
        else {
            $request  = Request::instance();
            $location = strLeftTo($request->getUrl(), '?');
            $session  = null;

            if (isset($_SESSION)) {
                $session = $_SESSION;
            }
            else if ($request->hasSessionId()) {
                try {
                    $request->getSession();                         // Make sure the session was restarted.
                }                                                   // A session may exist but content was delivered before
                catch (PHPError $error) {                           // the session was restarted.
                    if (!preg_match('/- headers already sent (by )?\(output started at /', $error->getMessage())) throw $error;
                }
                if (session_id() == $request->getSessionId()) {     // if both differ the id was regenerated and
                    $session = $_SESSION;                           // the session is considered empty (except markers)
                }
            }
            $session = isset($session) ? print_r(ksort_r($session), true) : null;
            $ip      = $_SERVER['REMOTE_ADDR'];
            $host    = NetTools::getHostByAddress($ip);
            if ($host != $ip)
                $ip .= ' ('.$host.')';
            $msg .= NL.NL.'Request:'.NL.'--------'.NL.$request.NL.NL
              . 'Session: '.($session ? NL.'--------'.NL.$session : '(none)'.NL.'--------'.NL).NL.NL
              . 'Server:'.NL.'-------'.NL.print_r(ksort_r($_SERVER), true).NL.NL
              . 'IP:   '.$ip.NL
              . 'Time: '.date('Y-m-d H:i:s').NL;
        }
        $type = isset($context['unhandled-exception']) ? 'Unhandled Exception ':'';

        // store subject and message
        $context['mailSubject'] = 'PHP ['.self::$logLevels[$level].'] '.$type.(CLI ? 'in ':'at ').$location;
        $context['mailMessage'] = $msg;
    }


    /**
     * Resolve the class scope the logger was called from and store it under $context['class']. For log messages from the ErrorHandler
     * this context field will be pre-populated. For log messages from user-land the field may be empty and is resolved here.
     *
     * @param  array $context - reference to the log context
     *
     * TODO: test with Closure and internal PHP functions
     */
    private static function resolveCaller(array &$context) {
        !isset($context['trace']) && self::generateTrace($context);
        $trace = $context['trace'];
        $context['class'] = isset($trace[0]['class']) ? $trace[0]['class'] : '';
    }


    /**
     * Resolve the file location the logger was called from and store it under $context['file'] and $context['line']. For log messages
     * from the ErrorHandler these context fields will be pre-populated. For log messages from user-land the fields may be empty and are
     * resolved here.
     *
     * @param  array $context - reference to the log context
     */
    private static function resolveCallLocation(array &$context) {
        !isset($context['trace']) && self::generateTrace($context);

        foreach ($context['trace'] as $i => $frame) {
            if (isset($frame['file'])) {                // find the first frame with 'file' (skips internal PHP functions)
                $context['file'] = $frame['file'];
                $context['line'] = $frame['line'];
                return;
            }
        }
        $context['file'] = '(unknown)';
        $context['line'] = '(?)';
    }


    /**
     * Generate an internal stacktrace and store it under $context['trace'].
     *
     * @param  array $context - reference to the log context
     */
    private static function generateTrace(array &$context) {
        $trace = ErrorHandler::getBetterTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), __FILE__, __LINE__);
        $LoggerLog = strtolower(__CLASS__.'::log');

        foreach ($trace as $i => $frame) {
            if (!isset($frame['class'], $frame['function'])) break;
            unset($trace[$i]);                                                  // remove all frames starting at Logger::log()

            $frameFunction = strtolower($frame['class'].'::'.$frame['function']);
            if ($frameFunction == $LoggerLog) {
                break;
            }
        }
        $context['trace'] = \array_values($trace);
    }
}
