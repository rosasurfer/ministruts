<?php
namespace rosasurfer\net\mail;

use rosasurfer\config\Config;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InfrastructureException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\util\Date;
use rosasurfer\util\Logger;
use rosasurfer\util\System;

use function rosasurfer\strContains;
use function rosasurfer\strStartsWithI;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;


/**
 * Mailer, der Mails per SMTP-Protokoll über einen SMTP-Server verschickt.
 */
class SMTPMailer extends Mailer {


   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice,
                  /*int */ $maxSendingTime = 2;    // benötigt der Versand länger als hier angegeben, wird er im Loglevel DEBUG geloggt


   protected /*string*/ $config = array('host'          => null,     // SMTP server host name
                                        'port'          => null,     // SMTP server port
                                        'timeout'       => 300,      // socket timeout: sendmail braucht ewig
                                        'auth_username' => null,     // authentification username
                                        'auth_password' => null,     // authentification password
                                        );

   private /*string  */ $hostName;
   private /*resource*/ $connection;
   private /*int     */ $responseStatus = 0;
   private /*string  */ $response;
   private /*string  */ $logBuffer;


   /**
    * Constructor
    *
    * @param  array $options - Mailer-Optionen
    */
   protected function __construct(array $options) {
      $loglevel        = Logger::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      // fehlende Optionen mit PHP-Defaults bestücken
      if (!isSet($options['host'])) {
         $options['host'] = ini_get('SMTP');
         $options['port'] = ini_get('smtp_port');
      }
      else {
         $host = $options['host'];
         if (!is_string($host)) throw new IllegalTypeException('Illegal type of option "host": '.getType($options['host']));
         $parts = explode(':', $host);

         if (sizeOf($parts) == 1) {
            if (trim($parts[0]) == '') throw new InvalidArgumentException('Invalid option "host": '.$options['host']);
            if (!isSet($options['port']))
               $options['port'] = ini_get('smtp_port');  // TODO: Adresse und Port prüfen
         }
         elseif (sizeOf($parts) == 2) {
            if (trim($parts[0])=='' || trim($parts[1])=='') throw new InvalidArgumentException('Invalid option "host": '.$options['host']);
            $options['host'] = $parts[0];                // TODO: Adresse und Port prüfen
            $options['port'] = $parts[1];
         }
         else {
            throw new InvalidArgumentException('Invalid option "host": '.$options['host']);
         }
      }

      // vorgegebene mit übergebenen Optionen mergen
      $this->config = array_merge($this->config, $options);

      // get our hostname
      $hostName = php_uname('n');
      if (!$hostName)
         $hostName  = 'localhost';
      if (!strContains($hostName, '.'))
         $hostName .= '.localdomain';    // hostname must contain more than one part (see RFC 2821)
      $this->hostName = strToLower($hostName);
   }


   /**
    * Destructor
    *
    * Sorgt bei Zerstörung des Objekts dafür, daß eine noch offene Connection geschlossen werden.
    */
   public function __destruct() {
      // Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
      // @see http://php.net/manual/en/language.oop5.decon.php
      try {
         $this->disconnect();
      }
      catch (\Exception $ex) {
         System::handleDestructorException($ex);
         throw $ex;
      }
   }


   /**
    * Verbindung herstellen
    */
   private function connect() {
      $connection = fSockOpen('tcp://'.$this->config['host'],
                              $this->config['port'],
                              $errorCode,
                              $errorMsg,
                              $this->config['timeout']);
      // TODO: connect() bleibt ohne Fehlermeldung hängen, wenn keine Verbindung zustande kommt
      if (!$connection)
         throw new RuntimeException("Could not open socket: $errorMsg (error $errorCode)");

      $data = stream_get_meta_data($connection);
      if ($data['timed_out'])
         throw new InfrastructureException('Timeout on socket connection');

      socket_set_timeout($connection, $this->config['timeout']);
      $this->connection = $connection;

      // init connection
      $this->readResponse();                          // read greeting
      $this->writeData('EHLO '.$this->hostName);      // extended Hello first...
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 250) {
         $this->writeData('HELO '.$this->hostName);   // normal Hello if extended fails...
         $response = $this->readResponse();

         $this->parseResponse($response);
         if ($this->responseStatus != 250)
            throw new RuntimeException('HELO command not accepted: '.$this->responseStatus.' '.$this->response);
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
         throw new RuntimeException('AUTH LOGIN command not supported: '.$this->responseStatus.' '.$this->response);

      // send username
      $this->writeData(base64_encode($this->config['auth_username']));
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 334)
         throw new RuntimeException('Username '.$this->config['auth_username'].' not accepted'.$this->responseStatus.' '.$this->response);

      // send password
      $this->writeData(base64_encode($this->config['auth_password']));
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 235)
         throw new RuntimeException('Login failed for username '.$this->config['auth_username'].': '.$this->responseStatus.' '.$this->response);
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
      if (!is_string($sender)) throw new IllegalTypeException('Illegal type of parameter $sender: '.getType($sender));
      $from = $this->parseAddress($sender);
      if (!$from)              throw new InvalidArgumentException('Invalid argument $sender: '.$sender);

      if (!is_string($receiver)) throw new IllegalTypeException('Illegal type of parameter $receiver: '.getType($receiver));
      $forced = Config::getDefault()->get('mail.address.forced-receiver', null);
      strLen($forced) && $receiver=$forced;
      $to = $this->parseAddress($receiver);
      if (!$to) throw new InvalidArgumentException('Invalid argument $receiver: '.$receiver);

      if (!is_string($subject)) throw new IllegalTypeException('Illegal type of parameter $subject: '.getType($subject));
      if (!is_string($message)) throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));

      if ($headers === null)
         $headers = array();
      foreach ($headers as $key => $header) {
         if (!is_string($header)) throw new IllegalTypeException('Illegal parameter type in argument $headers[$key]: '.getType($header));
      }


      // Versand je nach Konfiguration verschieben (um z.B. Transaktionen nicht zu blockieren)
      if ($this->sendLater($sender, $receiver, $subject, $message, $headers))
         return;


      // ggf. Startzeitpunkt speichern
      if (self::$logDebug)
         $start = microTime(true);


      if (is_resource($this->connection))
         $this->logBuffer = null;      // reset log buffer if already connected

      if (!is_resource($this->connection))
         $this->connect();

      if ($this->config['auth_username'])
         $this->authenticate();


      // check for a custom 'Return-Path' header
      $returnPath = $from['address'];
      foreach ($headers as $key => $header) {
         $header = trim($header);
         if (strStartsWithI($header, 'return-path:')) {
            $result = $this->parseAddress(subStr($header, 12));
            if (!$result) throw new InvalidArgumentException('Invalid Return-Path header: '.$header);
            $returnPath = $result['address'];
            unset($headers[$key]);
         }
      }


      // init mail
      $this->writeData("MAIL FROM: <$returnPath>");
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 250)
         throw new RuntimeException("MAIL FROM: <$returnPath> command not accepted: ".$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);

      $this->writeData("RCPT TO: <$to[address]>");
      $response = $this->readResponse();     // TODO: macht der MTA ein DNS-Lookup, kann es in readResponse() zum Time-out kommen

      $this->parseResponse($response);
      if ($this->responseStatus != 250 && $this->responseStatus != 251)
         throw new RuntimeException("RCPT TO: <$to[address]> command not accepted: ".$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);

      $this->writeData('DATA');
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 354)
         throw new RuntimeException('DATA command not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);

      // TODO: zu lange Header umbrechen

      // needed headers
      $this->writeData('Date: '.date('r'));

      $from = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $from);
      $this->writeData("From: $from[name] <$from[address]>");

      $to = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $to);
      $this->writeData("To: $to[name] <$to[address]>");

      $subject = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $subject);
      $this->writeData("Subject: $subject");
      $this->writeData("X-Mailer: Microsoft Office Outlook 11");     // save us from Hotmail junk folder
      $this->writeData("X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.2180");


      // custom headers
      foreach ($headers as $header) {
         // TODO: Header syntaktisch überprüfen
         $header = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $header);
         $this->writeData($header);
      }
      $this->writeData('');


      $maxLineLength = 990;   // eigentlich 998, doch FastMail nimmt auch nur 990

      // mail body
      $message = str_replace(array("\r\n", "\r"), array("\n", "\n"), $message);
      $lines = explode("\n", $message);
      foreach ($lines as $line) {

         // break up long lines into several shorter ones
         $pieces = null;
         while (strLen($line) > $maxLineLength) {
            $pos = strRPos(subStr($line, 0, $maxLineLength), ' ');
            if (!$pos)
               $pos = $maxLineLength - 1;    // patch to fix DOS attack

            $pieces[] = subStr($line, 0, $pos);
            $line = subStr($line, $pos + 1);
         }
         $pieces[] = $line;

         foreach ($pieces as $line) {
            if (subStr($line, 0, 1) == '.')
               $line = '.'.$line;            // escape leading dots to avoid end marker confusion
            $this->writeData($line);
         }
      }

      // end marker
      $this->writeData('.');
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 250)
         throw new RuntimeException('Sent data not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);


      // ggf. Endzeitpunkt speichern
      if (self::$logDebug)
         $end = microTime(true);


      // Zu lang dauernden Versand loggen
      if (self::$logDebug) {
         $neededTime = round($end - $start, 4);
         if ($neededTime > self::$maxSendingTime)
            Logger::log(__METHOD__.'() to '.$to['address'].' took more than '.self::$maxSendingTime.' seconds: '.$neededTime, null, L_DEBUG, __CLASS__);
      }
   }


   /**
    * Verbindung resetten
    */
   public function reset() {
      if (!is_resource($this->connection))
         throw new RuntimeException('Cannot reset connection: Not connected');

      $this->writeData('RSET');
      $response = $this->readResponse();

      $this->parseResponse($response);
      if ($this->responseStatus != 250)
         throw new RuntimeException('RSET command not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);
   }


   /**
    * Verbindung trennen
    *
    * @param  bool $silent - ob die Verbindung still und ohne Exception geschlossen werden soll (default: FALSE)
    */
   public function disconnect($silent = false) {
      if (!is_resource($this->connection))
         return;

      if (!$silent) {
         $this->writeData('QUIT');
         $response = $this->readResponse();

         $this->parseResponse($response);
         if ($this->responseStatus != 221)
            throw new RuntimeException('QUIT command not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer);
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


   // TODO: SPF-Eintrag der Absenderdomain zum Testen umgehen
   /**
    * Return-Path: <user@domain.tld>
    * Received: from compute1.internal (compute1.internal [0.0.0.0])
    *     by store52m.internal (Cyrus v2.3.13-fmsvn17160) with LMTPA;
    *     Sun, 21 Dec 2008 07:18:19 -0500
    * Received: from mx3.domain.tld ([0.0.0.0])
    *   by compute1.internal (LMTPProxy); Sun, 21 Dec 2008 07:18:20 -0500
    * Received: from quad.domain.tld (domain.tld [0.0.0.0])
    *    by mx3.domain.tld (Postfix) with ESMTP id 02D70FE
    *    for <user@domain.tld>; Sun, 21 Dec 2008 07:18:19 -0500 (EST)
    * Received: (qmail 6101 invoked by uid 110); 21 Dec 2008 13:18:18 +0100
    * X-Remote-Delivered-To: 25-user@domain.tld
    * X-Spam-Checker-Version: SpamAssassin 3.2.4 (2008-01-01) on
    *    quad.domain.tld
    * X-Spam-Level:
    * X-Spam-Status: No, score=0.6 required=7.0 tests=MISSING_MID,RCVD_IN_PBL,
    *    RDNS_DYNAMIC autolearn=no version=3.2.4
    * Received: (qmail 6033 invoked from network); 21 Dec 2008 13:18:13 +0100
    * Received: from d0-0-0-0.cust.domain.tld (HELO device.localdomain) (0.0.0.0)
    *   by domain.tld with SMTP; 21 Dec 2008 13:18:13 +0100
    *
    * Received-SPF: softfail (domain.tld: transitioning SPF record at domain.tlddoes not designate 0.0.0.0 as permitted sender)
    *                                                                                                                      ^
    *                                                                                                                      |
    *                                                                                                    lokale Adresse (beim Testen zu Hause)
    * Date: Sun, 21 Dec 2008 13:18:13 +0100
    * From: local.domain.tld <user@domain.tld>
    * To: User Name <user@domain.tld>
    * Subject: *********************************** a User Name
    * Content-Type: text/plain; charset=iso-8859-1
    *
    * Hallo User Name,
    * ...
    */
}
