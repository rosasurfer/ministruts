<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log;

use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\log\appender\AppenderInterface as LogAppender;
use rosasurfer\ministruts\log\appender\ErrorLogAppender;
use rosasurfer\ministruts\log\appender\MailAppender;
use rosasurfer\ministruts\log\appender\PrintAppender;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\core\exception\InvalidValueException;

use const rosasurfer\ministruts\L_DEBUG;
use const rosasurfer\ministruts\L_ERROR;
use const rosasurfer\ministruts\L_FATAL;
use const rosasurfer\ministruts\L_INFO;
use const rosasurfer\ministruts\L_NOTICE;
use const rosasurfer\ministruts\L_WARN;


/**
 * Logger
 *
 * Passes a message through a chain of log appenders. Each appender can be configured separately.
 *
 *  - PrintAppender:    Prints log messages to STDOUT/STDERR (CLI) or displays them as part of the HTTP response (web interface).
 *                      In a web context active only for requests from localhost or if the PHP setting "display_errors" is explicitly
 *                      enabled (not recommended for production). Custom configuration via config node "log.appender.print".
 *
 *  - ErrorLogAppender: Passes log messages to the PHP system logger as configured by the PHP setting "error_log". Custom configuration
 *                      via config node "log.appender.errorlog".
 *
 *  - MailAppender:     Sends email notifications for qualifying log messages. Custom configuration via config node "log.appender.mail".
 *
 * Loglevels can be configured for the whole application and per class. The default loglevel is L_NOTICE.
 *
 * @example
 * <pre>
 *  $config = $this->di('config');
 *  $config['log.level.default']              = L_WARN;     // set the default application loglevel to L_WARN
 *  $config['log.level.class.ClassA']         = L_DEBUG;    // set loglevel for class "ClassA" to L_DEBUG
 *  $config['log.level.class.foo\bar\ClassB'] = L_ERROR;    // set loglevel for class "foo\bar\ClassB" to L_ERROR
 * </pre>
 *
 *
 * TODO: Logger::resolveCaller() - test with Closure and internal PHP functions
 * TODO: implement \Psr\Log\LoggerInterface and remove static crap
 * TODO: support full email address format as in "Joe Blow <address@domain.tld>"
 */
class Logger extends StaticClass {

    /** @var int - default if no application loglevel is configured */
    const DEFAULT_LOGLEVEL = L_NOTICE;


    /** @var string[] - loglevels and their string representations */
    private static array $logLevels = [
        L_DEBUG  => 'Debug' ,
        L_INFO   => 'Info'  ,
        L_NOTICE => 'Notice',
        L_WARN   => 'Warn'  ,
        L_ERROR  => 'Error' ,
        L_FATAL  => 'Fatal' ,
    ];

    /** @var int - configured application loglevel */
    private static $appLogLevel;

    /** @var ?LogAppender - print appender if enabled */
    private static ?LogAppender $printAppender = null;

    /** @var ?LogAppender - errorlog appender if enabled */
    private static ?LogAppender $errorLogAppender = null;

    /** @var ?LogAppender - mail appender if enabled */
    private static ?LogAppender $mailAppender = null;

    /** @var string[] - config ids of enabled appenders */
    private static array $enabledAppenders = [];


    /**
     * Initialize the Logger.
     *
     * @return void
     */
    private static function init(): void {
        static $initialized = false;
        if ($initialized) return;

        // read the configured application loglevel
        /** @var Config $config */
        $config = self::di('config');
        $value = $config['log.level.default'] ?? '';
        $logLevel = 0;

        if (is_string($value))   $logLevel = self::strToLogLevel($value);
        else if (is_int($value)) $logLevel = self::isLogLevel($value) ? $value : 0;
        if (!$logLevel)          $logLevel = self::DEFAULT_LOGLEVEL;
        self::$appLogLevel = $logLevel;

        // initialize configured log appenders
        if (self::isAppenderEnabled($id = 'print')) {
            self::$printAppender = new PrintAppender($config["log.appender.$id"]);
            self::$enabledAppenders[] = $id;
        }
        if (self::isAppenderEnabled($id = 'errorlog')) {
            self::$errorLogAppender = new ErrorLogAppender($config["log.appender.$id"]);
            self::$enabledAppenders[] = $id;
        }
        if (self::isAppenderEnabled($id = 'mail')) {
            self::$mailAppender = new MailAppender($config["log.appender.$id"]);
            self::$enabledAppenders[] = $id;
        }

        $initialized = true;
    }


    /**
     * Whether a log appender is enabled.
     *
     * @param  string $id - appender identifier
     *
     * @return bool
     */
    private static function isAppenderEnabled(string $id): bool {
        /** @var ?Config $config */
        static $config = null;
        if (!$config) $config = self::di('config');

        return $config->getBool("log.appender.$id.enabled", false);
    }


    /**
     * Log a message or a stringable object.
     *
     * @param  string|object        $loggable           - a string or an object implementing <tt>__toString()</tt>
     * @param  int                  $level              - loglevel
     * @param  array<string, mixed> $context [optional] - logging context with additional data (default: none)
     *
     * @return void
     */
    public static function log($loggable, int $level, array $context = []) {
        // lock the method and prevent recursive calls
        static $isActive = false;
        if ($isActive) throw new IllegalStateException('Recursive call detected, aborting...');
        $isActive = true;

        self::init();
        if (!isset(self::$logLevels[$level])) throw new InvalidValueException("Invalid parameter \$level: $level (not a loglevel)");

        // filter messages below the active loglevel
        if ($level >= self::$appLogLevel) {
            $message = new LogMessage($loggable, $level, $context);

            /** @var bool continue */
            $continue = true;
            $context += array_flip(self::$enabledAppenders);        // let appenders know the status of each other (not a good design)
            if ($continue && self::$printAppender)    $continue = self::$printAppender->appendMessage($message);
            if ($continue && self::$errorLogAppender) $continue = self::$errorLogAppender->appendMessage($message);
            if ($continue && self::$mailAppender)     $continue = self::$mailAppender->appendMessage($message);
        }

        // unlock the method
        $isActive = false;
    }


    /**
     * Whether an integer value matches a loglevel constant.
     *
     * @param  int $value
     *
     * @return bool
     */
    public static function isLogLevel(int $value): bool {
        return isset(self::$logLevels[$value]);
    }


    /**
     * Return the description of a loglevel constant.
     *
     * @param  int $level - loglevel
     *
     * @return string - loglevel description or an empty string if $level is not a valid loglevel
     */
    public static function logLevelDescription(int $level): string {
        return self::$logLevels[$level] ?? '';
    }


    /**
     * Translate a loglevel description to a loglevel constant.
     *
     * @param  string $value - loglevel description
     *
     * @return int - loglevel constant or 0 (zero) if the string is not a valid loglevel description
     */
    public static function strToLogLevel(string $value): int {
        $flipped = array_flip(self::$logLevels);
        $logLevel = ucfirst(strtolower($value));
        return $flipped[$logLevel] ?? 0;
    }
}
