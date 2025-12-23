<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\mail;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;

use function rosasurfer\ministruts\normalizeEOL;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strLeft;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strRightFrom;

use const rosasurfer\ministruts\EOL_UNIX;
use const rosasurfer\ministruts\EOL_WINDOWS;

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

        // use SMTP mailer for direct SMTP communication
        $smtp = $options['smtp'] ?? null;
        if (isset($smtp) && Assert::isArray($smtp, '$options[smtp]')) {
            return new SmtpMailer($options);
        }

        // @todo CliMailer

        // all other cases: use the built-in mailer
        return new PhpMailer($options);
    }


    /**
     * Parse an email address "display name <user@domain.tld>" into name and address part.
     *
     * @param  string $value
     *
     * @return string[] - name and address part or an empty array if the passed address is invalid
     * @phpstan-return array{name:string, address:string}|array{}
     */
    public static function parseAddress(string $value): array {
        $value = trim($value);                                  // @todo The algorithm must account for trailing comments.

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
     * Wrap long lines and ensure RFC-compliant line endings.
     *
     * @param  string $value
     *
     * @return string
     *
     * @link https://www.rfc-editor.org/rfc/rfc2822#section-2.1.1
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
     * Send an email. Sender and receiver addresses may be specified in simple or full format. Simple format may be specified with
     * or without angle brackets.
     *
     *  - full format:                          "display name <user@domain.tld>" </br>
     *  - simple format with angel brackets:    "<user@domain.tld>"              </br>
     *  - simple format without angel brackets: "user@domain.tld"                </br>
     *
     * @param  ?string  $sender             - mail sender, if empty the mail is sent from .ini setting "sendmail_from" or the current user
     * @param  string   $receiver           - mail receiver
     * @param  string   $subject            - mail subject
     * @param  string   $message            - mail body
     * @param  string[] $headers [optional] - additional headers, plain text or MIME encoded (default: none)
     *
     * @return bool - whether the email was accepted for delivery (not whether it was indeed sent)
     */
    abstract public function sendMail(?string $sender, string $receiver, string $subject, string $message, array $headers = []): bool;
}
