<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\mail;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use PHPMailer\PHPMailer\SMTP;

/**
 * Mailer
 */
class Mailer extends CObject {

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
     * Factory method to create a mailer instance according to the passed options.
     *
     * @param  mixed[] $options [optional] - mail options
     *
     * @return self - new mailer instance
     *
     * @example
     * <pre>
     *  Option fields:
     *  --------------
     *  'class'      = (string)         // custom mailer class (must extend this class)
     *  'smtp'       = [                //
     *    'hostname' = {hostname|ip}    // host name or IP address of the SMTP server to be used for mail delivery (default: "php.ini" setting "SMTP")
     *    'port'     = (int)            // port of the SMTP server to be used for mail delivery (default: "php.ini" setting "smtp_port")
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
        $smtp = $options['smtp'] ?? null;
        if (isset($smtp) && Assert::isArray($smtp, '$options[smtp]')) {
            return new SmtpMailer($options);
        }

        // @todo CliMailer

        // all other cases: use the built-in mailer
        return new PHPMailer($options);
    }
}
