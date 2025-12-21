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
     * Constructor
     *
     * @param  mixed[] $options [optional] - mailer configuration
     */
    public function __construct(array $options = []) {
        parent::__construct($options);
    }
}
