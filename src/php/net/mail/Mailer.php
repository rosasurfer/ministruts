<?php
/**
 * Mailer
 *
 * Mailer-Factory und abstrakte Basisklasse für alle Mailer-Implementierungen.
 */
abstract class Mailer extends Object {


   protected /*string[]*/ $config;


   /**
    * Constructor
    *
    * @param array $options - Mailer-Optionen
    */
   abstract protected function __construct(array $options);


   /**
    * Verschickt eine Mail.
    *
    * @param string $sender   - Absender  (Format: 'Vorname Nachname <user@domain.tld>')
    * @param string $receiver - Empfänger (Format: 'Vorname Nachname <user@domain.tld>')
    * @param string $subject  - Betreffzeile der E-Mail
    * @param string $message  - Inhalt der E-Mail
    * @param array  $headers  - zusätzliche zu setzende Mail-Header
    */
   abstract public function sendMail($sender, $receiver, $subject, $message, array $headers = null);


   /**
    * Erzeugt eine neue Instanz und gibt sie zurück.
    *
    * @param array $options - Mailer-Optionen
    *
    * @return Mailer
    */
   final public static function create(array $options = null) {
      if ($options === null)
         $options = array();

      if (!isSet($options['class']))
         $options['class'] = 'PHPMailer';

      $class = $options['class'];

      return new $class($options);
   }


   /**
    * Verschiebt den Versandvorgang dieses Mailers, wenn dies entsprechend konfiguriert ist.
    *
    * @param array $args - Daten der zu versendenden E-Mail
    *
    * @return bool - ob der Versand verschoben wurde.
    */
   final protected function sendLater(array $args) {
      if (isSet($this->config['send-later']) && $this->config['send-later']) {

         $callable = array($this, 'sendMail');
         array_unshift($args, $callable);

         call_user_func_array('push_shutdown_function', $args);

         $this->config['send-later'] = false;
         return true;

         /**
          * TODO: Message-Queue implementieren
          *
          * Noch keine Möglichkeit gefunden, bei Redirect-Header und aktiviertem "output_buffering" die Header
          * vorzuschicken und den Versand im Hintergrund weiterlaufen zu lassen.  Da der Output-Buffer bei einem
          * Redirect nie voll ist, wartet PHP immer, ob die Shutdown-Funktion noch Content ausgibt.
          *
          * Lösung:  Versand nicht während des Shutdowns, sondern per Message-Queue in einem anderen Prozeß ausführen.
          *          (siehe https://www.dropr.org/)
          */
      }
      return false;
   }


   /**
    * Testet die übergebene E-Mailadresse. Dabei wird geprüft, ob die Domain existiert und ob Mail
    * für das Postfach angenommen wird (wenn möglich).
    *
    * @param  string $address - zu prüfende E-Mail-Adresse
    *
    * @return bool
    */
   public static function testAddress($address) {
      $address = strToLower($address);

      $parts = explode('@', $address);
      if (sizeOf($parts) != 2)
         return false;

      $mailbox = $parts[0];
      $domain  = $parts[1];

      // TODO: DNS und Postannahme prüfen

      // es gibt nur aol.com-Adressen, Format siehe: http://postmaster.info.aol.com/faq/mailerfaq.html#syntax
      if (String ::startsWith($domain, 'aol.') && strRPos($domain, '.')==3)
         return (($domain=='aol.com' || $domain=='aol.de') && preg_match('/^[a-z][a-z0-9]{2,15}$/', $mailbox));

      return true;
   }


   /**
    * Zerlegt eine vollständige E-Mailadresse "Name <user@domain>" in ihre beiden Bestandteile.
    *
    * @param string $address - Adresse
    *
    * @return mixed - ein Array mit den beiden Adressbestandteilen oder FALSE, wenn die übergebene
    *                 Adresse syntaktisch falsch ist
    */
   final protected function parseAddress($address) {
      if (!is_string($address)) throw new IllegalTypeException('Illegal type of parameter $address: '.getType($address));

      $address = trim($address);

      // auf schließende Klammer ">" prüfen
      if (!String ::endsWith($address, '>')) {
         // keine, es muß eine einfache E-Mailadresse sein
         if (CommonValidator ::isEmailAddress($address))
            return array('name'    => '',
                         'address' => $address);
         return false;
      }

      // schließende Klammer existiert, auf öffnende Klammer "<" prüfen
      $open = strRPos($address, '<');
      if ($open === false)
         return false;

      $name    = trim(subStr($address, 0, $open));
      $address = subStr($address, $open+1, strLen($address)-$open-2);

      if (CommonValidator ::isEmailAddress($address))
         return array('name'    => $name==$address ? '': $name,
                      'address' => $address);

      return false;
   }
}


/*
// $AttmFiles ... array containing the filenames to attach like array("file1","file2")
function getMessage($from, $fromName, $to, $toName, $subject, $textMsg, $htmlMsg, $attachments) {
   $outerBoundary = "----=_OuterBoundary_000";
   $innerBoundary = "----=_InnerBoundery_001";
   $htmlMsg = $htmlMsg ? $htmlMsg : preg_replace("/\n/","{br}", $textMsg) or die("Neither text nor HTML part present.");
   $textMsg = $textMsg ? $textMsg : "Sorry, but you need an HTML mailer to read this mail.";
   $from or die("sender address missing");
   $to or die("recipient address missing");

   $headers  = "MIME-Version: 1.0\n";
   $headers .= "From: $fromName <$from>\n";
   $headers .= "To: $toName <$to>\n";
   $headers .= "Reply-To: $fromName <$from>\n";
   $headers .= "Content-Type: multipart/mixed; boundary=\"$outerBoundary\"\n";

   // Messages start with text/html alternatives in OB
   $message  = "This is a multi-part message in MIME format.\n";
   $message .= "\n";
   $message .= "--$outerBoundary\n";
   $message .= "Content-Type: multipart/alternative; boundary=\"$innerBoundary\"\n";
   $message .= "\n";
   //plaintext section
   $message .= "\n";
   $message .= "--$innerBoundary\n";
   $message .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
   $message .= "Content-Transfer-Encoding: quoted-printable\n";
   $message .= "\n";
   // plaintext goes here
   $message .= $textMsg."\n\n";
   // html section
   $message .= "\n";
   $message .= "--$innerBoundary\n";
   $message .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
   $message .= "Content-Transfer-Encoding: base64\n";
   $message .= "\n";
   // html goes here
   $message .= chunk_split(base64_encode($htmlMsg))."\n";
   $message .= "\n";
   // end of IB
   $message .= "\n";
   $message .= "--$innerBoundary--\n";
   // attachments
   if ($attachments) {
      foreach($attachments as $attachment) {
         $pathArray = explode ("/", $attachment);
         $fileName = $pathArray[count($pathArray)-1];
         $message .= "\n";
         $message .= "--$outerBoundary\n";
         $message .= "Content-Type: application/octetstream; name=\"$fileName\"\n";
         $message .= "Content-Transfer-Encoding: base64\n";
         $message .= "Content-Disposition: attachment; filename=\"$fileName\"\n";
         $message .= "\n";

         // file goes here
         $fd = fOpen ($attachment, 'rb');
         $content = fRead($fd, fileSize($attachment));
         fClose($fd);
         $content = chunk_split(base64_encode($content));
         $message .= $content;
         $message .= "\n";
         $message .= "\n";
      }
   }

   // send message
   $message .= "\n";
   $message.="--$outerBoundary--\n";

   return $message;
}

// $AttmFiles ... array containing the filenames to attach like array("file1","file2")
function composeMessage($from, $fromName, $to, $toName, $subject, $textMsg, $htmlMsg, $attachments) {
   $outerBoundary = "----=_OuterBoundary_000";
   $innerBoundary = "----=_InnerBoundery_001";
   $htmlMsg = $htmlMsg ? $htmlMsg : preg_replace("/\n/","{br}", $textMsg) or die("Neither text nor HTML part present.");
   $textMsg = $textMsg ? $textMsg : "Sorry, but you need an HTML mailer to read this mail.";
   $from or die("sender address missing");
   $to or die("recipient address missing");

   $headers  = "MIME-Version: 1.0\n";
   $headers .= "From: $fromName <$from>\n";
   $headers .= "To: $toName <$to>\n";
   $headers .= "Reply-To: $fromName <$from>\n";
   $headers .= "Content-Type: multipart/mixed; boundary=\"$outerBoundary\"\n";

   // Messages start with text/html alternatives in OB
   $message  = "This is a multi-part message in MIME format.\n";
   $message .= "\n";
   $message .= "--$outerBoundary\n";
   $message .= "Content-Type: multipart/alternative; boundary=\"$innerBoundary\"\n";
   $message .= "\n";
   //plaintext section
   $message .= "\n";
   $message .= "--$innerBoundary\n";
   $message .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
   $message .= "Content-Transfer-Encoding: quoted-printable\n";
   $message .= "\n";
   // plaintext goes here
   $message .= $textMsg."\n\n";
   // html section
   $message .= "\n";
   $message .= "--$innerBoundary\n";
   $message .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
   $message .= "Content-Transfer-Encoding: base64\n";
   $message .= "\n";
   // html goes here
   $message .= chunk_split(base64_encode($htmlMsg))."\n";
   $message .= "\n";
   // end of IB
   $message .= "\n";
   $message .= "--$innerBoundary--\n";
   // attachments
   if ($attachments) {
      foreach($attachments as $attachment) {
         $pathArray = explode ("/", $attachment);
         $fileName = $pathArray[count($pathArray)-1];
         $message .= "\n";
         $message .= "--$outerBoundary\n";
         $message .= "Content-Type: application/octetstream; name=\"$fileName\"\n";
         $message .= "Content-Transfer-Encoding: base64\n";
         $message .= "Content-Disposition: attachment; filename=\"$fileName\"\n";
         $message .= "\n";

         // file goes here
         $fd = fOpen($attachment, 'rb');
         $content = fRead($fd, fileSize($attachment));
         fClose($fd);
         $content = chunk_split(base64_encode($content));
         $message .= $content;
         $message .= "\n";
         $message .= "\n";
      }
   }

   // send message
   $message .= "\n";
   $message.="--$outerBoundary--\n";

   mail($to, $subject, $message, $headers);
}
*/
?>
