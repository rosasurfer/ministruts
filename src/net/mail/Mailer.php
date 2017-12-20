<?php
namespace rosasurfer\net\mail;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWithI;


/**
 * Mailer
 *
 * Mailer factory and abstract base class for mailer implementations.
 */
abstract class Mailer extends Object implements MailerInterface {


    /** @var array */
    protected $options;


    /**
     * Constructor
     *
     * @param  array $options [optional] - mailer options (default: none)
     */
    public function __construct(array $options = []) {
        $this->options = $options;
    }


    /**
     * Create and return a new instance.
     *
     * @param  array $options [optional] - mailer options
     *
     * @return self
     */
    public static function create(array $options = []) {
        $class = PHPMailer::class;                          // default mailer
        if (!empty($options['class']))
            $class = $options['class'];
        return new $class($options);
    }


    /**
     * Delay sending of the mail to the script shutdown phase. Can be used to not to block other more important tasks.
     *
     * NOTE: Usage of this method is a poor man's approach and a last resort. A more professional way to decouple sending
     *       of mail is using a regular message queue.
     *
     * @param  string   $sender             - mail sender
     * @param  string   $receiver           - mail receiver
     * @param  string   $subject            - mail subject
     * @param  string   $message            - mail body
     * @param  string[] $headers [optional] - additional MIME headers (default: none)
     *
     * @return bool - whether or not sending of the email was successfully delayed
     *
     */
    protected function sendLater($sender, $receiver, $subject, $message, array $headers = []) {
        if (!empty($this->options['send-later'])) {
            $callable = [$this, 'sendMail'];
            register_shutdown_function($callable, $sender, $receiver, $subject, $message, $headers);

            $this->options['send-later'] = false;
            return true;
        }
        return false;

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
     * @return mixed - aray with name and address part or FALSE if the specified address is invalid
     */
    public static function parseAddress($value) {
        if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

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

        if (strLen($address) && filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return [
                'name'    => $name,
                'address' => $address
            ];
        }
        return false;
    }


    /**
     * Search for a given header and return its value. If the array contains multiple headers of that the last such header
     * is returned.
     *
     * @param  string[] $headers - array of headers
     * @param  string   $name    - header to search for
     *
     * @return string|null - value of the last found header or NULL if the header was not found
     */
    protected function getHeader(array $headers, $name) {
        if (!is_string($name))                           throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!preg_match('/^[a-z]+(-[a-z]+)*$/i', $name)) throw new InvalidArgumentException('Invalid parameter $name: "'.$name.'"');

        // reversely iterate over the array to find the last of duplicate headers
        for (end($headers); key($headers)!==null; prev($headers)){
            $header = current($headers);
            if (strStartsWithI($header, $name.':'))
                return trim(subStr($header, strLen($name)+1));
        }
        return null;
    }


    /**
     * Remove a given header from the array and return its value. If the array contains multiple headers of that name all
     * such headers are removed and the last removed one is returned.
     *
     * @param  string[] &$headers - array of headers
     * @param  string    $name    - header to remove
     *
     * @return string|null - value of the last removed header or NULL if the header was not found
     */
    protected function removeHeader(array &$headers, $name) {
        if (!is_string($name))                           throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!preg_match('/^[a-z]+(-[a-z]+)*$/i', $name)) throw new InvalidArgumentException('Invalid parameter $name: "'.$name.'"');

        $result = null;

        foreach ($headers as $i => $header) {
            if (strStartsWithI($header, $name.':')) {
                $result = trim(subStr($header, strLen($name)+1));
                unset($headers[$i]);
            }
        }
        return $result;
    }


    /**
     * Encode non-ASCII characters with UTF-8. If a string doesn't contain non-ASCII characters it is not modified.
     *
     * @param  string|string[] $value - value(s)
     *
     * @return string|string[] - encoded value(s)
     */
    protected function encodeNonAsciiChars($value) {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->{__FUNCTION__}($v);
            }
        }
        elseif (preg_match('/[\x80-\xFF]/', $value)) {
            $value = '=?utf-8?B?'.base64_encode($value).'?=';
          //$value = '=?utf-8?Q?'.imap_8bit($value).'?=';       // requires imap extension and the encoded string is longer
        }

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

        return $value;
    }
}
