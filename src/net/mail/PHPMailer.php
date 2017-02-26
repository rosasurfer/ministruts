<?php
namespace rosasurfer\net\mail;

use rosasurfer\exception\UnimplementedFeatureException;


/**
 * Mailer, der Mails mit Hilfe der in PHP integrierten mail()-Funktion verschickt.
 */
class PHPMailer extends Mailer {


    /**
     * Verschickt eine Mail.
     *
     * @param  string   $sender   - Absender  (Format: 'Vorname Nachname <user@domain.tld>')
     * @param  string   $receiver - Empfaenger (Format: 'Vorname Nachname <user@domain.tld>')
     * @param  string   $subject  - Betreffzeile der E-Mail
     * @param  string   $message  - Inhalt der E-Mail
     * @param  string[] $headers  - zusaetzliche zu setzende Mail-Header (default: none)
     */
    public function sendMail($sender, $receiver, $subject, $message, array $headers=[]) {
        throw new UnimplementedFeatureException('Method '.get_class().'::'.__FUNCTION__.'() is not implemented');

        // Versand je nach Konfiguration verschieben (um z.B. Transaktionen nicht zu blockieren)
        if ($this->sendLater($sender, $receiver, $subject, $message, $headers))
            return;
    }
}
