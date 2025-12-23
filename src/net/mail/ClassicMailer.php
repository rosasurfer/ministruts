<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\mail;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * ClassicMailer
 *
 * This mailer adds missing features to the mailer from package "phpmailer/phpmailer". That package is used for direct SMTP
 * communication because it's the only maintained package for standard encryption and authentication protocols (despite it's
 * awkward and legacy design).
 */
class ClassicMailer extends PHPMailer {

    /*
    Known issues:
    -------------
    - PHPMailer doesn't support setting a "Return-Path" header (for debatable reasons).

    - PHPMailer doesn't support setting incoming mailbox and "To" header to different addresses (for debatable reasons).

    - PHPMailer ignores a user-defined encoding "8bit" and falls back to "7bit" if the message contains ASCII only.
      That's because "8bit" is the used default value and PHPMailer can't distinguish between default and user setting.
    */
}
