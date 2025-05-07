<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\RuntimeException;

use function rosasurfer\ministruts\normalizeEOL;
use function rosasurfer\ministruts\strContains;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strLeft;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strRightFrom;
use function rosasurfer\ministruts\strStartsWithI;

use const rosasurfer\ministruts\EOL_WINDOWS;
use const rosasurfer\ministruts\WINDOWS;


/**
 * PHPMailer
 *
 * A mailer sending email using the built-in PHP function mail().
 */
class PHPMailer extends CObject {

    /** @var scalar[] */
    protected $options;

    /** @var string */
    protected $hostName;


    /**
     * Constructor
     *
     * @param  mixed[] $options [optional] - mailer configuration
     */
    public function __construct(array $options = []) {
        $this->options = $options;

        // get our hostname
        $hostName = php_uname('n');
        if (!$hostName)
            $hostName  = 'localhost';
        if (!strContains($hostName, '.'))
            $hostName .= '.localdomain';            // hostname must contain more than one part (see RFC 2821)
        $this->hostName = strtolower($hostName);
    }


    /**
     * Send an email. Sender and receiver addresses can be specified in simple or full format. The simple format
     * can be specified with or without angle brackets. If an empty sender is specified the mail is sent from the
     * current user.
     *
     * @param  ?string  $sender             - mail sender (From:), full format: "FirstName LastName <user@domain.tld>"
     * @param  string   $receiver           - mail receiver (To:), full format: "FirstName LastName <user@domain.tld>"
     * @param  string   $subject            - mail subject
     * @param  string   $message            - mail body
     * @param  string[] $headers [optional] - additional MIME headers (default: none)
     *
     * @return bool - whether the email was accepted for delivery (not whether it was indeed sent)
     */
    public function sendMail(?string $sender, string $receiver, string $subject, string $message, array $headers = []): bool {
        // delay sending to the script's shutdown if configured (e.g. as not to block other tasks)
        if (!empty($this->options['send-later'])) {
            return $this->sendLater($sender, $receiver, $subject, $message, $headers);
        }
        /** @var Config $config */
        $config = $this->di('config');

        // first validate the additional headers
        foreach ($headers as $i => $header) {
            if (!preg_match('/^[a-z]+(-[a-z]+)*:/i', $header)) {
                throw new InvalidValueException('Invalid parameter $headers['.$i.']: "'.$header.'"');
            }
        }

        // auto-complete sender if not specified
        if (!isset($sender)) {
            $sender = $config->get('mail.from', ini_get('sendmail_from') ?: '');
            if (!strlen($sender)) {
                $sender = strtolower(get_current_user().'@'.$this->hostName);
            }
        }

        // Return-Path: (invisible sender)
        $returnPath = self::parseAddress($sender);
        if (!$returnPath)                  throw new InvalidValueException('Invalid parameter $sender: '.$sender);
        $value = $this->removeHeader($headers, 'Return-Path');
        if (isset($value)) {
            $returnPath = self::parseAddress($value);
            if (!$returnPath)              throw new InvalidValueException('Invalid header "Return-Path: '.$value.'"');
        }

        // From: (visible sender)
        $from = self::parseAddress($sender);
        if (!$from)                        throw new InvalidValueException('Invalid parameter $sender: '.$sender);
        $value = $this->removeHeader($headers, 'From');
        if (isset($value)) {
            $from = self::parseAddress($value);
            if (!$from)                    throw new InvalidValueException('Invalid header "From: '.$value.'"');
        }
        $from = $this->encodeNonAsciiChars($from);

        // RCPT: (receiving mailbox)
        $rcpt = self::parseAddress($receiver);
        if (!$rcpt)                        throw new InvalidValueException('Invalid parameter $receiver: '.$receiver);
        $forced = $config->get('mail.forced-receiver', '');
        Assert::string($forced, 'config value "mail.forced-receiver"');
        if (strlen($forced)) {
            $rcpt = self::parseAddress($forced);
            if (!$rcpt)                    throw new InvalidValueException('Invalid config value "mail.forced-receiver": '.$forced);
        }

        // To: (visible receiver)
        $to = self::parseAddress($receiver);
        if (!$to)                          throw new InvalidValueException('Invalid parameter $receiver: '.$receiver);
        $value = $this->removeHeader($headers, 'To');
        if (isset($value)) {
            $to = self::parseAddress($value);
            if (!$to)                      throw new InvalidValueException('Invalid header "To: '.$value.'"');
        }
        $to = $this->encodeNonAsciiChars($to);

        // Subject:
        $subject = $this->encodeNonAsciiChars(trim($subject));

        // encode remaining headers (must be ASCII chars only)
        foreach ($headers as $i => &$header) {
            $pattern = '/^([a-z]+(?:-[a-z]+)*): *(.*)/i';
            $match = null;
            if (!preg_match($pattern, $header, $match)) throw new InvalidValueException('Invalid parameter $headers['.$i.']: "'.$header.'"');
            $name   = $match[1];
            $value  = $this->encodeNonAsciiChars(trim($match[2]));
            $header = $name.': '.$value;
        }
        unset($header);

        // add more needed headers
        $headers[] = 'X-Mailer: Microsoft Office Outlook 11';               // save us from Hotmail junk folder
        $headers[] = 'X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.2180';
        $headers[] = 'Content-Type: text/plain; charset=utf-8';
        $headers[] = 'Content-Transfer-Encoding: quoted-printable';
        $headers[] = 'From: '.trim($from['name'].' <'.$from['address'].'>');
        if ($rcpt != $to) {                                                 // on Linux mail() always adds another "To:" header (same as RCPT),
            $headers[] = 'To: '.trim($to['name'].' <'.$to['address'].'>');  // on Windows only if $headers is missing one
        }

        // mail body
        $message = str_replace(chr(0), '\0', $message);                     // replace NUL bytes which destroy the mail
        $message = normalizeEOL($message, EOL_WINDOWS);                     // multiple lines must be separated by CRLF
        $message = quoted_printable_encode($message);

        // TODO: wrap long lines into several shorter ones                  // max 998 chars per RFC but e.g. FastMail only accepts 990
                                                                            // @see https://tools.ietf.org/html/rfc2822 see 2.1 General description
        $oldSendmail_from = ini_get('sendmail_from');
        WINDOWS && PHP::ini_set('sendmail_from', $returnPath['address']);
        $receiver = trim($rcpt['name'].' <'.$rcpt['address'].'>');

        error_clear_last();
        $accepted = mail($receiver, $subject, $message, join(EOL_WINDOWS, $headers), '-f '.$returnPath['address']);
        if (!$accepted) throw new RuntimeException(error_get_last()['message'] ?? __METHOD__.'(): email was not accepted for delivery');

        WINDOWS && PHP::ini_set('sendmail_from', $oldSendmail_from);
        return true;
    }


    /**
     * Delay sending of the mail to the script shutdown phase.  Can be used to not to block other more important tasks.
     *
     * NOTE: Usage of this method is a poor man's approach and a last resort. It's recommended to use a message queue
     *       as a more professional way to decouple sending of mail.
     *
     * @param  ?string  $sender             - mail sender
     * @param  string   $receiver           - mail receiver
     * @param  string   $subject            - mail subject
     * @param  string   $message            - mail body
     * @param  string[] $headers [optional] - additional MIME headers (default: none)
     *
     * @return bool - whether sending of the email was successfully delayed
     */
    protected function sendLater(?string $sender, string $receiver, string $subject, string $message, array $headers = []): bool {
        $callable = [$this, 'sendMail'];
        register_shutdown_function($callable, $sender, $receiver, $subject, $message, $headers);

        $this->options['send-later'] = false;       // TODO: this causes the next mail to be sent immediately (not what we want)
        return true;

        // TODO: Not yet found a way to send a "Location" header (redirect) to the client, close the browser connection
        //       and keep the mail script sending in background with "output_buffering" enabled. As the output buffer is
        //       never full from just a redirect header PHP is waiting for the shutdown function to finish as it might
        //       push more content into the buffer. Maybe "output_buffering" can be disabled when entering shutdown?
    }


    /**
     * Parse a full email address "FirstName LastName <user@domain.tld>" into name and address part.
     *
     * @param  string $value
     *
     * @return ?string[] - array with name and address part or NULL if the specified address is invalid
     */
    public function parseAddress(string $value) {
        $value = trim($value);

        if (strEndsWith($value, '>')) {
            // closing angle bracket found, check for a matching opening bracket
            $name    = trim(strLeftTo($value, '<', -1));
            $address = strRightFrom($value, '<', -1);           // omits the opening bracket
            $address = trim(strLeft($address, -1));             // omit the closing bracket
        }
        else {
            // no closing angle bracket found, it must be a simple address
            $name  = '';
            $address = $value;
        }

        if (strlen($address) && filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return [
                'name'    => $name,
                'address' => $address
            ];
        }
        return null;
    }


    /**
     * Search for a given header and return its value. If the array contains multiple headers of that name the last such
     * header is returned.
     *
     * @param  string[] $headers - array of headers
     * @param  string   $name    - header to search for
     *
     * @return ?string - value of the last found header or NULL if the header was not found
     */
    protected function getHeader(array $headers, string $name): ?string {
        if (!preg_match('/^[a-z]+(-[a-z]+)*$/i', $name)) throw new InvalidValueException("Invalid parameter $name: \"$name\"");

        // reversely iterate over the array to find the last of duplicate headers
        for (end($headers); key($headers)!==null; prev($headers)){
            /** @var string $header */
            $header = current($headers);
            if (strStartsWithI($header, "$name:")) {
                return trim(substr($header, strlen($name)+1));
            }
        }
        return null;
    }


    /**
     * Remove the given header from the array and return its value. If the array contains multiple headers of that name
     * all such headers are removed and the last removed one is returned.
     *
     * @param  string[] $headers - reference to an array of headers
     * @param  string   $name    - header to remove
     *
     * @return ?string - value of the last removed header or NULL if the header was not found
     */
    protected function removeHeader(array &$headers, string $name): ?string {
        if (!preg_match('/^[a-z]+(-[a-z]+)*$/i', $name)) throw new InvalidValueException("Invalid parameter $name: \"$name\"");

        $result = null;

        foreach ($headers as $i => $header) {
            if (strStartsWithI($header, $name.':')) {
                $result = trim(substr($header, strlen($name)+1));
                unset($headers[$i]);
            }
        }
        return $result;
    }


    /**
     * Encode non-ASCII characters with UTF-8.
     *
     * @param  string|string[] $value - a single value or a list of values
     *
     * @return ($value is string ? string : string[]) - the encoded value(s)
     */
    protected function encodeNonAsciiChars($value) {
        if (is_array($value)) {
            /** @var string[] */
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->encodeNonAsciiChars($v);
            }
            return $result;
        }

        if (preg_match('/[\x80-\xFF]/', $value)) {
            return '=?utf-8?B?'.base64_encode($value).'?=';
        }
        return $value;

        // TODO: see https://tools.ietf.org/html/rfc1522
        //
        // An encoded-word may not be more than 75 characters long, including charset,
        // encoding, encoded-text, and delimiters.  If it is desirable to encode more
        // text than will fit in an encoded-word of 75 characters, multiple encoded-words
        // (separated by SPACE or newline) may be used.  Message header lines that contain
        // one or more encoded words should be no more than 76 characters long.
        //
        // While there is no limit to the length of a multiple-line header
        // field, each line of a header field that contains one or more
        // encoded-words is limited to 76 characters.
    }
}
