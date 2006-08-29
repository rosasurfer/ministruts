<?
/**
 * Mailer
 *
 *
 *
 *
 * Example:
 * --------
 * $mailer =& new Mailer();
 * $mailer->sendMail($from, $to, $subject, $message, $headers);
 */
class Mailer {

   var $config = array('host'    => null,       // SMTP server host name
                       'port'    => null,       // SMTP server port
                       'auth'    => false,      // use authentification ?
                       'user'    => null,       // username
                       'pass'    => null,       // password
                       'timeout' => 60);        // default socket timeout
   var $hostname;
   var $connection     = null;
   var $responseStatus = 0;
   var $response       = null;

   var $debug     = false;
   var $logFile   = 'Mailer.log';
   var $logBuffer = null;


   /**
    * Default constructor
    */
   function Mailer() {
      $this->debug = @$GLOBALS['debug'];
      global $smtp, $smtp_port, $smtp_use_auth, $smtp_user, $smtp_pass;

      $this->config['host'] = isSet($smtp)      ? $smtp     : ini_get('SMTP');
      $this->config['port'] = isSet($smtp_port) ? $smtp_port: ini_get('smtp_port');
      $this->config['auth'] = $smtp_use_auth;
      if ($smtp_use_auth) {
         $this->config['user'] = $smtp_user;
         $this->config['pass'] = $smtp_pass;
      }

      // get the hostname
      if (isSet($_SERVER['SERVER_NAME'])) {
         $hostname = $_SERVER['SERVER_NAME'];
      }
      elseif (!$hostname = php_uName('n')) {
         $hostname = 'localhost';
      }
      if (strPos($hostname, '.') === false) {
         $hostname .= '.localdomain';              // hostname must contain of more than one period (see RFC 2821)
      }
      $this->hostname = strToLower($hostname);

      $this->log("\n----==:[ New Mailer instance - smtp://".$this->config['host'].':'.$this->config['port'].($this->config['auth'] ? ' with authentification':'')."]:==----");
   }

   /**
    * Verbindung herstellen
    */
   function connect() {
      $this->log("\n----==::  Connecting  :==----");
      $connection = fSockOpen('tcp://'.$this->config['host'],
                              $this->config['port'],
                              $errorCode,
                              $errorMsg,
                              $this->config['timeout']) or trigger_error("Could not open socket: $errorMsg (error $errorCode)", E_USER_WARNING);
      if (!$connection)
         return false;

      $data = stream_get_meta_data($connection);
      if ($data['timed_out']) {
         trigger_error('Timeout on socket connection', E_USER_WARNING);
         return false;
      }
      socket_set_timeout($connection, $this->config['timeout']);
      $this->connection = $connection;

      if ($this->readResponse() === false)                                       // read greeting
         return false;


      // init connection
      $this->writeData('EHLO '.$this->hostname);                                 // extended Hello first...
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus != 250) {
         $this->writeData('HELO '.$this->hostname);                              // normal Hello if extended fails...
         $response = $this->readResponse();

         $this->checkResponse($response);
         if ($this->responseStatus != 250) {
            trigger_error('HELO command not accepted: '.$this->responseStatus.' '.$this->response, E_USER_WARNING);
            return false;
         }
      }
      return true;
   }

   /**
    * Authentifizierung
    */
   function authenticate() {
      if (!$this->connection) {
         trigger_error('Cannot authenticate: Not connected', E_USER_WARNING);
         return false;
      }

      // init authentication
      $this->writeData('AUTH LOGIN');
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus == 503) {
         return true;                                                            // already authenticated
      }
      if ($this->responseStatus != 334) {
         trigger_error('AUTH LOGIN command not supported: '.$this->responseStatus.' '.$this->response, E_USER_WARNING);
         return false;
      }

      // send user
      $this->writeData(base64_encode($this->config['user']));
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus != 334) {
         trigger_error('Username '.$this->config['user'].' not accepted'.$this->responseStatus.' '.$this->response, E_USER_WARNING);
         return false;
      }

      // send pass
      $this->writeData(base64_encode($this->config['pass']));
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus != 235) {
         trigger_error('Login failed for username '.$this->config['user'].': '.$this->responseStatus.' '.$this->response, E_USER_WARNING);
         return false;
      }
      return true;
   }

   /**
    * Mail verschicken.
    */
   function sendMail($from, $to, $subject, $message, $headers) {
      $this->connection && $this->logBuffer = null;                  // reset log buffer if already connected

      if (!$this->connection     && !$this->connect())      return false;
      if ( $this->config['auth'] && !$this->authenticate()) return false;

      // init mail
      $this->log("\n----==::  Sending new mail  [from: $from] [to: $to] [subject: $subject]  :==----");
      $returnPath = "<$from>";
      if (is_array($headers)) {
         $tmp = array();
         foreach ($headers as $header) {
            $header = trim($header);
            if (strPos(strToLower($header), 'return-path:') === 0) { // is a custom 'Return-Path' header given ?
               $returnPath = trim(subStr($header, 12));
            }
            else {
               $tmp[] = $header;
            }
         }
         $headers = $tmp;
      }

      $this->writeData("MAIL FROM:$returnPath");
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus != 250) {
         trigger_error("MAIL FROM:$returnPath command not accepted: ".$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer, E_USER_WARNING);
         return false;
      }

      $this->writeData("RCPT TO:<$to>");
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus != 250 && $this->responseStatus != 251) {
         trigger_error("RCPT TO:<$to> command not accepted: ".$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer, E_USER_WARNING);
         return false;
      }

      // sent the mail data
      $this->writeData('DATA');                                      // init
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus != 354) {
         trigger_error('DATA command not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer, E_USER_WARNING);
         return false;
      }

      $this->writeData('Date: '.date('r'));                          // needed headers
         $from = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $from);
      $this->writeData("From: Customer Support <$from>");
         $to = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $to);
      $this->writeData("To: $to");
         $subject = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $subject);
      $this->writeData("Subject: $subject");
      $this->writeData("X-Mailer: Microsoft Office Outlook 11");     // save us from Hotmail junk folder ;-)
      $this->writeData("X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.2180");

      if (is_array($headers)) {                                      // custom headers
         foreach ($headers as $header) {
            if (isSet($header) && $header != '') {
               $header = preg_replace('~([\xA0-\xFF])~e', '"=?ISO-8859-1?Q?=".strToUpper(decHex(ord("$1")))."?="', $header);
               $this->writeData($header);
            }
         }
      }
      $this->writeData('');

      $message = str_replace("\r\n", "\n", $message);
      $message = str_replace("\n", "\r\n", $message);
      $this->writeData($message);                                    // body

      $this->writeData('.');                                         // end
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus != 250) {
         trigger_error('Sent data not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer, E_USER_WARNING);
         return false;
      }
      return true;
   }

   /**
    * Verbindung resetten
    */
   function reset() {
      if (!$this->connection) {
         trigger_error('Cannot reset connection: Not connected', E_USER_WARNING);
         return false;
      }

      $this->writeData('RSET');
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus != 250) {
         trigger_error('RSET command not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer, E_USER_WARNING);
         return false;
      }
      return true;
   }

   /**
    * Verbindung trennen
    */
   function disconnect() {
      if (!$this->connection) return false;

      $this->writeData('QUIT');
      $response = $this->readResponse();

      $this->checkResponse($response);
      if ($this->responseStatus != 221) {
         trigger_error('QUIT command not accepted: '.$this->responseStatus.' '.$this->response."\n\nTransfer log:\n-------------\n".$this->logBuffer, E_USER_WARNING);
         return false;
      }

      fClose($this->connection);
      $this->connection = null;

      $this->debug && fClose($this->logFile);
      return true;
   }


   // ********************************************
   // private functions, for internal use only ...
   // ********************************************

   /**
    * Antwort des Servers lesen
    */
   function readResponse() {
      $lines = null;
      while (trim($line = fGets($this->connection)) != '') {
         $lines .= $line;
         if (subStr($line, 3, 1) == ' ')
            break;
      }
      $data = stream_get_meta_data($this->connection);
      if ($data['timed_out']) {
         trigger_error('Timeout on socket connection', E_USER_WARNING);
         return false;
      }

      $this->logResponse($lines);
      return $lines;
   }

   /**
    * Daten in die Socketverbindung schreiben
    */
   function writeData($data) {
      $count = fWrite($this->connection, $data."\r\n", strLen($data)+2);

      if ($count != strLen($data)+2) {
         trigger_error('Error writing to socket, length of data: '.(strLen($data)+2).', bytes written: '.$count."\ndata: ".$data."\n\nTransfer log:\n-------------\n".$this->logBuffer, E_USER_ERROR);
      }

      $this->logSentData($data);
   }

   /**
    * Response parsen
    */
   function checkResponse($response) {
      $response = trim($response);
      $this->responseStatus = intVal(subStr($response, 0, 3));
      $this->response = subStr($response, 4);
   }

   /**
    * Message loggen
    */
   function log($data) {
      $data .= "\n";

      if ($this->debug) {
         is_resource($this->logFile) || ($this->logFile = fOpen(dirName(__FILE__).'/'.$this->logFile, 'ab'));
         fWrite($this->logFile, $data, strLen($data));
      }
   }

   /**
    * Gesendete Daten loggen
    */
   function logSentData($data) {
      $data = preg_replace('/^(.*)/m', " -> $1", $data)."\n";
      $this->logBuffer .= $data;

      if ($this->debug) {
         is_resource($this->logFile) || ($this->logFile = fOpen(dirName(__FILE__).'/'.$this->logFile, 'ab'));
         fWrite($this->logFile, $data, strLen($data));
      }
   }

   /**
    * Empfangene Daten loggen
    */
   function logResponse($data) {
      $this->logBuffer .= $data;

      if ($this->debug) {
         is_resource($this->logFile) || ($this->logFile = fOpen(dirName(__FILE__).'/'.$this->logFile, 'ab'));
         fWrite($this->logFile, $data, strLen($data));
      }
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
