<?php
namespace rosasurfer\net\mail;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\ErrorHandler;
use rosasurfer\core\exception\InfrastructureException;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\RuntimeException;

use function rosasurfer\normalizeEOL;

use const rosasurfer\EOL_WINDOWS;
use const rosasurfer\NL;


/**
 * Mailer sending email via TCP directly to an SMTP server.
 *
 * @deprecated - use a better maintained external library
 */
class SMTPMailer extends Mailer {


    /** @var array<string, int> */
    private $defaultOptions = [
        'timeout' => 300,                   // socket timeout
    ];

    /** @var ?resource */
    private $connection = null;

    /** @var int */
    private $responseStatus = 0;

    /** @var string */
    private $response;

    /** @var string */
    private $logBuffer;


    /**
     * Constructor
     *
     * @param  mixed[] $options [optional] - mailer configuration
     */
    public function __construct(array $options = []) {
        trigger_error(__CLASS__.' is deprecated and will be removed in a future release', E_USER_DEPRECATED);

        parent::__construct(\array_merge($this->defaultOptions, $options));

        // set missing options to PHP defaults
        if (!isset($this->options['host'])) {
            $this->options['host'] = ini_get('SMTP');
            $this->options['port'] = ini_get('smtp_port');
        }
        else {
            $host = $this->options['host'];
            Assert::string($host, 'option "host"');
            $parts = explode(':', $host);

            if (sizeof($parts) == 1) {
                if (trim($parts[0]) == '') throw new InvalidArgumentException('Invalid option "host": '.$this->options['host']);
                if (!isset($this->options['port']))
                    $this->options['port'] = ini_get('smtp_port');  // TODO: validate host and port
            }
            elseif (sizeof($parts) == 2) {
                if (trim($parts[0])=='' || trim($parts[1])=='') throw new InvalidArgumentException('Invalid option "host": '.$this->options['host']);
                $this->options['host'] = $parts[0];                 // TODO: validate host and port
                $this->options['port'] = $parts[1];
            }
            else {
                throw new InvalidArgumentException('Invalid option "host": '.$this->options['host']);
            }
        }
    }


    /**
     * Destructor
     *
     * Closes an open connection.
     */
    public function __destruct() {
        try {
            $this->disconnect();
        }
        catch (\Throwable $ex) { throw ErrorHandler::handleDestructorException($ex); }
        catch (\Exception $ex) { throw ErrorHandler::handleDestructorException($ex); }  // @phpstan-ignore catch.alreadyCaught (PHP5 compatibility)
    }


    /**
     * Connect to the SMTP server.
     *
     * @return void
     */
    private function connect() {
        $errorCode = $errorMsg = null;
        $this->connection = fsockopen('tcp://'.$this->options['host'],
                                               $this->options['port'],
                                               $errorCode,
                                               $errorMsg,
                                               $this->options['timeout']);
        $data = stream_get_meta_data($this->connection);
        if ($data['timed_out']) throw new InfrastructureException('Timeout on socket connection');

        socket_set_timeout($this->connection, $this->options['timeout']);

        // init connection
        $this->readResponse();                          // read greeting
        $this->writeData('EHLO '.$this->hostName);      // extended "Hello" first
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 250) {
            $this->writeData('HELO '.$this->hostName);  // regular "Hello" if the extended one fails
            $response = $this->readResponse();

            $this->parseResponse($response);
            if ($this->responseStatus != 250) throw new RuntimeException('HELO command not accepted: '.$this->responseStatus.' '.$this->response);
        }
    }


    /**
     * Authenticate the connection.
     *
     * @return void
     */
    private function authenticate() {
        if (!is_resource($this->connection)) throw new RuntimeException('Cannot authenticate: Not connected');

        // init authentication
        $this->writeData('AUTH LOGIN');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus == 503)
            return;                                     // already authenticated

        if ($this->responseStatus != 334)
            throw new RuntimeException('AUTH LOGIN command not supported: '.$this->responseStatus.' '.$this->response);

        // send username
        $this->writeData(base64_encode($this->options['auth_username']));
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 334)
            throw new RuntimeException('Username '.$this->options['auth_username'].' not accepted'.$this->responseStatus.' '.$this->response);

        // send password
        $this->writeData(base64_encode($this->options['auth_password']));
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 235)
            throw new RuntimeException('Login failed for username '.$this->options['auth_username'].': '.$this->responseStatus.' '.$this->response);
    }


    /**
     * Send an email.  Sender and receiver addresses can be specified in simple or full format.  The simple format
     * can be specified with or without angle brackets.  If an empty sender is specified the mail is sent from the
     * current user.
     *
     * @param  ?string  $sender             - mail sender (From:), full format: "FirstName LastName <user@domain.tld>"
     * @param  string   $receiver           - mail receiver (To:), full format: "FirstName LastName <user@domain.tld>"
     * @param  string   $subject            - mail subject
     * @param  string   $message            - mail body
     * @param  string[] $headers [optional] - additional MIME headers (default: none)
     *
     * @return void
     */
    public function sendMail($sender, $receiver, $subject, $message, array $headers = []) {
        // delay sending to the script's shutdown if configured (e.g. as to not to block other tasks)
        if (!empty($this->options['send-later'])) {
            $this->sendLater($sender, $receiver, $subject, $message, $headers);
            return;
        }
        /** @var ConfigInterface $config */
        $config = $this->di('config');

        // first validate the additional headers
        foreach ($headers as $i => $header) {
            Assert::string($header, '$headers['.$i.']');
            if (!preg_match('/^[a-z]+(-[a-z]+)*:/i', $header)) throw new InvalidArgumentException('Invalid parameter $headers['.$i.']: "'.$header.'"');
        }

        // auto-complete sender if not specified
        if (!isset($sender)) {
            $sender = $config->get('mail.from', ini_get('sendmail_from'));
            if (!strlen($sender)) {
                $sender = strtolower(get_current_user().'@'.$this->hostName);
            }
        }

        // Return-Path: (invisible sender)
        Assert::string($sender, '$sender');
        $returnPath = self::parseAddress($sender);
        if (!$returnPath)                      throw new InvalidArgumentException('Invalid parameter $sender: '.$sender);
        $value = $this->removeHeader($headers, 'Return-Path');
        if (strlen($value)) {
            $returnPath = self::parseAddress($value);
            if (!$returnPath)                  throw new InvalidArgumentException('Invalid header "Return-Path: '.$value.'"');
        }

        // From: (visible sender)
        $from  = self::parseAddress($sender);
        if (!$from)                            throw new InvalidArgumentException('Invalid parameter $sender: '.$sender);
        $value = $this->removeHeader($headers, 'From');
        if (strlen($value)) {
            $from = self::parseAddress($value);
            if (!$from)                        throw new InvalidArgumentException('Invalid header "From: '.$value.'"');
        }

        // RCPT: (invisible receiver)
        Assert::string($receiver, '$receiver');
        $rcpt = self::parseAddress($receiver);
        if (!$rcpt)                            throw new InvalidArgumentException('Invalid parameter $receiver: '.$receiver);
        $forced = $config->get('mail.forced-receiver', '');
        Assert::string($forced, 'config value "mail.forced-receiver"');
        if (strlen($forced)) {
            $rcpt = self::parseAddress($forced);
            if (!$rcpt)                        throw new InvalidArgumentException('Invalid config value "mail.forced-receiver": '.$forced);
        }

        // To: (visible receiver)
        $to = self::parseAddress($receiver);
        if (!$to)                              throw new InvalidArgumentException('Invalid parameter $sender: '.$sender);
        $value = $this->removeHeader($headers, 'To');
        if (strlen($value)) {
            $to = self::parseAddress($value);
            if (!$to)                          throw new InvalidArgumentException('Invalid header "To: '.$value.'"');
        }

        // Subject: subject and body
        Assert::string($subject, '$subject');
        Assert::string($message, '$message');


        // start SMTP communication
        if (is_resource($this->connection)) {
            $this->logBuffer = '';                      // reset log buffer if already connected
        }
        else {
            $this->connect();
        }

        if (!empty($this->options['auth_username']))
            $this->authenticate();


        // send mail
        $this->writeData('MAIL FROM: <'.$returnPath['address'].'>');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 250)
            throw new RuntimeException('MAIL FROM: <'.$returnPath['address'].'> command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'SMTP transfer log:'.NL.'------------------'.NL.$this->logBuffer);

        $this->writeData('RCPT TO: <'.$rcpt['address'].'>');
        $response = $this->readResponse();              // TODO: a DNS lookup in the receiving MTA might cause a timeout in readResponse()

        $this->parseResponse($response);
        if ($this->responseStatus != 250 && $this->responseStatus != 251)
            throw new RuntimeException('RCPT TO: <'.$rcpt['address'].'> command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'SMTP transfer log:'.NL.'------------------'.NL.$this->logBuffer);

        $this->writeData('DATA');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 354)
            throw new RuntimeException('DATA command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'SMTP transfer log:'.NL.'------------------'.NL.$this->logBuffer);

        // TODO: wrap long header lines

        // needed headers
        $this->writeData('Date: '.date('r'));

        $from = $this->encodeNonAsciiChars($from);
        $this->writeData('From: '.$from['name'].' <'.$from['address'].'>');

        $to = $this->encodeNonAsciiChars($to);
        $this->writeData('To: '.$to['name'].' <'.$to['address'].'>');

        $encSubject = $this->encodeNonAsciiChars($subject);
        if (strlen($encSubject) > 76) throw new RuntimeException('The encoded mail subject exceeds the maximum number of characters per line: "'.$subject.'"');
        $this->writeData('Subject: '.$encSubject);
        $this->writeData('X-Mailer: Microsoft Office Outlook 11');  // save us from Hotmail junk folder
        $this->writeData('X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.2180');


        // custom headers
        foreach ($headers as $i => $header) {
            $pattern = '/^([a-z]+(?:-[a-z]+)*): *(.*)/i';
            $match = null;
            if (!preg_match($pattern, $header, $match)) throw new InvalidArgumentException('Invalid parameter $headers['.$i.']: "'.$header.'"');
            $name  = $match[1];
            $value = $this->encodeNonAsciiChars(trim($match[2]));
            $this->writeData($name.': '.$value);
        }
        $this->writeData('');

        $maxLineLength = 990;                           // actually 998 per RFC but e.g. FastMail only accepts 990
                                                        // https://tools.ietf.org/html/rfc2822 see 2.1 General description

        // mail body
        $message = normalizeEOL($message);
        $lines = explode("\n", $message);
        foreach ($lines as $line) {

            // wrap long lines into several shorter ones
            $pieces = [];
            while (strlen($line) > $maxLineLength) {
                $pos = strrpos(substr($line, 0, $maxLineLength), ' ');
                if (!$pos)
                    $pos = $maxLineLength - 1;          // patch to fix DoS attack; good old times :-)

                $pieces[] = substr($line, 0, $pos);
                $line = substr($line, $pos + 1);
            }
            $pieces[] = $line;

            foreach ($pieces as $line) {
                if (substr($line, 0, 1) == '.')
                    $line = '.'.$line;                  // escape leading dots to avoid mail end marker confusion
                $this->writeData($line);
            }
        }


        // end marker
        $this->writeData('.');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 250) throw new RuntimeException(
            'Sent data not accepted: '.$this->responseStatus.' '.$this->response.NL
            .NL
            .'SMTP transfer log:'.NL
            .'------------------'.NL
            .$this->logBuffer
        );
    }


    /**
     * Reset the connection.
     *
     * @return void
     */
    public function reset() {
        if (!is_resource($this->connection)) {
            throw new RuntimeException('Cannot reset connection: Not connected');
        }

        $this->writeData('RSET');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 250)
            throw new RuntimeException('RSET command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'SMTP transfer log:'.NL.'------------------'.NL.$this->logBuffer);
    }


    /**
     * Disconnect.
     *
     * @param  bool $silent [optional] - whether to silently suppress disconnect errors (default: don't)
     *
     * @return void
     */
    public function disconnect($silent = false) {
        if (!is_resource($this->connection)) return;

        if (!$silent) {
            $this->writeData('QUIT');
            $response = $this->readResponse();

            $this->parseResponse($response);
            if ($this->responseStatus != 221)
                throw new RuntimeException('QUIT command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'SMTP transfer log:'.NL.'------------------'.NL.$this->logBuffer);
        }

        fclose($this->connection);
        $this->connection = null;
    }


    /**
     * Read the MTA's response.
     *
     * @return string
     */
    private function readResponse() {
        $lines = '';
        while (trim($line = fgets($this->connection)) != '') {
            $lines .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        $data = stream_get_meta_data($this->connection);
        if ($data['timed_out']) throw new RuntimeException('Timeout on socket connection');

        $this->logResponse($lines);
        return $lines;
    }


    /**
     * Write data into the open socket.
     *
     * @param string $data
     *
     * @return void
     */
    private function writeData($data) {
        $count = fwrite($this->connection, $data.EOL_WINDOWS, strlen($data)+2);

        if ($count != strlen($data)+2) {
            throw new RuntimeException('Error writing to socket, length of data: '.(strlen($data)+2).', bytes written: '.$count.NL.'data: '.$data.NL.NL.'SMTP transfer log:'.NL.'------------------'.NL.$this->logBuffer);
        }
        $this->logSentData($data);
    }


    /**
     * Parse the MTA's response.
     *
     * @param  string $response
     *
     * @return void
     */
    private function parseResponse($response) {
        $response = trim($response);
        $this->responseStatus = (int) substr($response, 0, 3);
        $this->response = substr($response, 4);
    }


    /**
     * Log sent data.
     *
     * @param  string $data
     *
     * @return void
     */
    private function logSentData($data) {
        $data = preg_replace('/^(.*)/m', ' -> $1', $data).NL;
        $this->logBuffer .= $data;
    }


    /**
     * Log reseived data.
     *
     * @param  string $data
     *
     * @return void
     */
    private function logResponse($data) {
        $this->logBuffer .= $data;
    }
}
