<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\mail;

use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\util\PHP;

use PHPMailer\PHPMailer\PHPMailer as ClassicPHPMailer;
use PHPMailer\PHPMailer\SMTP;

use function rosasurfer\ministruts\strContains;
use function rosasurfer\ministruts\strIsDigits;

use const rosasurfer\ministruts\WINDOWS;

/**
 * SmtpMailer
 *
 * A mailer using package "phpmailer/phpmailer" to send email directly to an SMTP server.
 */
class SmtpMailer extends Mailer {

    /** @var string - SMTP hostname or IP address*/
    protected string $host;

    /** @var int - SMTP port */
    protected int $port;

    /** @var ?string - SMTP authentication type */
    protected ?string $auth;

    /** @var ?string - SMTP authentication user */
    protected ?string $user;

    /** @var ?string - SMTP authentication password */
    protected ?string $pass;

    /**
     * {@inheritDoc}
     *
     * @example
     * <pre>
     *  SMTP specific options:
     *  ----------------------
     *  'smtp'       = [
     *    'hostname' = {hostname|ip}    // host name or IP address of the SMTP server to be used for mail delivery (default: .ini setting "SMTP")
     *    'port'     = (int)            // port of the SMTP server to be used for mail delivery (default: .ini setting "smtp_port")
     *    'auth'     = (string)         // SMTP authentication type
     *    'user'     = (string)         // SMTP authentication username (default: the current user)
     *    'pass'     = (string)         // SMTP authentication password
     *  ]
     * </pre>
     */
    public function __construct(array $options = []) {
        parent::__construct($options);

        $smtpHost = $options['smtp']['hostname'] ?? ini_get('SMTP') ?: '';
        Assert::stringNotEmpty($smtpHost, '$options[smtp][hostname]');
        $this->host = $smtpHost;

        $smtpPort = $options['smtp']['port'] ?? ini_get('smtp_port') ?: null;
        Assert::stringNotEmpty($smtpPort, '$options[smtp][port]');
        if (!strIsDigits($smtpPort)) {
            throw new InvalidValueException("Invalid parameter \$options[smtp][port]: \"$smtpPort\" (non-digits)");
        }
        $this->port = (int)$smtpPort;

        $smtpAuth = $options['smtp']['auth'] ?? '';
        Assert::string($smtpAuth, '$options[smtp][auth]');
        $this->auth = $smtpAuth ?: null;

        $smtpUser = $options['smtp']['user'] ?? '';
        Assert::string($smtpUser, '$options[smtp][user]');
        $this->user = $smtpUser ?: null;

        $smtpPass = $options['smtp']['pass'] ?? '';
        Assert::string($smtpPass, '$options[smtp][pass]');
        $this->pass = $smtpPass ?: null;
    }


    /**
     * {@inheritDoc}
     */
    public function sendMail(?string $sender, string $receiver, string $subject, string $message, array $headers = []): bool {
        // on Windows without authentication: pass-through to PhpMailer which uses the built-in mail() function
        if (false && WINDOWS && !isset($this->auth) && !isset($this->pass)) {                       // @phpstan-ignore-line (development)
            return $this->pass2PhpMailer($sender, $receiver, $subject, $message);
        }

        /** @var Config $config */
        $config = $this->di('config');

        // auto-complete an empty sender
        if (!isset($sender)) {
            $sender = $config->getString('mail.from', ini_get('sendmail_from') ?: '');
            if ($sender == '') {
                $hostName = php_uname('n') ?: 'localhost';
                if (!strContains($hostName, '.')) {
                    $hostName .= '.localdomain';                // hostname must contain more than one part (see RFC 2821)
                }
                $sender = strtolower(get_current_user()."@$hostName");
            }
        }

        // From: (visible sender)
        $from = self::parseAddress($sender);
        if (!$from) throw new InvalidValueException("Invalid parameter \$sender: $sender");

        // RCPT: (receiving mailbox)
        $rcpt = self::parseAddress($receiver);
        if (!$rcpt) throw new InvalidValueException("Invalid parameter \$receiver: $receiver");
        $forced = $config->getString('mail.forced-receiver', '');
        if ($forced != '') {
            $rcpt = self::parseAddress($forced);
            if (!$rcpt) throw new InvalidValueException("Invalid config value \"mail.forced-receiver\": $forced");
        }

        // To: (visible receiver)
        $to = self::parseAddress($receiver);
        if (!$to) throw new InvalidValueException("Invalid parameter \$receiver: $receiver");

        // mail body
        $message = str_replace(chr(0), '\0', $message);         // replace NUL bytes which destroy the mail
        $message = $this->normalizeLines($message);

        // compose the mail
        $mail = new ClassicPHPMailer(true);                     // enable exceptions
        $mail->XMailer = null;                                  // don't add "X-Mailer:" header
        $mail->AllowEmpty = true;                               // don't try to be smart

        $mail->isSMTP();                                        // use SMTP
        $mail->SMTPDebug = SMTP::DEBUG_OFF;                     // DEBUG_OFF|DEBUG_CLIENT|DEBUG_SERVER|DEBUG_CONNECTION|DEBUG_LOWLEVEL
        $mail->Debugoutput = 'error_log';
        $mail->Host = $this->host;                              // MTA hostname
        $mail->Port = $this->port;                              // MTA port

        $mail->setFrom($from['address'], $from['name']);        // "MAIL FROM" and "From:" header
        $mail->addAddress($rcpt['address'], $rcpt['name']);     // RCPT TO

        $mail->Subject = $subject;

        $mail->isHTML(false);
        $mail->CharSet = 'utf-8';
        $mail->Encoding = '8bit';
        $mail->Body = $message;

        // send it
        $result = $mail->send();
        //echo $mail->getSentMIMEMessage().PHP_EOL;
        return $result;
    }


    /**
     * Pass all arguments to the PhpMailer (on Windows without authentication only).
     *
     * @param  ?string  $sender
     * @param  string   $receiver
     * @param  string   $subject
     * @param  string   $message
     * @param  string[] $headers [optional]
     *
     * @return bool
     */
    protected function pass2PhpMailer(?string $sender, string $receiver, string $subject, string $message, array $headers = []): bool {
        static $mailer = null;
        $mailer ??= new PhpMailer($this->options);

        $oldSmtp     = ini_get('SMTP') ?: '';
        $oldSmtpPort = ini_get('smtp_port') ?: '';

        try {
            PHP::ini_set('SMTP', $this->host);
            PHP::ini_set('smtp_port', $this->port);
            return $mailer->sendMail($sender, $receiver, $subject, $message, $headers);
        }
        finally {
            PHP::ini_set('SMTP', $oldSmtp);
            PHP::ini_set('smtp_port', $oldSmtpPort);
        }
    }
}
