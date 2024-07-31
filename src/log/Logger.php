<?php
namespace rosasurfer\log;

use rosasurfer\Application;
use rosasurfer\config\ConfigInterface;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\DebugHelper;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\core\exception\error\PHPError;
use rosasurfer\di\proxy\Request;
use rosasurfer\net\NetTools;
use rosasurfer\net\http\CurlHttpClient;
use rosasurfer\net\http\HttpRequest;
use rosasurfer\net\http\HttpResponse;
use rosasurfer\net\mail\Mailer;

use function rosasurfer\hsc;
use function rosasurfer\ini_get_bool;
use function rosasurfer\ksort_r;
use function rosasurfer\normalizeEOL;
use function rosasurfer\printPretty;
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
 * This is the framework's default logger implementation which is used if no other logger is registered. The logger
 * passes the message to a chain of handlers. Each handler is invoked depending on the application's runtime environment
 * (CLI vs. web server, local vs. remote access) and the application configuration.
 *
 *  - PrintHandler:    Display the message on the standard output device (STDOUT in CLI mode, HTTP response in a web context).
 *                     If the script runs in CLI mode the handler is always invoked. If the script runs in a web context the
 *                     handler is always invoked for local requests (i.e. from localhost). For remote requests the handler
 *                     is invoked only if the remote IP address has admin access or if the PHP configuration option
 *                     "display_errors" is set to TRUE.
 *
 *  - MailHandler:     Send the message to the configured mail receivers (email addresses). The handler is invoked if
 *                     the application configuration contains one or more mail receivers for log messages.
 *
 *                     Example:
 *                     --------
 *                     log.mail.receiver = address-1@domain.tld, address-2@another-domain.tld
 *
 *  - SMSHandler:      Send the message to the configured SMS receivers (phone numbers). The handler is invoked if the
 *                     application configuration contains one or more phone numbers for log messages and a valid SMS
 *                     operator configuration (at log time). For text messages an additional loglevel constraint can be
 *                     specified (on top of the default loglevel constraint). At the moment the message providers
 *                     Clickatell and Nexmo are supported.
 *
 *                     Example:
 *                     --------
 *                     log.sms.level    = error                           # additional loglevel constraint for SMS
 *                     log.sms.receiver = +3591234567, +441234567         # international number format
 *
 *  - ErrorLogHandler: The last resort log handler. Passes the message to the PHP default error log mechanism as defined
 *                     by the PHP configuration value "error_log". The handler is invoked if the MailHandler was not
 *                     invoked or if a circular or similar fatal error occurred. Typically this handler writes into the
 *                     PHP error log file which again should be monitored by a logwatch script.
 *
 *
 * Loglevel configuration:
 * -----------------------
 * The loglevel can be configured per class. For a class without a specific configuration the specified application
 * loglevel applies. Without a specified application loglevel the built-in default loglevel of L_NOTICE is used.
 *
 * Example:
 *  log.level                  = warn              # the general application loglevel is set to L_WARN
 *  log.level.MyClassA         = debug             # the loglevel for "MyClassA" is set to L_DEBUG
 *  log.level.foo\bar\MyClassB = notice            # the loglevel for "foo\bar\MyClassB" is set to L_NOTICE
 *  log.sms.level              = error             # the loglevel for text messages is set a bit higher to L_ERROR
 *
 *
 * @todo   Logger::resolveLogCaller() - test with Closure and internal PHP functions
 * @todo   refactor and separate handlers into single classes
 * @todo   implement \Psr\Log\LoggerInterface and remove static crap
 * @todo   implement full mail address support as in "Joe Blow <address@domain.tld>"
 */
class Logger extends StaticClass {


    /** @var int - built-in default loglevel if no application loglevel is configured */
    const DEFAULT_LOGLEVEL = L_NOTICE;


    /** @var int - application loglevel */
    private static $appLogLevel = self::DEFAULT_LOGLEVEL;


    /** @var bool - whether the print handler for L_FATAL messages is enabled */
    private static $printFatalHandler = false;

    /** @var bool - whether the print handler for non L_FATAL messages is enabled */
    private static $printNonfatalHandler = false;

    /** @var int - counter for messages handled by the print handler */
    private static $printCounter = 0;

    /** @var bool - whether the mail handler is enabled */
    private static $mailHandler = false;

    /** @var string[] - mail receivers */
    private static $mailReceivers = [];

    /** @var bool - whether the SMS handler is enabled */
    private static $smsHandler = false;

    /** @var string[] - SMS receivers */
    private static $smsReceivers = [];

    /** @var array - SMS options; resolved at log message time */
    private static $smsOptions = [];

    /** @var bool - whether the PHP error_log handler is enabled */
    private static $errorLogHandler = true;

    /** @var string[] - loglevel descriptions for message formatter */
    private static $logLevels = [
        L_DEBUG  => 'Debug' ,
        L_INFO   => 'Info'  ,
        L_NOTICE => 'Notice',
        L_WARN   => 'Warn'  ,
        L_ERROR  => 'Error' ,
        L_FATAL  => 'Fatal' ,
    ];


    /**
     * Initialize the Logger configuration.
     *
     * @return void
     */
    private static function init() {
        static $initialized = false;
        if ($initialized) return;

        /** @var ConfigInterface $config */
        $config = self::di('config');

        // Get the application's default loglevel configuration (fall back to the built-in default).
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
        self::$mailHandler   = (bool) $receivers;
        self::$mailReceivers =        $receivers;


        // L_FATAL print handler: enabled on local/white-listed access or if explicitely enabled
        self::$printFatalHandler = CLI || Application::isAdminIP() || ini_get_bool('display_errors');


        // non L_FATAL print handler: enabled on local access, if explicitely enabled or if the mail handler is disabled
        self::$printNonfatalHandler = CLI || in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', $_SERVER['SERVER_ADDR']])
                                          || ini_get_bool('display_errors')
                                          || (self::$printFatalHandler && !self::$mailHandler);

        // SMS handler: enabled if SMS receivers are configured (operator settings are checked at log time)
        self::$smsReceivers = [];
        foreach (explode(',', $config->get('log.sms.receiver', '')) as $receiver) {
            if ($receiver=trim($receiver)) {
                if (strStartsWith($receiver, '+' )) $receiver = substr($receiver, 1);
                if (strStartsWith($receiver, '00')) $receiver = substr($receiver, 2);
                if (!ctype_digit($receiver)) {
                    self::log('Invalid SMS receiver configuration: "'.$receiver.'"', L_WARN, ['class'=>__CLASS__, 'file'=>__FILE__, 'line'=>__LINE__]);
                    continue;
                }
                self::$smsReceivers[] = $receiver;
            }
        }
        $logLevel = $config->get('log.sms.level', self::$appLogLevel);
        if (is_string($logLevel)) {             // a string if configured
            $logLevel = self::logLevelToId($logLevel) ?: self::$appLogLevel;
        }

        $options = $config->get('sms', []);
        Assert::isArray($options, 'config value "sms"');
        self::$smsOptions = $options;

        self::$smsHandler = self::$smsReceivers && self::$smsOptions;

        // PHP error_log handler: enabled if the mail handler is disabled
        self::$errorLogHandler = !self::$mailHandler;

        $initialized = true;
    }


    /**
     * Convert a loglevel description to a loglevel constant.
     *
     * @param  string $value - loglevel description
     *
     * @return ?int - loglevel constant or NULL, if $value is not a valid loglevel description
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
                    if (!$logLevel) throw new InvalidArgumentException('Invalid configuration value for "log.level.'.$className.'" = '.$level);
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
     * Log a message.
     *
     * @param  string|object $loggable           - a message or an object implementing <tt>__toString()</tt>
     * @param  int           $level              - loglevel
     * @param  array         $context [optional] - logging context with additional data
     *
     * @return void
     */
    public static function log($loggable, $level, array $context = []) {
        self::init();

        // wrap everything in try-catch to handle the case when logging fails
        $ex = null;
        try {
            // block recursive calls
            // TODO: instead of recursive calls block duplicate messages
            static $isActive = false;
            if ($isActive) throw new RuntimeException('Detected recursive call of '.__METHOD__.'(), aborting...');
            $isActive = true;                                           // lock the method

            // validate parameters
            if (!is_string($loggable)) {
                Assert::hasMethod($loggable, '__toString', '$loggable');
                if (!$loggable instanceof \Throwable && !$loggable instanceof \Exception) { // @phpstan-ignore instanceof.alwaysFalse (PHP5 compatibility)
                    $loggable = (string) $loggable;
                }
            }
            Assert::int($level, '$level');
            if (!\key_exists($level, self::$logLevels)) throw new InvalidArgumentException('Invalid argument $level: '.$level.' (not a loglevel)');

            $filtered = false;

            // filter messages below the active loglevel
            if ($level != L_FATAL) {                                            // L_FATAL (highest) can't be below
                if (!\key_exists('class', $context))                            // resolve the calling class and check its loglevel
                    self::resolveLogCaller($context);
                $filtered = $level < self::getLogLevel($context['class']);      // message is below the active loglevel
            }

            // filter "headers already sent" errors triggered by a previously printed HTML log message
            if (!$filtered && !CLI && self::$printCounter && is_object($loggable)) {
                if (preg_match('/- headers already sent (by )?\(output started at /', $loggable->getMessage())) {
                    $filtered = true;
                }
            }

            // invoke all active log handlers
            if (!$filtered) {
                if ($level == L_FATAL) $printHandler = 'printFatalHandler';
                else                   $printHandler = 'printNonfatalHandler';

                self::${$printHandler} && self::invokePrintHandler   ($loggable, $level, $context);
                self::$mailHandler     && self::invokeMailHandler    ($loggable, $level, $context);
                self::$smsHandler      && self::invokeSmsHandler     ($loggable, $level, $context);
                self::$errorLogHandler && self::invokeErrorLogHandler($loggable, $level, $context);
            }

            // unlock the method
            $isActive = false;

        }
        catch (\Throwable $ex) {}
        catch (\Exception $ex) {}       // @phpstan-ignore catch.alreadyCaught (PHP5 compatibility)

        if ($ex) {
            // If the call comes from our framework's exception handler \rosasurfer\core\debug\ErrorHandler::handleException()
            // a failed logging is already handled. If the call comes from user-land code make sure the message doesn't get
            // lost and is logged to the PHP default error log.
            if (!\key_exists('unhandled-exception', $context)) {
                $file  = \key_exists('file', $context) ? $context['file'] : '';
                $line  = \key_exists('line', $context) ? $context['line'] : '';
                $level = \key_exists($level, self::$logLevels) ? strtoupper(self::$logLevels[$level]) : 'ERROR';
                $msg   = 'PHP ['.$level.'] '.$loggable.NL.' in '.$file.' on line '.$line;
                error_log(trim($msg), ERROR_LOG_DEFAULT);
            }
            throw $ex;
        }
    }


    /**
     * Display the message on the standard output device (STDOUT in CLI mode, HTTP response in a web context).
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception to log
     * @param  int                          $level    - loglevel of the loggable
     * @param  array                        $context  - reference to the log context with additional data
     *
     * @return void
     */
    private static function invokePrintHandler($loggable, $level, array &$context) {
        $message = null;

        if (CLI) {
            !\key_exists('cliMessage', $context) && self::composeCliMessage($loggable, $level, $context);
            $message .= $context['cliMessage'];
            if (\key_exists('cliExtra', $context))
                $message .= $context['cliExtra'];
        }
        else {
            !\key_exists('htmlMessage', $context) && self::composeHtmlMessage($loggable, $level, $context);
            $message = $context['htmlMessage'];
        }

        echo $message.NL;
        ob_get_level() && ob_flush();

        self::$printCounter++;
    }


    /**
     * Send the message to the configured mail receivers.
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception to log
     * @param  int                          $level    - loglevel of the loggable
     * @param  array                        $context  - reference to the log context with additional data
     *
     * @return void
     */
    private static function invokeMailHandler($loggable, $level, array &$context) {
        if (!\key_exists('mailSubject', $context) || !\key_exists('mailMessage', $context))
            self::composeMailMessage($loggable, $level, $context);

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
     * Send the message to the configured SMS receivers (phone numbers).
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception to log
     * @param  int                          $level    - loglevel of the loggable
     * @param  array                        $context  - reference to the log context with additional data
     *
     * @return void
     *
     * @todo   replace CURL dependency with internal PHP functions
     */
    private static function invokeSmsHandler($loggable, $level, array &$context) {
        if (!\key_exists('cliMessage', $context)) {
            self::composeCliMessage($loggable, $level, $context);
        }

        // CURL options (all service providers)
        $curlOptions = [];
        $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;                  // the SSL certifikat may be self-signed or invalid
      //$curlOptions[CURLOPT_VERBOSE       ] = true;                   // enable debugging

        // clean-up message
        $message = trim($context['cliMessage']);
        $message = normalizeEOL($message);                             // enforce Unix line-breaks
        $message = substr($message, 0, 150);                           // limit length to about one text message

        // check availability and use Clickatell
        if (isset(self::$smsOptions['clickatell'])) {
            $smsOptions = self::$smsOptions['clickatell'];
            if (!empty($smsOptions['user']) && !empty($smsOptions['password']) && !empty($smsOptions['api_id'])) {
                $params = [];
                $params['user'    ] = $smsOptions['user'    ];
                $params['password'] = $smsOptions['password'];
                $params['api_id'  ] = $smsOptions['api_id'  ];
                $params['text'    ] = $message;

                foreach (self::$smsReceivers as $receiver) {
                    $params['to'] = $receiver;
                    $url      = 'https://api.clickatell.com/http/sendmsg?'.http_build_query($params, '', '&');
                    $request  = new HttpRequest($url);
                    $client   = new CurlHttpClient($curlOptions);
                    $response = $client->send($request);
                    $status   = $response->getStatus();
                    $content  = $response->getContent();

                    if ($status != 200) {
                        try {
                            $description = isset(HttpResponse::$statusCodes[$status]) ? HttpResponse::$statusCodes[$status] : '?';
                            self::log('Unexpected HTTP status code '.$status.' ('.$description.') for URL: '.$request->getUrl(), L_WARN, ['class'=>__CLASS__, 'file'=>__FILE__, 'line'=>__LINE__]);
                        }
                        catch (\Throwable $ex) {}   // intentionally eat it
                        catch (\Exception $ex) {}   // @phpstan-ignore catch.alreadyCaught (PHP5 compatibility)
                        continue;
                    }
                    if (strStartsWithI($content, 'ERR: 113')) {
                        // TODO: 'ERR: 113' => max message parts exceeded, repeat with shortened message
                        // TODO:               consider to send concatenated messages
                    }
                }
                return;
            }
        }

        // check availability and use Nexmo
        // TODO encoding issues when sending to Bulgarian receivers (some chars are auto-converted to ciryllic crap)
        if (isset(self::$smsOptions['nexmo'])) {
            $smsOptions = self::$smsOptions['nexmo'];
            if (!empty($smsOptions['api_key']) && !empty($smsOptions['api_secret'])) {
                $params = [];
                $params['api_key'   ] =        $smsOptions['api_key'   ];
                $params['api_secret'] =        $smsOptions['api_secret'];
                $params['from'      ] = !empty($smsOptions['from'      ]) ? $smsOptions['from'] : 'PHP Logger';
                $params['text'      ] =        $message;

                foreach (self::$smsReceivers as $receiver) {
                    $params['to'] = $receiver;
                    $url      = 'https://rest.nexmo.com/sms/json?'.http_build_query($params, '', '&');
                    $request  = new HttpRequest($url);
                    $client   = new CurlHttpClient($curlOptions);
                    $response = $client->send($request);
                    $status   = $response->getStatus();
                    $content  = $response->getContent();
                    if ($status != 200) {
                        try {
                            $description = isset(HttpResponse::$statusCodes[$status]) ? HttpResponse::$statusCodes[$status] : '?';
                            self::log('Unexpected HTTP status code '.$status.' ('.$description.') for URL: '.$request->getUrl(), L_WARN, ['class'=>__CLASS__, 'file'=>__FILE__, 'line'=>__LINE__]);
                        }
                        catch (\Throwable $ex) {}   // intentionally eat it
                        catch (\Exception $ex) {}   // @phpstan-ignore catch.alreadyCaught (PHP5 compatibility)
                        continue;
                    }
                }
                return;
            }
        }
    }


    /**
     * Pass the message to the PHP default error log mechanism as defined by the PHP configuration value "error_log".
     *
     * ini_get('error_log')
     *
     * Name of the file where script errors should be logged. If the special value "syslog" is used, errors are sent to the
     * system logger instead. On Unix, this means syslog(3) and on Windows it means the event log. If this directive is not
     * set, errors are sent to the SAPI error logger. For example, it is an error log in Apache or STDERR in CLI mode.
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception to log
     * @param  int                          $level    - loglevel of the loggable
     * @param  array                        $context  - reference to the log context with additional data
     *
     * @return void
     */
    private static function invokeErrorLogHandler($loggable, $level, array &$context) {
        if (!\key_exists('cliMessage', $context))
            self::composeCliMessage($loggable, $level, $context);

        $msg = 'PHP '.$context['cliMessage'];
        if (\key_exists('cliExtra', $context))
            $msg .= $context['cliExtra'];
        $msg = str_replace(chr(0), '?', $msg);                   // replace NUL bytes which mess up the logfile

        if (CLI && empty(ini_get('error_log'))) {
            // Suppress duplicated output to STDERR, the PrintHandler already wrote to STDOUT.

            // TODO: Instead of messing around here the PrintHandler must not print to STDOUT if the ErrorLogHandler
            //       is active and prints to STDERR.
            // TODO: Suppress output to STDERR in interactive terminals only (i.e. not in CRON).
        }
        else {
            error_log(trim($msg), ERROR_LOG_DEFAULT);
        }
    }


    /**
     * Compose a CLI log message and store it in the passed log context under the keys "cliMessage" and "cliExtra".
     * The separation is used by the SMS handler which only sends the main message ("cliMessage").
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception to log
     * @param  int                          $level    - loglevel of the loggable
     * @param  array                        $context  - reference to the log context
     *
     * @return void
     */
    private static function composeCliMessage($loggable, $level, array &$context) {
        if (!\key_exists('file', $context) || !\key_exists('line', $context))
            self::resolveLogLocation($context);
        $file = $context['file'];
        $line = $context['line'];

        $cliMessage = $cliExtra = null;
        $indent = ' ';

        // compose message
        if (is_string($loggable)) {
            // $loggable is a simple message
            $msg = $loggable;

            $lines = explode(NL, normalizeEOL($msg));       // indent multiline messages
            $eom = '';
            if (strEndsWith($msg, NL)) {
                \array_pop($lines);
                $eom = NL;
            }
            $msg = join(NL.$indent, $lines).$eom;
            $cliMessage = '['.strtoupper(self::$logLevels[$level]).'] '.$msg.NL.$indent.'in '.$file.' on line '.$line.NL;

            // if there was no exception append the internal stacktrace to "cliExtra"
            if (!\key_exists('exception', $context) && \key_exists('trace', $context)) {
                $traceStr  = $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
                $traceStr .= DebugHelper::formatTrace($context['trace'], $indent);
                $cliExtra .= NL.$traceStr;
            }
        }
        else {
            // $loggable is an exception
            $type = null;
            $msg  = trim(DebugHelper::composeBetterMessage($loggable, $indent));
            if (\key_exists('unhandled-exception', $context)) {
                $type = 'Unhandled ';
                if ($loggable instanceof PHPError) {
                    $msg   = strRightFrom($msg, ':');
                    $type .= 'PHP Error:';
                }
            }
            $cliMessage = '['.strtoupper(self::$logLevels[$level]).'] '.$type.$msg.NL.$indent.'in '.$file.' on line '.$line.NL;

            // the stack trace will go into "cliExtra"
            $traceStr  = $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $traceStr .= DebugHelper::getBetterTraceAsString($loggable, $indent);
            $cliExtra .= NL.$traceStr;
        }

        // append an existing context exception to "cliExtra"
        if (\key_exists('exception', $context)) {
            $exception = $context['exception'];
            $msg       = $indent.trim(DebugHelper::composeBetterMessage($exception, $indent));
            $cliExtra .= NL.$msg.NL.NL;
            $traceStr  = $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $traceStr .= DebugHelper::getBetterTraceAsString($exception, $indent);
            $cliExtra .= NL.$traceStr;
        }

        // store main and extra message
        $context['cliMessage'] = $cliMessage;
        if ($cliExtra)
            $context['cliExtra'] = $cliExtra;
    }


    /**
     * Compose a mail log message and store it in the passed log context under the keys "mailSubject" and "mailMessage".
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception to log
     * @param  int                          $level    - loglevel of the loggable
     * @param  array                        $context  - reference to the log context
     *
     * @return void
     */
    private static function composeMailMessage($loggable, $level, array &$context) {
        if (!\key_exists('cliMessage', $context))
            self::composeCliMessage($loggable, $level, $context);

        $msg = $context['cliMessage'];
        if (\key_exists('cliExtra', $context))
            $msg .= $context['cliExtra'];
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
                    if (!preg_match('/- headers already sent (by )?\(output started at /', $error->getMessage()))
                        throw $error;
                }
                if (session_id() == $request->getSessionId())       // if both differ the id was regenerated and
                    $session = $_SESSION;                           // the session is considered empty (except markers)
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
        $type = (\key_exists('unhandled-exception', $context)) ? 'Unhandled Exception ':'';

        // store subject and message
        $context['mailSubject'] = 'PHP ['.self::$logLevels[$level].'] '.$type.(CLI ? 'in ':'at ').$location;
        $context['mailMessage'] = $msg;
    }


    /**
     * Compose a HTML log message and store it in the passed log context under the key "htmlMessage".
     *
     * @param  string|\Exception|\Throwable $loggable - message or exception to log
     * @param  int                          $level    - loglevel of the loggable
     * @param  array                        $context  - reference to the log context
     *
     * @return void
     */
    private static function composeHtmlMessage($loggable, $level, array &$context) {
        if (!\key_exists('file', $context) || !\key_exists('line', $context))
            self::resolveLogLocation($context);
        $file = $context['file'];
        $line = $context['line'];

        // break out of unfortunate HTML tags              // id = md5('ministruts')
        $html  = '<a attr1="" attr2=\'\'></a></meta></title></head></script></img></input></select></textarea></label></li></ul></font></pre></tt></code></small></i></b></span></div>';
        $html .= '<div id="99a05cf355861c76747b7176c778eed2'.self::$printCounter.'"
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
            // $loggable is a simple message
            $msg   = $loggable;
            $html .= '<span style="white-space:nowrap"><span style="font-weight:bold">['.strtoupper(self::$logLevels[$level]).']</span> <span style="white-space:pre; line-height:8px">'.nl2br(hsc($msg)).'</span></span><br><br>';
            $html .= 'in <span style="font-weight:bold">'.$file.'</span> on line <span style="font-weight:bold">'.$line.'</span><br>';

            // attach the internal stacktrace if there was no exception
            if (!\key_exists('exception', $context) && \key_exists('trace', $context)) {
                $traceStr  = $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
                $traceStr .= DebugHelper::formatTrace($context['trace'], $indent);
                $html     .= '<span style="clear:both"></span><br>'.printPretty($traceStr, true).'<br>';
            }
        }
        else {
            // $loggable is an exception
            $type = null;
            $msg  = trim(DebugHelper::composeBetterMessage($loggable));
            if (\key_exists('unhandled-exception', $context)) {
                $type = 'Unhandled ';
                if ($loggable instanceof PHPError) {
                    $msg   = strRightFrom($msg, ':');
                    $type .= 'PHP Error:';
                }
            }
            $html     .= '<span style="white-space:nowrap"><span style="font-weight:bold">['.strtoupper(self::$logLevels[$level]).']</span> <span style="white-space:pre; line-height:8px">'.nl2br(hsc($type.$msg)).'</span></span><br><br>';
            $html     .= 'in <span style="font-weight:bold">'.$file.'</span> on line <span style="font-weight:bold">'.$line.'</span><br>';
            $traceStr  = $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $traceStr .= DebugHelper::getBetterTraceAsString($loggable, $indent);
            $html     .= '<span style="clear:both"></span><br>'.printPretty($traceStr, true).'<br>';
        }

        // append an existing context exception
        if (\key_exists('exception', $context)) {
            $exception = $context['exception'];
            $msg       = DebugHelper::composeBetterMessage($exception);
            $html     .= '<br>'.nl2br(hsc($msg)).'<br><br>';
            $traceStr  = $indent.'Stacktrace:'.NL.$indent.'-----------'.NL;
            $traceStr .= DebugHelper::getBetterTraceAsString($exception, $indent);
            $html     .= printPretty($traceStr, true);
        }

        // append the current HTTP request
        if (!CLI) {
            $html .= '<br style="clear:both"><br>'.printPretty('Request:'.NL.'--------'.NL.Request::instance(), true).'<br>';
        }

        // close the HTML tag and add some JavaScript to ensure it becomes visible                      // id = md5('ministruts')
        $html .= '</div>
                  <script>
                      var bodies = document.getElementsByTagName("body");
                      if (bodies && bodies.length)
                         bodies[0].appendChild(document.getElementById("99a05cf355861c76747b7176c778eed2'.self::$printCounter.'"));
                  </script>';
        // store the HTML tag
        $context['htmlMessage'] = $html;
    }


    /**
     * Resolve the location the logger was called from and store it in the log context under the keys "file" and "line".
     *
     * @param  array $context - reference to the log context
     *
     * @return void
     */
    private static function resolveLogLocation(array &$context) {
        if (!\key_exists('trace', $context))
            self::generateStackTrace($context);
        $trace = $context['trace'];

        foreach ($trace as $frame) {                        // find the first frame with "file"
            if (isset($frame['file'])) {                    // skip internal PHP functions
                $context['file'] = $frame['file'];
                $context['line'] = $frame['line'];
                break;
            }
        }

        if (!\key_exists('file', $context)) {
            $context['file'] = '(unknown)';
            $context['line'] = '(?)';
        }
    }


    /**
     * Resolve the class the logger was called from and store it in the log context under the key "class".
     *
     * @param  array $context - reference to the log context
     *
     * @return void
     *
     * @todo   test with Closure and internal PHP functions
     */
    private static function resolveLogCaller(array &$context) {
        if (!\key_exists('trace', $context))
            self::generateStackTrace($context);
        $trace = $context['trace'];

        $context['class'] = isset($trace[0]['class']) ? $trace[0]['class'] : '';
    }


    /**
     * Generate an internal stacktrace and store it in the log context under the key "trace".
     *
     * @param  array $context - reference to the log context
     *
     * @return void
     */
    private static function generateStackTrace(array &$context) {
        if (!\key_exists('trace', $context)) {
            $trace = DebugHelper::fixTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), __FILE__, __LINE__);

            foreach ($trace as $i => $frame) {
                if (!isset($frame['class']) || $frame['class']!=__CLASS__)      // remove non-logger frames
                    break;
                unset($trace[$i]);
            }
            $context['trace'] = \array_values($trace);
        }
    }
}
