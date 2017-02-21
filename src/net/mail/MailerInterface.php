<?php
namespace rosasurfer\net\mail;


/**
 * MailerInterface
 */
interface MailerInterface {


   /**
    * Constructor
    *
    * @param  array $options - Mailer-Optionen
    */
   protected function __construct(array $options);


   /**
    * Verschickt eine Mail.
    *
    * @param  string   $sender   - Absender  (Format: 'Vorname Nachname <user@domain.tld>')
    * @param  string   $receiver - Empfaenger (Format: 'Vorname Nachname <user@domain.tld>')
    * @param  string   $subject  - Betreffzeile der E-Mail
    * @param  string   $message  - Inhalt der E-Mail
    * @param  string[] $headers  - zusaetzliche zu setzende Mail-Header (default: none)
    *
    * @return void
    */
   public function sendMail($sender, $receiver, $subject, $message, array $headers=[]);


   /**
    * Verschiebt den Versandvorgang dieses Mailers, wenn dies entsprechend konfiguriert ist.
    *
    * @param  string   $sender   - Absender  (Format: 'Vorname Nachname <user@domain.tld>')
    * @param  string   $receiver - Empfaenger (Format: 'Vorname Nachname <user@domain.tld>')
    * @param  string   $subject  - Betreffzeile der E-Mail
    * @param  string   $message  - Inhalt der E-Mail
    * @param  string[] $headers  - zusaetzliche zu setzende Mail-Header (default: keine)
    *
    * @return bool - ob der Versand verschoben wurde.
    */
   protected function sendLater($sender, $receiver, $subject, $message, array $headers=[]);
}
