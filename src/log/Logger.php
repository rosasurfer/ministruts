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
 *  $config['log.level.default']              = L_WARN;         // set the application's default loglevel to L_WARN
 *  $config['log.level.class.ClassA']         = L_DEBUG;        // set the loglevel for class "ClassA" to L_DEBUG
 *  $config['log.level.class.foo\bar\ClassB'] = L_ERROR;        // set the loglevel for class "foo\bar\ClassB" to L_ERROR
 * </pre>
 *
 *
 * @todo: implement \Psr\Log\LoggerInterface and remove static crap
 */
class Logger extends StaticClass {

    /** default if no application loglevel is configured */
    public const DEFAULT_LOGLEVEL = L_NOTICE;


    /** @var string[] - loglevels and their string representations */
    private static array $logLevels = [
        L_DEBUG  => 'Debug' ,
        L_INFO   => 'Info'  ,
        L_NOTICE => 'Notice',
        L_WARN   => 'Warn'  ,
        L_ERROR  => 'Error' ,
        L_FATAL  => 'Fatal' ,
    ];

    /** @var int - the application's default loglevel */
    private static int $appLogLevel;

    /** @var LogAppender[] - enabled appenders and their ids */
    private static array $logAppenders = [];


    /**
     * Initialize the Logger and instantiate enabled appenders.
     *
     * @return void
     */
    private static function init(): void {
        static $initialized = false;
        if (!$initialized) {
            // read the configured application loglevel
            /** @var Config $config */
            $config = self::di('config');
            $value = $config['log.level.default'] ?? '';
            $logLevel = 0;

            if (is_string($value))  $logLevel = self::strToLogLevel($value);
            elseif (is_int($value)) $logLevel = self::isLogLevel($value) ? $value : 0;
            if (!$logLevel)         $logLevel = self::DEFAULT_LOGLEVEL;
            self::$appLogLevel = $logLevel;

            // initialize log appenders
            if (self::isAppenderEnabled($id = 'print')) {
                self::$logAppenders[$id] = new PrintAppender($config["log.appender.$id"] ?? []);
            }
            if (self::isAppenderEnabled($id = 'errorlog')) {
                // the ErrorLogAppender needs to know the status of the PrintAppender
                $options = $config["log.appender.$id"] ?? [];
                $options += ['print.enabled' => isset(self::$logAppenders['print'])];
                self::$logAppenders[$id] = new ErrorLogAppender($options);
            }
            if (self::isAppenderEnabled($id = 'mail')) {
                self::$logAppenders[$id] = new MailAppender($config["log.appender.$id"] ?? []);
            }
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
        static $config = null;
        if (!$config) {
            /** @var Config $config */
            $config = self::di('config');
        }

        switch ($id) {
            case 'print':    return $config->getBool("log.appender.$id.enabled", true, PrintAppender::getDefaultEnabled());
            case 'errorlog': return $config->getBool("log.appender.$id.enabled", true, ErrorLogAppender::getDefaultEnabled());
            case 'mail':     return $config->getBool("log.appender.$id.enabled", true, MailAppender::getDefaultEnabled());
        }
        return $config->getBool("log.appender.$id.enabled", true);
    }


    /**
     * Log a message or a stringable object.
     *
     * @param  string|object        $loggable           - a string or an object implementing <tt>__toString()</tt>
     * @param  int                  $level              - loglevel
     * @param  array<string, mixed> $context [optional] - logging context with additional data (default: none)
     *
     * @return bool - success status
     */
    public static function log($loggable, int $level, array $context = []): bool {
        // prevent recursive calls
        static $recursion = false;
        if ($recursion) throw new IllegalStateException('Recursive call detected, aborting...');
        $recursion = true;

        self::init();
        if (!isset(self::$logLevels[$level])) throw new InvalidValueException("Invalid parameter \$level: $level (not a loglevel)");

        // filter messages below the active loglevel
        if ($level >= self::$appLogLevel) {
            $context = array_intersect_key($context, array_flip(['error-handler', 'exception', 'file', 'line']));
            $message = new LogMessage($loggable, $level, $context);

            foreach (self::$logAppenders as $appender) {
                if (!$appender->appendMessage($message)) {
                    break;
                }
            }
        }

        $recursion = false;
        return true;
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
