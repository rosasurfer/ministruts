<?php
namespace rosasurfer\net\mail;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;

use function rosasurfer\strEndsWith;


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
     * Create a new instance.
     *
     * @param  array $options [optional] - mailer options
     *
     * @return self
     */
    public static function create(array $options = []) {
        $class = SMTPMailer::class;                         // default implementation

        if (!empty($options['class']))
            $class = $options['class'];

        return new $class($options);
    }


    /**
     * Delay sending of the mail to the script shutdown phase. Can be used to not to block other more important tasks.
     *
     * NOTE: Usage of this method is a poor man's approach. A more professional way to decouple sending of mail is to use
     *       a regular message queue.
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
     * Parse a full email address "FirstName LastName <user@domain>" into name and address part.
     *
     * @param  string $address
     *
     * @return mixed - aray with name and address part or FALSE if the specified address is invalid
     */
    protected function parseAddress($address) {
        if (!is_string($address)) throw new IllegalTypeException('Illegal type of parameter $address: '.getType($address));

        $address = trim($address);

        // check for closing brace ">"
        if (!strEndsWith($address, '>')) {
            // none, it has to be a simple address
            if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return [
                    'name'    => '',
                    'address' => $address
                ];
            }
            return false;
        }

        // closing brace exists, check opening brace "<"
        $open = strRPos($address, '<');
        if ($open === false)
            return false;

        $name    = trim(subStr($address, 0, $open));
        $address = subStr($address, $open+1, strLen($address)-$open-2);

        if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return [
                'name'    => $name==$address ? '' : $name,
                'address' => $address
            ];
        }
        return false;
    }
}
