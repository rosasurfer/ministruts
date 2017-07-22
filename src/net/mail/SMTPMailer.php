<?php
namespace rosasurfer\net\mail;

use rosasurfer\config\Config;
use rosasurfer\debug\ErrorHandler;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InfrastructureException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\util\Date;

use function rosasurfer\normalizeEOL;
use function rosasurfer\strContains;
use function rosasurfer\strStartsWithI;

use const rosasurfer\EOL_WINDOWS;
use const rosasurfer\NL;


/**
 * Simple mailer sending an email directly via an SMTP server.
 *
 * @deprecated - Use an external library for sending more user friendly email or for using templates.
 */
class SMTPMailer extends Mailer {


    /** @var array */
    private $defaultOptions = [
        'timeout' => 300,                   // socket timeout
    ];

    /** @var string */
    private $hostName;

    /** @var resource */
    private $connection;

    /** @var int */
    private $responseStatus = 0;

    /** @var string */
    private $response;

    /** @var string */
    private $logBuffer;


    /**
     * Constructor
     *
     * @param  array $options - mailer options
     */
    public function __construct(array $options) {
        trigger_error(__CLASS__.' is deprecated and will be removed in a future release', E_USER_DEPRECATED);

        parent::__construct(array_merge($this->defaultOptions, $options));

        // set missing options to PHP defaults
        if (!isSet($this->options['host'])) {
            $this->options['host'] = ini_get('SMTP');
            $this->options['port'] = ini_get('smtp_port');
        }
        else {
            $host = $this->options['host'];
            if (!is_string($host)) throw new IllegalTypeException('Illegal type of option "host": '.getType($this->options['host']));
            $parts = explode(':', $host);

            if (sizeOf($parts) == 1) {
                if (trim($parts[0]) == '') throw new InvalidArgumentException('Invalid option "host": '.$this->options['host']);
                if (!isSet($this->options['port']))
                    $this->options['port'] = ini_get('smtp_port');  // TODO: validate host and port
            }
            elseif (sizeOf($parts) == 2) {
                if (trim($parts[0])=='' || trim($parts[1])=='') throw new InvalidArgumentException('Invalid option "host": '.$this->options['host']);
                $this->options['host'] = $parts[0];                 // TODO: validate host and port
                $this->options['port'] = $parts[1];
            }
            else {
                throw new InvalidArgumentException('Invalid option "host": '.$this->options['host']);
            }
        }

        // get our hostname
        $hostName = php_uname('n');
        if (!$hostName)
            $hostName  = 'localhost';
        if (!strContains($hostName, '.'))
            $hostName .= '.localdomain';            // hostname must contain more than one part (see RFC 2821)
        $this->hostName = strToLower($hostName);
    }


    /**
     * Destructor
     *
     * Closes an open connection.
     */
    public function __destruct() {
        // Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
        // @see http://php.net/manual/en/language.oop5.decon.php
        try {
            $this->disconnect();
        }
        catch (\Exception $ex) {
            throw ErrorHandler::handleDestructorException($ex);
        }
    }


    /**
     * Connect to the SMTP server.
     */
    private function connect() {
        $connection = fSockOpen('tcp://'.$this->options['host'],
                                         $this->options['port'],
                                         $errorCode,
                                         $errorMsg,
                                         $this->options['timeout']);
        // TODO: connect() might hang without producing an error if the connection fails
        if (!$connection) throw new RuntimeException('Could not open socket: '.$errorMsg.' (error '.$errorCode.')');

        $data = stream_get_meta_data($connection);
        if ($data['timed_out']) throw new InfrastructureException('Timeout on socket connection');

        socket_set_timeout($connection, $this->options['timeout']);
        $this->connection = $connection;

        // init connection
        $this->readResponse();                          // read greeting
        $this->writeData('EHLO '.$this->hostName);      // extended "Hello" first
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 250) {
            $this->writeData('HELO '.$this->hostName);   // regular "Hello" if the extended one fails
            $response = $this->readResponse();

            $this->parseResponse($response);
            if ($this->responseStatus != 250)
                throw new RuntimeException('HELO command not accepted: '.$this->responseStatus.' '.$this->response);
        }
    }


    /**
     * Authentificate the connection.
     */
    private function authenticate() {
        if (!is_resource($this->connection))
            throw new RuntimeException('Cannot authenticate: Not connected');

        // init authentication
        $this->writeData('AUTH LOGIN');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus == 503)
            return;                          // already authenticated

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
     * Send an email.
     *
     * @param  string $sender             - sender (format: 'FirstName SecondName <user@domain.tld>')
     * @param  string $receiver           - receiver (format: 'FirstName SecondName <user@domain.tld>')
     * @param  string $subject            - email subject line
     * @param  string $message            - email body
     * @param  array  $headers [optional] - additional headers to set (default: none)
     */
    public function sendMail($sender, $receiver, $subject, $message, array $headers=[]) {
        if (!is_string($sender))   throw new IllegalTypeException('Illegal type of parameter $sender: '.getType($sender));
        $from = $this->parseAddress($sender);
        if (!$from)                throw new InvalidArgumentException('Invalid argument $sender: '.$sender);

        if (!is_string($receiver)) throw new IllegalTypeException('Illegal type of parameter $receiver: '.getType($receiver));

        if (!$config=Config::getDefault()) throw new RuntimeException('Service locator returned empty default config: '.getType($config));

        $forced = $config->get('mail.forced-receiver', '');
        if (!is_string($forced))           throw new IllegalTypeException('Invalid type of variable $forced: '.getType($forced).' (not string)');

        strLen($forced) && $receiver=$forced;
        $to = $this->parseAddress($receiver);
        if (!$to) throw new InvalidArgumentException('Invalid argument $receiver: '.$receiver);

        if (!is_string($subject)) throw new IllegalTypeException('Illegal type of parameter $subject: '.getType($subject));
        if (!is_string($message)) throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));

        foreach ($headers as $key => $header) {
            if (!is_string($header)) throw new IllegalTypeException('Illegal parameter type in argument $headers[$key]: '.getType($header));
        }


        // delay sending to the script's shutdown if configured (e.g. as to not to block other tasks)
        if ($this->sendLater($sender, $receiver, $subject, $message, $headers))
            return;


        if (is_resource($this->connection)) {
            $this->logBuffer = '';          // reset log buffer if already connected
        }
        else {
            $this->connect();
        }

        if (!empty($this->options['auth_username']))
            $this->authenticate();


        // check for a custom 'Return-Path' header
        $returnPath = $from['address'];
        foreach ($headers as $key => $header) {
            $header = trim($header);
            if (strStartsWithI($header, 'return-path:')) {
                $result = $this->parseAddress(subStr($header, 12));
                if (!$result) throw new InvalidArgumentException('Invalid Return-Path header: '.$header);
                $returnPath = $result['address'];
                unset($headers[$key]);
            }
        }


        // init mail
        $this->writeData('MAIL FROM: <'.$returnPath.'>');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 250)
            throw new RuntimeException('MAIL FROM: <'.$returnPath.'> command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'Transfer log:'.NL.'-------------'.NL.$this->logBuffer);

        $this->writeData('RCPT TO: <'.$to['address'].'>');
        $response = $this->readResponse();     // TODO: a DNS lookup in the receiving MTA might cause a timeout in readResponse()

        $this->parseResponse($response);
        if ($this->responseStatus != 250 && $this->responseStatus != 251)
            throw new RuntimeException('RCPT TO: <'.$to['address'].'> command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'Transfer log:'.NL.'-------------'.NL.$this->logBuffer);

        $this->writeData('DATA');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 354)
            throw new RuntimeException('DATA command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'Transfer log:'.NL.'-------------'.NL.$this->logBuffer);

        // TODO: wrap long header lines

        // needed headers
        $this->writeData('Date: '.date('r'));

        $from = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $from);
        $this->writeData('From: '.$from['name'].' <'.$from['address'].'>');

        $to = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $to);
        $this->writeData('To: '.$to['name'].' <'.$to['address'].'>');

        $subject = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $subject);
        $this->writeData('Subject: '.$subject);
        $this->writeData('X-Mailer: Microsoft Office Outlook 11');     // save us from Hotmail junk folder
        $this->writeData('X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.2180');


        // custom headers                   // TODO: validation
        foreach ($headers as $header) {
            $header = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $header);
            $this->writeData($header);
        }
        $this->writeData('');


        $maxLineLength = 990;   // actually 998 per RFC but e.g. FastMail only accepts 990

        // mail body
        $message = normalizeEOL($message);
        $lines = explode("\n", $message);
        foreach ($lines as $line) {

            // wrap long lines into several shorter ones
            $pieces = null;
            while (strLen($line) > $maxLineLength) {
                $pos = strRPos(subStr($line, 0, $maxLineLength), ' ');
                if (!$pos)
                    $pos = $maxLineLength - 1;    // patch to fix DoS attack; good old times :-)

                $pieces[] = subStr($line, 0, $pos);
                $line = subStr($line, $pos + 1);
            }
            $pieces[] = $line;

            foreach ($pieces as $line) {
                if (subStr($line, 0, 1) == '.')
                    $line = '.'.$line;            // escape leading dots to avoid mail end marker confusion
                $this->writeData($line);
            }
        }

        // end marker
        $this->writeData('.');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 250)
            throw new RuntimeException('Sent data not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'Transfer log:'.NL.'-------------'.NL.$this->logBuffer);
    }


    /**
     * Reset the connection.
     */
    public function reset() {
        if (!is_resource($this->connection))
            throw new RuntimeException('Cannot reset connection: Not connected');

        $this->writeData('RSET');
        $response = $this->readResponse();

        $this->parseResponse($response);
        if ($this->responseStatus != 250)
            throw new RuntimeException('RSET command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'Transfer log:'.NL.'-------------'.NL.$this->logBuffer);
    }


    /**
     * Disconnect.
     *
     * @param  bool $silent [optional] - whether or not to silently suppress disconnect errors (default: no)
     */
    public function disconnect($silent = false) {
        if (!is_resource($this->connection))
            return;

        if (!$silent) {
            $this->writeData('QUIT');
            $response = $this->readResponse();

            $this->parseResponse($response);
            if ($this->responseStatus != 221)
                throw new RuntimeException('QUIT command not accepted: '.$this->responseStatus.' '.$this->response.NL.NL.'Transfer log:'.NL.'-------------'.NL.$this->logBuffer);
        }

        fClose($this->connection);
        $this->connection = null;
    }


    /**
     * Read the MTA's response.
     */
    private function readResponse() {
        $lines = null;
        while (trim($line = fGets($this->connection)) != '') {
            $lines .= $line;
            if (subStr($line, 3, 1) == ' ')
                break;
        }
        $data = stream_get_meta_data($this->connection);
        if ($data['timed_out'])
            throw new RuntimeException('Timeout on socket connection');

        $this->logResponse($lines);
        return $lines;
    }


    /**
     * Write data into the open socket.
     *
     * @param string $data
     */
    private function writeData($data) {
        $count = fWrite($this->connection, $data.EOL_WINDOWS, strLen($data)+2);

        if ($count != strLen($data)+2)
            throw new RuntimeException('Error writing to socket, length of data: '.(strLen($data)+2).', bytes written: '.$count.NL.'data: '.$data.NL.NL.'Transfer log:'.NL.'-------------'.NL.$this->logBuffer);

        $this->logSentData($data);
    }


    /**
     * Parse the MTA's response.
     *
     * @param string $response
     */
    private function parseResponse($response) {
        $response = trim($response);
        $this->responseStatus = intVal(subStr($response, 0, 3));
        $this->response = subStr($response, 4);
    }


    /**
     * Log sent data.
     *
     * @param string $data
     */
    private function logSentData($data) {
        $data = preg_replace('/^(.*)/m', ' -> $1', $data).NL;
        $this->logBuffer .= $data;
    }


    /**
     * Log reseived data.
     *
     * @param string $data
     */
    private function logResponse($data) {
        $this->logBuffer .= $data;
    }
}
