<?
/**
 * Mailer
 *
 * Example:
 * --------
 * $mailer = new Mailer();
 * $mailer->sendMail($from, $to, $subject, $message, $headers);
 */
class Mailer extends Object {


   private /*array*/ $config = array('host'    => null,      // SMTP server host name
                                     'port'    => null,      // SMTP server port
                                     'auth'    => false,     // use authentification ?
                                     'user'    => null,      // username
                                     'pass'    => null,      // password
                                     'timeout' => 90);       // default socket timeout

   private /*string*/   $hostname;
   private /*resource*/ $connection     = null;
   private /*int*/      $responseStatus = 0;
   private /*string*/   $response       = null;
   private /*string*/   $logBuffer      = null;


   /**
    * Constructor
    */
   public function __construct() {
      $this->config['host'] = ini_get('SMTP');
      $this->config['port'] = ini_get('smtp_port');

      if (isSet($GLOBALS['smtp_use_auth'])) {
         $this->config['auth'] = (bool) $GLOBALS['smtp_use_auth'];
         if ($this->config['auth']) {
            $this->config['user'] = $GLOBALS['smtp_user'];
            $this->config['pass'] = $GLOBALS['smtp_pass'];
         }
      }

      // get our hostname
      $hostname = php_uName('n');
      if (!$hostname)
         $hostname  = 'localhost';
      if (!String ::contains($hostname, '.'))
         $hostname .= '.localdomain';    // hostname must contain more than one part (see RFC 2821)
      $this->hostname = strToLower($hostname);

      $this->log("\n----==:[ New Mailer instance - smtp://".$this->config['host'].':'.$this->config['port'].($this->config['auth'] ? ' with authentification':'')."]:==----");
   }


   /**
    * Destructor
    *
    * Sorgt bei Zerstörung des Objekts dafür, daß eine noch offene Connection geschlossen werden.
    */
   public function __destruct() {
      // Wird der Destructor nach einer Exception während des Versandes aufgerufen und löst disconnect()
      // in der Folge eine weitere Exception aus, wird keine der beiden Exceptions von PHP an den globalen
      // Errorhandler übergeben.  Deshalb ist jede öffentliche Methode dieser Klasse in einen try-catch-Block
      // gekapselt, der bei Auslösen einer Exception die Verbindung still schließt [$this->disconnect(true)].
      // Dadurch wird gewährleistet, daß Exceptions, die hier im Destruktor auftreten, normal weitergereicht
      // werden können [$this->disconnect(false)] und nicht absichtlich unterdrückt werden müssen.

      $this->disconnect(false);
   }


   /**
    * Erzeugt eine neue Instanz und gibt sie zurück.
    *
    * @return Mailer
    */
   public static function create() {
      return new self();
   }


   /**
    * Verbindung herstellen
    */
   private function connect() {
      $this->log("\n----==::  Connecting  :==----");
      $connection = fSockOpen('tcp://'.$this->config['host'],
                              $this->config['port'],
                              $errorCode,
                              $errorMsg,
                              $this->config['timeout']);
      if (!$connection)
         throw new RuntimeException("Could not open socket: $errorMsg (error $errorCode)");

      $data = stream_get_meta_data($connection);
      if ($data['timed_out'])
         throw new InfrastructureException('Timeout on socket connection');

      socket_set_timeout($connection, $this->config['timeout']);
      $this->connection = $connection;

      // init connection
      $this->readResponse();                          // read greeting
      $this->writeData('EHLO '.$this->hostname);      // extended Hello first...
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 250) {
         $this->writeData('HELO '.$this->hostname);   // normal Hello if extended fails...
         $response = $this->readResponse();

         $this->parseResponse($response);
         if ($this->responseStatus != 250)
            throw new MailerException('HELO command not accepted: '.$this->responseStatus.' '.$this->response);
      }
   }


   /**
    * Authentifizierung
    */
   private function authenticate() {
      if (!is_resource($this->connection))
         throw new RuntimeException('Cannot authenticate: Not connected');

      // init authentication
      $this->writeData('AUTH LOGIN');
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus == 503)
         return;                          // already authenticated

      if ($this->responseStatus != 334)
         throw new MailerException('AUTH LOGIN command not supported: '.$this->responseStatus.' '.$this->response);

      // send user
      $this->writeData(base64_encode($this->config['user']));
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 334)
         throw new MailerException('Username '.$this->config['user'].' not accepted'.$this->responseStatus.' '.$this->response);

      // send pass
      $this->writeData(base64_encode($this->config['pass']));
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 235)
         throw new MailerException('Login failed for username '.$this->config['user'].': '.$this->responseStatus.' '.$this->response);
   }


   /**
    * Mail verschicken.
    */
   public function sendMail($fromAddress, $toAddress, $subject, $message, array $headers = null) {
      // alles kapseln, um bei Fehlern Verbindung still zu schließen
      try {

         if (!is_string($fromAddress)) throw new IllegalTypeException('Illegal type of parameter $fromAddress: '.getType($fromAddress));
         $from = $this->parseAddress($fromAddress);
         if (!$from) throw new InvalidArgumentException('Invalid argument $fromAddress: '.$fromAddress);

         if (!is_string($toAddress)) throw new IllegalTypeException('Illegal type of parameter $toAddress: '.getType($toAddress));
         $to = $this->parseAddress($toAddress);
         if (!$to) throw new InvalidArgumentException('Invalid argument $toAddress: '.$toAddress);

         if (!is_string($subject)) throw new IllegalTypeException('Illegal type of parameter $subject: '.getType($subject));
         if (!is_string($message)) throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));

         if ($headers === null)
            $headers = array();
         foreach ($headers as $key => $header)
            if (!is_string($header)) throw new IllegalTypeException('Illegal parameter type in argument $headers[$key]: '.getType($header));


         if (is_resource($this->connection))
            $this->logBuffer = null;         // reset log buffer if already connected

         if (!is_resource($this->connection))
            $this->connect();

         if ($this->config['auth'])
            $this->authenticate();


         // init mail
         $this->log("\n----==::  Sending new mail  [from: $from[address]] [to: $to[address]] [subject: $subject]  :==----");


         // check for a custom 'Return-Path' header
         $returnPath = $from['address'];
         foreach ($headers as $key => $header) {
            $header = trim($header);
            if (String ::startsWith($header, 'return-path:', true)) {
               $result = $this->parseAddress(subStr($header, 12));
               if (!$result) throw new InvalidArgumentException('Invalid Return-Path header: '.$header);
               $returnPath = $result['address'];
               unset($headers[$key]);
            }
         }

         $this->writeData("MAIL FROM: <$returnPath>");
         $response = $this->readResponse();

         $this->parseResponse($response);
         if ($this->responseStatus != 250)
            throw new MailerException("MAIL FROM: <$returnPath> command not accepted: ".$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);

         $this->writeData("RCPT TO: <$to[address]>");
         // TODO: macht der MTA ein DNS lookup, kann es in readResponse() zu einem Time-out kommen
         $response = $this->readResponse();

         $this->parseResponse($response);
         if ($this->responseStatus != 250 && $this->responseStatus != 251)
            throw new MailerException("RCPT TO: <$to[address]> command not accepted: ".$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);


         // send mail data
         $this->writeData('DATA');
         $response = $this->readResponse();

         $this->parseResponse($response);
         if ($this->responseStatus != 354)
            throw new MailerException('DATA command not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);


         // send needed headers
         $this->writeData('Date: '.date('r'));
            $from = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $from);
         $this->writeData("From: $from[name] <$from[address]>");
            $to = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $to);
         $this->writeData("To: $to[name] <$to[address]>");
            $subject = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $subject);
         $this->writeData("Subject: $subject");
         $this->writeData("X-Mailer: Microsoft Office Outlook 11");     // save us from Hotmail junk folder
         $this->writeData("X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.2180");


         // send custom headers
         foreach ($headers as $header) {
            // TODO: Header syntaktisch überprüfen
            $header = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $header);
            $this->writeData($header);
         }
         $this->writeData('');

         $message = str_replace("\r\n", "\n", $message);
         $message = str_replace("\n", "\r\n", $message);
         $this->writeData($message);                                    // body

         $this->writeData('.');                                         // end
         $response = $this->readResponse();

         $this->parseResponse($response);
         if ($this->responseStatus != 250)
            throw new MailerException('Sent data not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);

      }
      catch (Exception $ex) {
         $this->disconnect(true);
         throw $ex;
      }
   }


   /**
    * Verbindung resetten
    */
   public function reset() {
      // alles kapseln, um bei Fehlern Verbindung still zu schließen
      try {

         if (!is_resource($this->connection))
            throw new RuntimeException('Cannot reset connection: Not connected');

         $this->writeData('RSET');
         $response = $this->readResponse();

         $this->parseResponse($response);
         if ($this->responseStatus != 250)
            throw new MailerException('RSET command not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);

      }
      catch (Exception $ex) {
         $this->disconnect(true);
         throw $ex;
      }
   }


   /**
    * Verbindung trennen
    *
    * @param bool $silent - ob die Verbindung still und ohne Exception geschlossen werden soll
    *                       (default = FALSE)
    */
   public function disconnect($silent = false) {
      if (!is_resource($this->connection))
         return;

      if (!$silent) {
         $this->writeData('QUIT');
         $response = $this->readResponse();

         $this->parseResponse($response);
         if ($this->responseStatus != 221)
            throw new MailerException('QUIT command not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);
      }

      fClose($this->connection);
      $this->connection = null;
   }


   /**
    * Antwort der Gegenseite lesen
    */
   private function readResponse() {
      $lines = null;
      while (trim($line = fGets($this->connection)) != '') {
         $lines .= $line;
         if (subStr($line, 3, 1) == ' ')
            break;
      }
      $data = stream_get_meta_data($this->connection);
      if ($data['timed_out'])
         throw new RuntimeException('Timeout on socket connection');

      $this->logResponse($lines);
      return $lines;
   }


   /**
    * Daten in die Socketverbindung schreiben
    */
   private function writeData($data) {
      $count = fWrite($this->connection, $data."\r\n", strLen($data)+2);

      if ($count != strLen($data)+2)
         throw new RuntimeException('Error writing to socket, length of data: '.(strLen($data)+2).', bytes written: '.$count."\ndata: ".$data."\n\nTransfer log:\n-------------\n".$this->logBuffer);

      $this->logSentData($data);
   }


   /**
    * Response parsen
    */
   private function parseResponse($response) {
      $response = trim($response);
      $this->responseStatus = intVal(subStr($response, 0, 3));
      $this->response = subStr($response, 4);
   }


   /**
    * Message loggen
    */
   private function log($data) {
      $data .= "\n";
   }

   /**
    * Gesendete Daten loggen
    */
   private function logSentData($data) {
      $data = preg_replace('/^(.*)/m', " -> $1", $data)."\n";
      $this->logBuffer .= $data;
   }


   /**
    * Empfangene Daten loggen
    */
   private function logResponse($data) {
      $this->logBuffer .= $data;
   }


   /**
    * Testet die übergebene E-Mailadresse. Dabei wird geprüft, ob die Domain existiert und ob Mail
    * für das Postfach angenommen wird (wenn möglich).
    *
    * @param  string $address - zu prüfende E-Mail-Adresse
    *
    * @return boolean
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
         return ($domain=='aol.com' && preg_match('/^[a-z][a-z0-9]{2,15}$/', $mailbox));

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
   private function parseAddress($address) {
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
