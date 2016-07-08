<?php
use rosasurfer\ministruts\exceptions\UnimplementedFeatureException;


/**
 * Mailer, der Mails über eine FileSocket-Verbindung verschickt.
 */
class FileSocketMailer extends Mailer {


   /**
    * Constructor
    *
    * @param  array $options - Mailer-Optionen
    */
   protected function __construct(array $options) {
      throw new UnimplementedFeatureException('Method '.get_class($this).'::'.__FUNCTION__.'() is not implemented');
   }


   /**
    * Verschickt eine Mail.
    *
    * @param  string $sender   - Absender  (Format: 'Vorname Nachname <user@domain.tld>')
    * @param  string $receiver - Empfänger (Format: 'Vorname Nachname <user@domain.tld>')
    * @param  string $subject  - Betreffzeile der E-Mail
    * @param  string $message  - Inhalt der E-Mail
    * @param  array  $headers  - zusätzliche zu setzende Mail-Header
    */
   public function sendMail($sender, $receiver, $subject, $message, array $headers = null) {
      throw new UnimplementedFeatureException('Method '.get_class($this).'::'.__FUNCTION__.'() is not implemented');

      // Versand je nach Konfiguration verschieben (um z.B. Transaktionen nicht zu blockieren)
      if ($this->sendLater($sender, $receiver, $subject, $message, $headers))
         return;
   }
}
