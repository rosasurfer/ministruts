<?php
namespace rosasurfer\net\mail;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\util\Validator;

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
     * @param  array $options - mailer options
     */
    public function __construct(array $options) {
        $this->options = $options;
    }


    /**
     * Create a new instance.
     *
     * @param  array $options [optional] - mailer options
     *
     * @return self
     */
    final public static function create(array $options=[]) {
        if (!isSet($options['class']))
            $options['class'] = 'PHPMailer';

        $class = $options['class'];
        return new $class($options);
    }


    /**
     * Delay sending of the mail to the script shutdown phase. Can be used to not to block other more important tasks.
     *
     * @param  string   $sender             - sender address (format: 'Firstname SecondName <user@domain.tld>')
     * @param  string   $receiver           - receiver address (format: 'Firstname SecondName <user@domain.tld>')
     * @param  string   $subject            - mail subject line
     * @param  string   $message            - mail body
     * @param  string[] $headers [optional] - additional headers to set (default: no)
     *
     * @return bool - whether or not sending otf the email was successfully delayed
     */
    public function sendLater($sender, $receiver, $subject, $message, array $headers=[]) {
        if (isSet($this->options['send-later']) && $this->options['send-later']) {

            $callable = [$this, 'sendMail'];
            register_shutdown_function($callable, $sender, $receiver, $subject, $message, $headers);

            $this->options['send-later'] = false;
            return true;

            /**
             * TODO: implement a regular message queue
             *
             * Not yet found a way to send a "Location" header (redirect) to the client, close the browser connection and
             * keep the mail script sending in background with "output_buffering" enabled. As the output buffer is never
             * "full" from just a redirect header, PHP is waiting for the shutdown function to finish as it might push more
             * content into the buffer. Maybe "output_buffering" can be disabled when entering shutdown?
             *
             * Solution: The correct way is to use a message queue in another process and trash sendLater() all together.
             */
        }
        return false;
    }


    /**
     * Parse a full email address "Firstname Secondname <user@domain>" into name and address part.
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
            if (Validator::isEmailAddress($address))
                return [
                    'name'    => '',
                    'address' => $address
                ];
            return false;
        }

        // closing brace exists, check opening brace "<"
        $open = strRPos($address, '<');
        if ($open === false)
            return false;

        $name    = trim(subStr($address, 0, $open));
        $address = subStr($address, $open+1, strLen($address)-$open-2);

        if (Validator::isEmailAddress($address))
            return [
                'name'    => $name==$address ? '' : $name,
                'address' => $address
            ];

        return false;
    }
}
