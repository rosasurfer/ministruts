<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\mail;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;

use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strLeft;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strRightFrom;

/**
 * Mailer
 */
abstract class Mailer extends CObject {

    /** @var mixed[] */
    protected array $options;

    /**
     * Constructor
     *
     * @param  mixed[] $options [optional] - mailer configuration
     */
    protected function __construct(array $options = []) {
        $this->options = $options;
    }


    /**
     * Factory method to create a mailer instance according to the specified options.
     *
     * @param  mixed[] $options [optional] - mailer options
     *
     * @return self - new mailer instance
     *
     * @example
     * <pre>
     *  Option fields:
     *  --------------
     *  'class'      = {classname}      // custom mailer implementation, must extend this class (default: auto)
     *  'smtp'       = [                //
     *    'hostname' = {hostname|ip}    // host name or IP address of the SMTP server to be used for mail delivery (default: .ini setting "SMTP")
     *    'port'     = (int)            // port of the SMTP server to be used for mail delivery (default: .ini setting "smtp_port")
     *    'auth'     = (string)         // SMTP authentication type
     *    'user'     = (string)         // SMTP authentication username
     *    'pass'     = (string)         // SMTP authentication password
     *  ]
     * </pre>
     */
    public static function create(array $options = []): self {
        // use a custom mailer implementation if given
        $class = $options['class'] ?? null;
        if (isset($class) && Assert::string($class, '$options[class]')) {
            if (!is_subclass_of($class, self::class)) {
                throw new InvalidValueException("\$options[class] ($class) must extend ".self::class);
            }
            return new $class($options);
        }

        // use SMTP mailer for direct MTA delivery
        $smtp = $options['smtp_'] ?? null;                                  // temporarily disable
        if (isset($smtp) && Assert::isArray($smtp, '$options[smtp]')) {
            return new SmtpMailer($options);
        }

        // @todo CliMailer

        // all other cases: use the built-in mailer
        return new PHPMailer($options);
    }


    /**
     * Parse an email address "display name <user@domain.tld>" into name and address part.
     *
     * @param  string $value
     *
     * @return string[] - name and address part or an empty array if the passed address is invalid
     * @phpstan-return array{name:string, address:string}|array{}
     *
     * @todo  The algorithm must account for trailing comments.
     */
    public static function parseAddress(string $value): array {
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

        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return [];
        }
        return [
            'name'    => $name,
            'address' => $address,
        ];
    }


    /**
     * Send an email. Sender and receiver addresses can be specified in simple or full format. Simple format can be specified
     * with or without angle brackets.
     *
     *  - full format:                          "display name <user@domain.tld>"
     *  - simple format with angel brackets:    "<user@domain.tld>"
     *  - simple format without angel brackets: "user@domain.tld"
     *
     * @param  ?string  $sender             - mail sender, if empty the mail is sent from .ini setting "sendmail_from" or the current user
     * @param  string   $receiver           - mail receiver
     * @param  string   $subject            - mail subject
     * @param  string   $message            - mail body
     * @param  string[] $headers [optional] - additional MIME headers, encoded or unencoded (default: none)
     *
     * @return bool - whether the email was accepted for delivery (not whether it was indeed sent)
     */
    abstract public function sendMail(?string $sender, string $receiver, string $subject, string $message, array $headers = []): bool;
}
