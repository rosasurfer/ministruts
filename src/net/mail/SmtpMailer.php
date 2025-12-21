<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\mail;

/**
 * SmtpMailer
 *
 * A mailer sending email to an SMTP server.
 */
class SmtpMailer extends Mailer {

    /**
     * {@inheritDoc}
     */
    public function __construct(array $options = []) {
        parent::__construct($options);
    }


    /**
     * {@inheritDoc}
     */
    public function sendMail(?string $sender, string $receiver, string $subject, string $message, array $headers = []): bool {
        return false;
    }
}
