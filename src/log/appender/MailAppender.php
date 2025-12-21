<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\appender;

use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\log\Logger;
use rosasurfer\ministruts\log\LogMessage;
use rosasurfer\ministruts\log\detail\Request;
use rosasurfer\ministruts\net\mail\Mailer;

use function rosasurfer\ministruts\realpath;
use function rosasurfer\ministruts\simpleClassName;
use function rosasurfer\ministruts\strLeftTo;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\L_FATAL;
use const rosasurfer\ministruts\NL;

/**
 * MailAppender
 *
 * A log appender which sends email notifications for qualifying log messages. The appender is configured via config key
 * "log.appender.mail". All configuration options except "receiver" are optional. For defaults see the getDefault*() methods.
 * For mailer options see {@link \rosasurfer\ministruts\net\mail\Mailer::create()}.
 *
 * @example
 * <pre>
 *  $config = $this->di('config');
 *  $options = $config['log.appender.mail'];
 *  $appender = new MailAppender($options);
 *
 *  Option fields:
 *  --------------
 *  'enabled'               = (bool)            // whether the appender is enabled (default: no)
 *  'loglevel'              = (int|string)      // appender loglevel (default: application loglevel)
 *  'details.trace'         = (bool)            // whether a stacktrace is attached to log messages (default: yes)
 *  'details.request'       = (bool)            // whether HTTP request details are attached to log messages (default: yes)
 *  'details.session'       = (bool)            // whether HTTP session details are attached to log messages (default: yes)
 *  'details.server'        = (bool)            // whether server details are attached to log messages (default: no)
 *  'filter'                = {classname}       // content filter to apply to the resulting output (default: none)
 *  'aggregate-messages'    = (bool)            // whether to group messages per HTTP request/CLI call (default: see self::getDefaultAggregation())
 *  'sender'                = {email-address}   // sender address (default: .ini setting "sendmail_from")
 *  'receivers'             = {email-address}   // one or more receiver addresses separated by comma "," (required)
 *  'headers'               = string[]          // optional array of additional MIME headers, e.g. "Cc: user@domain.tld" (default: none)
 *  'options'               = []                // optional mailer options, see {@link \rosasurfer\ministruts\net\mail\Mailer::create()}
 * </pre>
 */
class MailAppender extends BaseAppender {

    /** @var ?string - mail sender */
    protected ?string $sender = null;

    /** @var string[] - mail receivers */
    protected array $receivers = [];

    /** @var string[] - additional mail headers (if any) */
    protected array $headers = [];

    /** @var bool - whether to aggregate multiple messages per request/process */
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

        // sender (optional)
        $sender = $options['sender'] ?? null;
        Assert::nullOrString($sender, '$options[sender]');
        $this->sender = $sender;

        // receivers (required)
        $receivers = $options['receivers'] ?? null;
        Assert::notNull($receivers, 'Missing parameter $options[receivers]: (empty)');
        if (is_string($receivers)) {
            $receivers = explode(',', $receivers);
        }
        Assert::isArray($receivers, 'Invalid type of parameter \$options[receivers]: ('.gettype($options['receivers']).')');
        foreach ($receivers as $i => $receiver) {
            $receiver = trim($receiver);
            if ($receiver == '') {
                unset($receivers[$i]);
            }
        }
        if (!$receivers) throw new InvalidValueException('Invalid parameter $options[receivers]: (empty)');
        $this->receivers = $receivers;

        // additional headers (optional)
        $headers = $options['headers'] ?? [];
        Assert::isArray($headers, '$options[headers]');
        $this->headers = $headers;

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
     * Send an email notification for the passed log message.
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

        // if aggregation is enabled collect messages only
        $this->messages[] = $message;
        if ($this->aggregateMessages) {
            return true;
        }

        // otherwise process all collected messages
        $messages = $this->messages;
        $this->messages = [];
        return $this->processMessages($messages);
    }


    /**
     * Process collected log messages.
     *
     * @param  LogMessage[] $logMessages
     *
     * @return bool - Whether logging should continue with the next registered appender. Returning FALSE interrupts the chain.
     */
    protected function processMessages(array $logMessages): bool {
        if (!$logMessages) return true;
        $location = $subject = $msg = '';

        foreach ($logMessages as $i => $logMessage) {
            if ($i == 0) {
                $exception = $logMessage->getException();
                $logLevel  = $logMessage->getLogLevel();

                if ($exception) {
                    $subjectMsg = simpleClassName($exception);
                    if ($logMessage->fromErrorHandler()) {
                        $subjectMsg = $logLevel == L_FATAL ? "Unhandled $subjectMsg" : (string)strtok($exception->getMessage(), NL);
                    }
                    $location = CLI ? 'in '.realpath($_SERVER['PHP_SELF']) : 'at '.strLeftTo(Request::getUrl($this->filter), '?');
                }
                else {
                    $subjectMsg = (string)strtok(ltrim($logMessage->getMessage()), NL);
                    $location = CLI ? 'in '.realpath($_SERVER['PHP_SELF']) : '';
                }

                $logLevelDescr = strtoupper(Logger::logLevelDescription($logLevel));
                $subject       = "PHP [$logLevelDescr] $subjectMsg $location";
            }
            else {
                $msg .= NL.NL.' followed by'.NL;
            }
            $msg .= $logMessage->getMessageDetails(false, $this->filter);
            if ($this->traceDetails && $detail = $logMessage->getTraceDetails(false, $this->filter)) {
                $msg .= NL.$detail;
            }
        }
        if (sizeof($logMessages) > 1) $msg .= NL;

        if ($this->requestDetails && $detail = $logMessage->getRequestDetails(false, $this->filter)) $msg .= NL.$detail;
        if ($this->sessionDetails && $detail = $logMessage->getSessionDetails(false, $this->filter)) $msg .= NL.$detail;
        if ($this->serverDetails  && $detail = $logMessage->getServerDetails (false, $this->filter)) $msg .= NL.$detail;

        $msg .= NL.$logMessage->getCallDetails(false);
        $msg = trim($msg);

        // create a static mailer instance
        static $mailer = null;
        $options = $this->options['options'] ?? [];
        Assert::isArray($options, 'config "log.appender.mail.options"');
        $mailer ??= Mailer::create($options);

        // send a separate email to each receiver (use header "Cc: receiver2, receiver3..." to send a single mail to all receivers
        foreach ($this->receivers as $receiver) {
            $mailer->sendMail($this->sender, $receiver, $subject, $msg, $this->headers);
        }
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
