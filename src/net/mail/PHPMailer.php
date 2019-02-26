<?php
namespace rosasurfer\net\mail;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\util\PHP;

use function rosasurfer\normalizeEOL;

use const rosasurfer\EOL_WINDOWS;
use const rosasurfer\WINDOWS;


/**
 * Mailer sending email using the built-in PHP function mail().
 */
class PHPMailer extends Mailer {


    /**
     * Send an email. Sender and receiver addresses can be specified in simple or full format. The simple format can be
     * specified with or without angle brackets. If an empty sender is specified the mail is sent from the current user.
     *
     * @param  string   $sender             - mail sender (From:), full format: "FirstName LastName <user@domain.tld>"
     * @param  string   $receiver           - mail receiver (To:), full format: "FirstName LastName <user@domain.tld>"
     * @param  string   $subject            - mail subject
     * @param  string   $message            - mail body
     * @param  string[] $headers [optional] - additional MIME headers (default: none)
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
            Assert::string($header, 'Illegal type of parameter $headers['.$i.']: %s');
            if (!preg_match('/^[a-z]+(-[a-z]+)*:/i', $header))
                                           throw new InvalidArgumentException('Invalid parameter $headers['.$i.']: "'.$header.'"');
        }

        // auto-complete sender if not specified
        if (!isset($sender)) {
            $sender = $config->get('mail.from', ini_get('sendmail_from'));
            if (!strlen($sender)) {
                $sender = strtolower(get_current_user().'@'.$this->hostName);
            }
        }

        // Return-Path: (invisible sender)
        Assert::string($sender, 'Illegal type of parameter $sender: %s');
        $returnPath = self::parseAddress($sender);
        if (!$returnPath)                  throw new InvalidArgumentException('Invalid parameter $sender: '.$sender);
        $value = $this->removeHeader($headers, 'Return-Path');
        if (strlen($value)) {
            $returnPath = self::parseAddress($value);
            if (!$returnPath)              throw new InvalidArgumentException('Invalid header "Return-Path: '.$value.'"');
        }

        // From: (visible sender)
        $from = self::parseAddress($sender);
        if (!$from)                        throw new InvalidArgumentException('Invalid parameter $sender: '.$sender);
        $value = $this->removeHeader($headers, 'From');
        if (strlen($value)) {
            $from = self::parseAddress($value);
            if (!$from)                    throw new InvalidArgumentException('Invalid header "From: '.$value.'"');
        }
        $from = $this->encodeNonAsciiChars($from);

        // RCPT: (receiving mailbox)
        Assert::string($receiver, 'Illegal type of parameter $receiver: %s');
        $rcpt = self::parseAddress($receiver);
        if (!$rcpt)                        throw new InvalidArgumentException('Invalid parameter $receiver: '.$receiver);
        $forced = $config->get('mail.forced-receiver', '');
        Assert::string($forced, 'Illegal type of config value "mail.forced-receiver": %s');
        if (strlen($forced)) {
            $rcpt = self::parseAddress($forced);
            if (!$rcpt)                    throw new InvalidArgumentException('Invalid config value "mail.forced-receiver": '.$forced);
        }

        // To: (visible receiver)
        $to = self::parseAddress($receiver);
        if (!$to)                          throw new InvalidArgumentException('Invalid parameter $receiver: '.$receiver);
        $value = $this->removeHeader($headers, 'To');
        if (strlen($value)) {
            $to = self::parseAddress($value);
            if (!$to)                      throw new InvalidArgumentException('Invalid header "To: '.$value.'"');
        }
        $to = $this->encodeNonAsciiChars($to);

        // Subject:
        Assert::string($subject, 'Illegal type of parameter $subject: %s');
        $subject = $this->encodeNonAsciiChars(trim($subject));

        // encode remaining headers (must be ASCII chars only)
        foreach ($headers as $i => &$header) {
            $pattern = '/^([a-z]+(?:-[a-z]+)*): *(.*)/i';
            if (!preg_match($pattern, $header, $match)) throw new InvalidArgumentException('Invalid parameter $headers['.$i.']: "'.$header.'"');
            $name   = $match[1];
            $value  = $this->encodeNonAsciiChars(trim($match[2]));
            $header = $name.': '.$value;
        }; unset($header);

        // add more needed headers
        $headers[] = 'X-Mailer: Microsoft Office Outlook 11';               // save us from Hotmail junk folder
        $headers[] = 'X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.2180';
        $headers[] = 'Content-Type: text/plain; charset=utf-8';             // ASCII is a subset of UTF-8
        $headers[] = 'From: '.trim($from['name'].' <'.$from['address'].'>');
        if ($rcpt != $to)                                                   // on Linux mail() always adds another "To:" header (same as RCPT),
            $headers[] = 'To: '.trim($to['name'].' <'.$to['address'].'>');  // on Windows only if $headers is missing one

        // mail body
        Assert::string($message, 'Illegal type of parameter $message: %s');
        $message = str_replace(chr(0), '?', $message);                      // replace NUL bytes which destroy the mail
        $message = normalizeEOL($message, EOL_WINDOWS);                     // multiple lines must be separated by CRLF

        // TODO: wrap long lines into several shorter ones                  // max 998 chars per RFC but e.g. FastMail only accepts 990
                                                                            // @see https://tools.ietf.org/html/rfc2822 see 2.1 General description
        $oldSendmail_from = ini_get('sendmail_from');
        WINDOWS && PHP::ini_set('sendmail_from', $returnPath['address']);
        $receiver = trim($rcpt['name'].' <'.$rcpt['address'].'>');

        mail($receiver, $subject, $message, join(EOL_WINDOWS, $headers), '-f '.$returnPath['address']);

        WINDOWS && PHP::ini_set('sendmail_from', $oldSendmail_from);
    }
}
