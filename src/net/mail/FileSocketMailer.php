<?php
namespace rosasurfer\net\mail;

use rosasurfer\core\exception\UnimplementedFeatureException;


/**
 * Mailer sending email via a file socket to a local MTA.
 */
class FileSocketMailer extends Mailer {


    /**
     * Send an email&#46;  Sender and receiver addresses can be specified in simple or full format&#46;  The simple format
     * can be specified with or without angle brackets&#46;  If an empty sender is specified the mail is sent from the
     * current user.
     *
     * @param  string   $sender             - mail sender (From:), full format: "FirstName LastName <user@domain.tld>"
     * @param  string   $receiver           - mail receiver (To:), full format: "FirstName LastName <user@domain.tld>"
     * @param  string   $subject            - mail subject
     * @param  string   $message            - mail body
     * @param  string[] $headers [optional] - additional MIME headers (default: none)
     */
    public function sendMail($sender, $receiver, $subject, $message, array $headers = []) {
        throw new UnimplementedFeatureException('Method '.get_class($this).'::'.__FUNCTION__.'() is not implemented');
    }
}
