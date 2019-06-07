<?php
namespace rosasurfer\net\mail;


/**
 * MailerInterface
 */
interface MailerInterface {


    /**
     * Constructor
     *
     * @param  array $options [optional] - mailer options (default: none)
     */
    public function __construct(array $options = []);


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
     *
     * @return void
     */
    public function sendMail($sender, $receiver, $subject, $message, array $headers = []);
}
