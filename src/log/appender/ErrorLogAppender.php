<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\appender;

use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\log\LogMessage;
use rosasurfer\ministruts\log\detail\Request;

use function rosasurfer\ministruts\isRelativePath;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\ERROR_LOG_DEFAULT;
use const rosasurfer\ministruts\ERROR_LOG_FILE;
use const rosasurfer\ministruts\ERROR_LOG_SAPI;
use const rosasurfer\ministruts\NL;
use const rosasurfer\ministruts\WINDOWS;

/**
 * ErrorLogAppender
 *
 * A log appender passing log messages via "error_log()" to the PHP system logger. The appender is configured via config
 * key "log.appender.errorlog". All configuration options are optional. For defaults see the getDefault*() methods.
 *
 * @example
 * <pre>
 *  $config = $this->di('config');
 *  $options = $config['log.appender.errorlog'];
 *  $appender = new ErrorLogAppender($options);
 *
 *  Option fields:
 *  --------------
 *  'enabled'            = (bool)        // whether the appender is enabled (default: yes)
 *  'loglevel'           = (int|string)  // appender loglevel (default: application loglevel)
 *  'details.trace'      = (bool)        // whether a stacktrace is attached to log messages (default: yes)
 *  'details.request'    = (bool)        // whether HTTP request details are attached to log messages from the web interface (default: yes)
 *  'details.session'    = (bool)        // whether HTTP session details are attached to log messages from the web interface (default: yes)
 *  'details.server'     = (bool)        // whether server details are attached to log messages from the CLI interface (default: no)
 *  'filter'             = {classname}   // content filter to apply to the resulting output (default: none)
 *  'aggregate-messages' = (bool)        // whether to group messages per HTTP request/CLI call (default: see self::getDefaultAggregation())
 *  'filepath'           = {filename}    // log filename; absolute or relative to $config['app.dir.root'] (default: php.ini setting "error_log")
 * </pre>
 */
class ErrorLogAppender extends BaseAppender {

    /** @var int - log destination type */
    protected int $destinationType = ERROR_LOG_DEFAULT;

    /** @var string - log destination filename */
    protected string $destinationFile;

    /** @var bool - whether to aggregate multiple messages per request or process */
    protected bool $aggregateMessages;

    /** @var LogMessage[] - collected messages */
    protected array $messages = [];


    /**
     * Constructor
     *
     * Create and initialize a new instance.
     *
     * @param  mixed[] $options - configuration options
     */
    public function __construct(array $options) {
        parent::__construct($options);

        /** @var ?string $filepath */
        $filepath = $options['filepath'] ?? null;

        if (!is_string($filepath) || empty($filepath)) {
            $this->destinationType = ERROR_LOG_DEFAULT;
            $this->destinationFile = '';
        }
        else {
            $this->destinationType = ERROR_LOG_FILE;
            if (isRelativePath($filepath)) {
                // convert to absolute path as the current PHP working directory is not fix
                /** @var Config $config */
                $config = $this->di('config');
                $filepath = $config['app.dir.root']."/$filepath";
            }
            $this->destinationFile = $filepath;
        }

        // initialize message aggregation
        $this->aggregateMessages = filter_var($options['aggregate-messages'] ?? static::getDefaultAggregation(), FILTER_VALIDATE_BOOLEAN);
        if ($this->aggregateMessages) {
            register_shutdown_function(function(): void {
                // add a nested handler to include log entries triggered during shutdown itself
                register_shutdown_function(function(): void {
                    $messages = $this->messages;
                    $this->messages = [];
                    $this->processMessages($messages);
                });
            });
        }
    }


    /**
     * Pass on a log message to the PHP system logger.
     *
     * @param  LogMessage $message
     *
     * @return bool - Whether logging should continue with the next registered appender. Returning FALSE interrupts the chain.
     */
    public function appendMessage(LogMessage $message): bool {
        // filter messages below the active loglevel
        if ($message->getLogLevel() < $this->logLevel) {
            return true;
        }

        // prevent duplicated output on STDERR if the print appender is active
        if ($this->toSTDERR()) {
            if ($this->options['print.enabled'] ?? false) {
                return true;
            }
        }

        // if aggregation is enabled only collect messages
        $this->messages[] = $message;
        if ($this->aggregateMessages) {
            return true;
        }

        // process/reset collected messages
        $messages = $this->messages;
        $this->messages = [];
        return $this->processMessages($messages);
    }


    /**
     * Process the passed log messages.
     *
     * @param  LogMessage[] $messages
     *
     * @return bool - Whether logging should continue with the next registered appender. Returning FALSE interrupts the chain.
     */
    protected function processMessages(array $messages): bool {
        if (!$messages) return true;
        $msg = '';

        foreach ($messages as $i => $message) {
            if ($i > 0) {
                $msg .= NL.NL.' followed by'.NL;
            }
            $msg .= $message->getMessageDetails(false, $this->filter);

            if ($this->traceDetails && $detail = $message->getTraceDetails(false, $this->filter)) {
                $msg .= NL.$detail;
            }
        }
        if ($this->requestDetails && $detail = $message->getRequestDetails(false, $this->filter)) $msg .= NL.$detail;
        if ($this->sessionDetails && $detail = $message->getSessionDetails(false, $this->filter)) $msg .= NL.$detail;
        if ($this->serverDetails  && $detail = $message->getServerDetails (false, $this->filter)) $msg .= NL.$detail;

        $msg .= NL.$message->getCallDetails(false, false);
        $msg = trim($msg).NL;

        if (!$this->toSTDERR()) {
            $msg  = (CLI ? 'CLI' : Request::getRemoteIP()).' '.$msg;
            $msg .= NL.str_repeat('-', 150);
        }

        $msg = str_replace(chr(0), '\0', $msg);                 // replace NUL bytes which mess up the logfile
        if (WINDOWS) $msg = str_replace(NL, PHP_EOL, $msg);     // prevent a mixed EOL file format on Windows

        if ($this->destinationType == ERROR_LOG_DEFAULT) {
            error_log($msg, $this->destinationType);
        }
        elseif ($this->destinationType == ERROR_LOG_FILE) {
            error_log(date('[d-M-Y H:i:s T] ').$msg.PHP_EOL, $this->destinationType, $this->destinationFile);
        }

        // with ERROR_LOG_DEFAULT ini_get("error_log") controls whether a message is sent to syslog, a file or the SAPI logger
        // -------------------------------------------------------------------------------------------------------------------
        // (empty):      Errors are sent to the SAPI logger, e.g. the Appache error log or STDERR in CLI mode.
        // "syslog":     Errors are sent to the system logger. On Unix this is syslog(3), and on Windows it's the event log.
        // "<filepath>": Name of the file where errors should be logged.
        return true;
    }


    /**
     * Whether the appender logs to STDERR. Used to prevent duplicate screen output from both this and the display appender.
     *
     * @return bool
     */
    protected function toSTDERR(): bool {
        if (CLI) {
            $iniSetting = !empty(ini_get('error_log'));
            return ($this->destinationType == ERROR_LOG_DEFAULT && !$iniSetting) || $this->destinationType == ERROR_LOG_SAPI;
        }
        return false;
    }


    /**
     * Return the default "enabled" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultEnabled(): bool {
        return true;
    }


    /**
     * Return the default "details.request" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultRequestDetails(): bool {
        return true;
    }


    /**
     * Return the default "details.session" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultSessionDetails(): bool {
        return true;
    }
}
