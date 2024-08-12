<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\appender;

use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\InvalidTypeException;
use rosasurfer\ministruts\log\Logger;
use rosasurfer\ministruts\log\LogMessage;
use rosasurfer\ministruts\log\detail\Request;

use function rosasurfer\ministruts\normalizeEOL;
use function rosasurfer\ministruts\realpath;
use function rosasurfer\ministruts\strContains;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strRightFrom;
use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\EOL_UNIX;
use const rosasurfer\ministruts\EOL_WINDOWS;
use const rosasurfer\ministruts\L_DEBUG;
use const rosasurfer\ministruts\L_ERROR;
use const rosasurfer\ministruts\L_FATAL;
use const rosasurfer\ministruts\L_INFO;
use const rosasurfer\ministruts\L_NOTICE;
use const rosasurfer\ministruts\L_WARN;
use const rosasurfer\ministruts\NL;
use const rosasurfer\ministruts\WINDOWS;


/**
 * MailAppender
 *
 * A log appender which sends email notifications for qualifying log messages. The appender is configured via config key
 * "log.appender.mail". All configuration options except "receiver" are optional. For defaults see the getDefault*() methods.
 *
 * @example
 * <pre>
 *  $config = $this->di('config');
 *  $options = $config['log.appender.mail'];
 *  $appender = new MailAppender($options);
 *
 *  Option fields:
 *  --------------
 *  'enabled'            = (bool)           // whether the appender is enabled (default: no)
 *  'loglevel'           = (int|string)     // appender loglevel (default: application loglevel)
 *  'details.trace'      = (bool)           // whether a stacktrace is attached to log messages (default: yes)
 *  'details.request'    = (bool)           // whether HTTP request details are attached to log messages from the web interface (default: yes)
 *  'details.session'    = (bool)           // whether HTTP session details are attached to log messages from the web interface (default: yes)
 *  'details.server'     = (bool)           // whether server details are attached to log messages from the CLI interface (default: no)
 *  'filter'             = {classname}      // content filter to apply to the resulting output (default: none)
 *  'aggregate-messages' = (bool)           // whether to group messages per HTTP request/CLI call (default: see self::getDefaultAggregation())
 *  'sender'             = {email-address}  // sender address of log messages (default: php.ini setting "sendmail_from")
 *  'receiver'           = {email-address}  // required: one or more receiver addresses separated by comma ","
 *  'headers'            = string[]         // zero or more additional MIME headers, e.g. "CC: user@domain.tld" (default: none)
 * </pre>
 */
class MailAppender extends BaseAppender {

    /** @var string - mail sender */
    protected string $sender;

    /** @var string[] - one or more mail receivers */
    protected array $receivers = [];

    /** @var string[] - additional mail headers, if any */
    protected array $headers = [];

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

        // sender (optional)
        $sender = $options['sender'] ?? null;
        if (is_string($sender)) {
            $sender = $this->parseAddress($sender);
            if (!$sender) throw new InvalidValueException("Invalid parameter \$options[sender]: \"$sender\"");
        }
        if (!is_null($sender)) {
            throw new InvalidTypeException('Invalid type of parameter $options[sender]: ('.gettype($sender).')');
        }
        else {
            $sender = ini_get('sendmail_from');
            if (!is_string($sender) || !filter_var($sender, FILTER_VALIDATE_EMAIL)) {
                $hostName = php_uname('n');                                     // compose synthetic sender
                if (!$hostName)                   $hostName  = 'localhost';
                if (!strContains($hostName, '.')) $hostName .= '.localdomain';  // RFC 2821: hostname must contain more than one part
                $sender = strtolower(get_current_user()."@$hostName");
            }
        }
        $this->sender = $sender;

        // receiver (required)
        $receivers = $options['receiver'] ?? [];
        if (!is_array($receivers)) {
            $receivers = [$receivers];
        }
        if (!$receivers) throw new InvalidValueException('Missing parameter $options[receiver]: (empty)');

        foreach ($receivers as $i => $value) {
            if (!is_string($value)) throw new InvalidTypeException("Invalid type of parameter \$options[receiver][$i]: (".gettype($value).")");
            foreach (explode(',', $value) as $segment) {
                $segment = trim($segment);
                if (strlen($segment)) {
                    $receiver = $this->parseAddress($segment);
                    if (!$receiver) throw new InvalidValueException("Invalid parameter \$options[receiver][$i]: \"$value\"");
                    $this->receivers[] = $receiver;
                }
            }
        }
        if (!$this->receivers) throw new InvalidValueException('Missing parameter $options[receiver]: (empty)');

        // additional MIME headers (optional)
        $headers = $options['headers'] ?? [];
        if (!is_array($headers)) throw new InvalidTypeException('Invalid type of parameter $options[headers]: '.gettype($headers).' (array expected)');

        foreach ($headers as $i => $value) {
            if (!is_string($value)) throw new InvalidTypeException("Invalid type of parameter \$options[headers][$i]: (".gettype($value).")");
            if (!preg_match('/^[a-z]+(-[a-z]+)*:/i', $value)) throw new InvalidValueException("Invalid parameter \$options[headers][$i]: \"$value\"");
            $this->headers[] = $value;
        }

        // initialize message aggregation
        $this->aggregateMessages = filter_var($options['aggregate-messages'] ?? static::getDefaultAggregation(), FILTER_VALIDATE_BOOLEAN);
        if ($this->aggregateMessages) {
            register_shutdown_function(function() {
                // add a nested handler to include log entries triggered during shutdown itself
                register_shutdown_function(function() {
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
        // skip on missing receivers
        if (!$this->receivers) {
            return true;
        }

        // filter messages below the active loglevel
        if ($message->getLogLevel() < $this->logLevel) {
            return true;
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
        $location = $subject = $msg = '';

        foreach ($messages as $i => $message) {
            if ($i == 0) {
                $logLevel = $message->getLogLevel();
                $type = 'Exception';

                if ($message->isSentByErrorHandler() || !$message->getException()) {
                    switch ($logLevel) {
                        case L_DEBUG:
                        case L_INFO:
                        case L_NOTICE:
                        case L_WARN:  $type = 'message'; break;
                        case L_ERROR: $type = 'error';   break;
                        case L_FATAL:
                            $type = $message->isSentByErrorHandler() ? 'Unhandled exception' : 'error';
                            break;
                    }
                }
                $location = CLI ? realpath($_SERVER['PHP_SELF']) : strLeftTo(Request::getUrl($this->filter), '?');
                $subject  = 'PHP ['.strtoupper(Logger::logLevelDescription($logLevel))."] $type".(CLI ? ' in':' at')." $location";
            }
            else {
                $msg .= NL.NL.' followed by'.NL;
            }
            $msg .= $message->getMessageDetails(false, $this->filter);
            if ($this->traceDetails && $detail = $message->getTraceDetails(false, $this->filter)) {
                $msg .= NL.$detail;
            }
        }
        if (sizeof($messages) > 1) $msg .= NL;

        if ($this->requestDetails && $detail = $message->getRequestDetails(false, $this->filter)) $msg .= NL.$detail;
        if ($this->sessionDetails && $detail = $message->getSessionDetails(false, $this->filter)) $msg .= NL.$detail;
        if ($this->serverDetails  && $detail = $message->getServerDetails (false, $this->filter)) $msg .= NL.$detail;

        $msg .= NL.$message->getCallDetails(false);
        $msg = trim($msg);

        foreach ($this->receivers as $receiver) {
            $this->sendMail($receiver, $subject, $msg);
        }
        return true;
    }


    /**
     * Send an email.
     *
     * @param  string $receiver - mail receiver
     * @param  string $subject  - mail subject
     * @param  string $message  - mail body
     *
     * @return void
     */
    protected function sendMail(string $receiver, string $subject, string $message): void {
        $headers = $this->headers;

        // Return-Path: (invisible sender)
        $returnPath = $this->sender;
        $value = $this->removeHeader($headers, 'Return-Path');
        if (is_string($value)) {
            $returnPath = $this->parseAddress($value);
            if (!$returnPath) throw new InvalidValueException("Invalid header \"Return-Path: $value\"");
        }

        // From: (visible sender)
        $from = $this->sender;
        $value = $this->removeHeader($headers, 'From');
        if (is_string($value)) {
            $from = $this->parseAddress($value);
            if (!$from) throw new InvalidValueException("Invalid header \"From: $value\"");
        }

        // RCPT: (receiving mailbox)
        $rcpt = $receiver;

        // To: (visible receiver)
        $to = $receiver;
        $this->removeHeader($headers, 'To');

        // Subject:
        $subject = $this->encodeNonAsciiChars(trim($subject));

        // remaining headers
        foreach ($headers as &$header) {
            $pattern = '/^([a-z]+(?:-[a-z]+)*): *(.*)/i';
            $match = null;
            if (!preg_match($pattern, $header, $match)) throw new InvalidValueException("Invalid header \"$header\"");
            $name   = $match[1];
            $value  = $this->encodeNonAsciiChars(trim($match[2]));
            $header = "$name: $value";
        };
        unset($header);

        $headers[] = 'X-Mailer: Microsoft Office Outlook 11';           // save us from Hotmail junk folder
        $headers[] = 'X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.2180';
        $headers[] = 'Content-Type: text/plain; charset=utf-8';         // ASCII is a subset of UTF-8
        $headers[] = "From: <$from>";
        if ($to != $rcpt) {                                             // Linux-PHP always sets the "To:" header (to RCPT) and doesn't check for a custom header
            $headers[] = "To: <$to>";                                   // Windows-PHP sets the "To:" header (to RCPT) only if no custom header is given
        }
        $headers = join(EOL_WINDOWS, $headers);

        // mail body
        $message = str_replace(chr(0), '\0', $message);                 // replace NUL bytes which destroy the mail
        $message = $this->normalizeLines($message);

        // send mail
        $prevSendmailFrom = ini_get('sendmail_from');
        if (WINDOWS) ini_set('sendmail_from', $returnPath);
        try {
            mail("<$receiver>", $subject, $message, $headers, "-f $returnPath");
        }
        finally {
            if (WINDOWS) ini_set('sendmail_from', $prevSendmailFrom);
        }
    }


    /**
     * MIME-encode a string with UTF-8 if it contains non-ASCII characters.
     *
     * @param  string $value - value to encode
     *
     * @return string - encoded value
     *
     * @see    https://tools.ietf.org/html/rfc1522
     */
    protected function encodeNonAsciiChars(string $value): string {
        if (preg_match('/[\x80-\xFF]/', $value)) {
            $words = str_split($value, 45);
            foreach ($words as $i => $word) {
                $words[$i] = '=?utf-8?B?'.base64_encode($word).'?=';
            }
            $value = join(EOL_WINDOWS."\t", $words);
        }
        return $value;

        // https://tools.ietf.org/html/rfc1522
        // -----------------------------------
        // An encoded-word may not be more than 75 characters long, including charset, encoding, encoded-text, and delimiters.
        // If it is desirable to encode more text than will fit in an encoded-word of 75 characters, multiple encoded-words
        // (separated by CRLF WSP) may be used.  Message header lines that contain one or more encoded words should be no more
        // than 76 characters long.
        //
        // While there is no limit to the length of a multiple-line header field, each line of a header field that contains one
        // or more encoded-words is limited to 76 characters.

        // max(encoded-word)=75  =>  max(base64)=63
        // base64 multiple of 4:     max(base64)=60
        // original value:           max 45 chars                   @see https://en.wikipedia.org/wiki/Base64#Output_padding
        // split $value into chunks of 45 chars
    }


    /**
     * Wrap long lines and ensure RFC-compliant line endings.
     *
     * @param  string $value
     *
     * @return string
     *
     * @see    https://www.rfc-editor.org/rfc/rfc2822#section-2.1.1
     */
    protected function normalizeLines(string $value): string {
        $limit = 980;                                               // per RFC max 998 chars but e.g. FastMail only accepts 990
        $lines = explode(EOL_UNIX, normalizeEOL($value, EOL_UNIX));

        $results = [];
        foreach ($lines as $line) {
            if (strlen($line) > $limit) {
                $results = array_merge($results, str_split($line, $limit));
            }
            else {
                $results[] = $line;
            }
        }
        return join(EOL_WINDOWS, $results);
    }


    /**
     * Parse a full email address "FirstName LastName <user@domain.tld>" and return the address part.
     *
     * @param  string $value
     *
     * @return ?string - address part or NULL if the passed string is not valid
     */
    protected function parseAddress(string $value): ?string {
        $value = trim($value);

        if (strEndsWith($value, '>')) {
            // closing angle bracket found, check for a matching opening bracket
            $address = strRightFrom($value, '<', -1);           // omit the opening bracket
            $address = trim(substr($address, 0, -1));           // omit the closing bracket
        }
        else {
            // no closing angle bracket found, it must be a simple address
            $address = $value;
        }

        if (strlen($address) && filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return $address;
        }
        return null;
    }


    /**
     * Remove a header from the passed array and return its value. If the array contains multiple
     * such headers all headers are removed and the last removed one is returned.
     *
     * @param  string[] $headers - reference to the header array to modify
     * @param  string   $name    - header to remove and return
     *
     * @return ?string - value of the last removed header or NULL if no such header was found
     */
    protected function removeHeader(array &$headers, string $name): ?string {
        if (!preg_match('/^[a-z]+(-[a-z]+)*$/i', $name)) throw new InvalidValueException("Invalid parameter \$name: \"$name\"");
        $result = null;

        foreach ($headers as $i => $header) {
            if (strStartsWith($header, "$name:", true)) {
                $result = trim(substr($header, strlen($name) + 1));
                unset($headers[$i]);
            }
        }
        return $result;
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
