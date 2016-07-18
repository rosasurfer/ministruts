<?php
use rosasurfer\ministruts\core\StaticClass;

use rosasurfer\ministruts\exception\BaseException as RosasurferException;
use rosasurfer\ministruts\exception\IllegalTypeException;
use rosasurfer\ministruts\exception\InvalidArgumentException;
use rosasurfer\ministruts\exception\RuntimeException;

use function rosasurfer\echoPre;
use function rosasurfer\printPretty;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\ERROR_LOG_DEFAULT;
use const rosasurfer\ERROR_LOG_MAIL;
use const rosasurfer\L_DEBUG;
use const rosasurfer\L_ERROR;
use const rosasurfer\L_FATAL;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\L_WARN;
use const rosasurfer\LOCALHOST;
use const rosasurfer\NL;
use const rosasurfer\WINDOWS;


/**
 * Loggt eine Nachricht, wenn der mit der Nachricht angegebene Loglevel den konfigurierten Mindestloglevel erreicht oder
 * überschreitet.
 *
 * Konfiguration:
 * --------------
 * Der Loglevel kann für jede Klasse einzeln konfiguriert werden. Ohne Konfiguration gilt der Default-Loglevel L_NOTICE.
 * Standardmäßig wird die Logmessage am Bildschirm ausgegeben und per E-Mail verschickt.
 *
 *  Beispiel:
 *  ---------
 *  log.level.MyClassA = warn                   # Messages in MyClassA werden geloggt, wenn sie mindestens den Level L_WARN erreichen.
 *  log.level.MyClassB = debug                  # Messages in MyClassB werden geloggt, wenn sie mindestens den level L_DEBUG erreichen.
 *
 *  log.mail.receiver  = address1@domain.tld    # Logmessages werden an ein oder mehrere E-Mailempfänger verschickt (komma-getrennt).
 *
 *  log.sms.receiver   = +491234567,+441234567  # Logmessages per SMS gehen an ein oder mehrere Rufnummern (komma-getrennt, intern. Format).
 *  log.sms.loglevel   = error                  # Logmessages werden per SMS verschickt, wenn sie mindestens den Level L_ERROR erreichen.
 *
 *
 * TODO: Logger muß erweitert werden können
 */
class Logger extends StaticClass {


   private static /*bool    */ $print;                      // ob die Nachricht am Bildschirm angezeigt werden soll
   private static /*bool    */ $mail;                       // ob die Nachricht per E-Mail verschickt werden soll (alle Loglevel)
   private static /*string[]*/ $mailReceivers = [];         // E-Mailempfänger
   private static /*bool    */ $sms;                        // ob die Nachricht per SMS verschickt werden soll
   private static /*string[]*/ $smsReceivers  = [];         // SMS-Empfänger
   private static /*int     */ $smsLogLevel   = L_FATAL;    // notwendiger Loglevel für den SMS-Versand
   private static /*string[]*/ $smsOptions    = [];         // SMS-Konfiguration


   // Beschreibungen der verfügbaren Loglevel
   private static $logLevels = array(L_DEBUG  => '[Debug]' ,
                                     L_INFO   => '[Info]'  ,
                                     L_NOTICE => '[Notice]',
                                     L_WARN   => '[Warn]'  ,
                                     L_ERROR  => '[Error]' ,
                                     L_FATAL  => '[Fatal]' ,
   );


   /**
    * Initialize the static config properties.
    */
   public static function init() {
      static $initialized = false;
      if ($initialized)
         return;

      // Standardmäßig ist die Ausgabe am Bildschirm bei lokalem Zugriff an und bei Remote-Zugriff aus, es sei denn,
      // es ist ausdrücklich etwas anderes konfiguriert.
      self::$print = CLI || LOCALHOST || (bool) ini_get('display_errors');


      // Der E-Mailversand ist aktiv, wenn in der Projektkonfiguration mindestens eine E-Mailadresse angegeben wurde.
      $receivers = [];
      foreach (explode(',', Config::get('log.mail.receiver', '')) as $receiver) {
         if ($receiver=trim($receiver))
            $receivers[] = $receiver;                       // TODO: Adressformat validieren
      }
      if ($receivers) {
         if ($forced=Config::get('mail.address.forced-receiver', null)) {
            $receivers = [];                                // Um Fehler zu vermeiden, wird zum Mailen keine Klasse, sondern
            foreach (explode(',', $forced) as $receiver) {  // die PHP-interne Funktion mail() benutzt. Die Konfiguration muß
               if ($receiver=trim($receiver))               // deshalb hier selbst auf "mail.address.forced-receiver" geprüft
                  $receivers[] = $receiver;                 // werden.
            }
         }
      }
      self::$mail          = (bool) $receivers;
      self::$mailReceivers =        $receivers;


      // Der SMS-Versand ist aktiv, wenn in der Projektkonfiguration mindestens eine Rufnummer und ein SMS-Loglevel angegeben wurden.
      self::$sms          = false;
      self::$smsReceivers = array();
      $receivers = Config::get('log.sms.receiver', null);
      foreach (explode(',', $receivers) as $receiver) {
         if ($receiver=trim($receiver))
            self::$smsReceivers[] = $receiver;
      }
      $logLevel = Config::get('log.sms.loglevel', null);
      if (strLen($logLevel) > 0) {
         if (defined('L_'.strToUpper($logLevel))) self::$smsLogLevel = constant('L_'.strToUpper($logLevel));
         else                                     self::$smsLogLevel = null;
      }
      $options = Config::get('sms.clickatell', null);
      if (!empty($options['username']) && !empty($options['password']) && !empty($options['api_id']))
         self::$smsOptions = $options;
      self::$sms = self::$smsReceivers && self::$smsLogLevel && self::$smsOptions;

      $initialized = true;
   }


   /**
    * Gibt den Loglevel der angegebenen Klasse zurück.
    *
    * @param  string $class - Klassenname
    *
    * @return int - Loglevel
    */
   public static function getLogLevel($class) {
      static $logLevels = null;

      if ($logLevels === null) {
         // Die konfigurierten Loglevel werden einmal eingelesen und gecacht. Nachfolgende Logger-Aufrufe verwenden nur noch den Cache.
         $logLevels = Config ::get('log.level', array());
         if (is_string($logLevels))
            $logLevels = array('' => $logLevels);

         foreach ($logLevels as $className => $level) {
            if (!is_string($level)) throw new IllegalTypeException('Illegal log level type for class '.$className.': '.getType($level));
            if     ($level == '')                     $logLevels[$className] = System::DEFAULT_LOGLEVEL;
            elseif (defined('L_'.strToUpper($level))) $logLevels[$className] = constant('L_'.strToUpper($level));
            else                    throw new InvalidArgumentException('Invalid log level for class '.$className.': '.$level);
         }
      }

      // Loglevel abfragen
      if (isSet($logLevels[$class]))
         return $logLevels[$class];

      return System::DEFAULT_LOGLEVEL;
   }


   /**
    * Globaler Handler für nicht abgefangene Exceptions. Die Exception wird mit dem Loglevel L_FATAL geloggt und das Script beendet.
    * Der Aufruf kann automatisch (durch installierten Errorhandler) oder manuell (durch Code, der selbst keine Exceptions werfen darf)
    * erfolgen.
    *
    * NOTE: PHP bricht das Script nach Aufruf dieses Handlers automatisch ab.
    *
    * @param  Exception $exception - die zu behandelnde Exception
    */
   public static function handleException(\Exception $exception) {
      try {
         // 1. Fehlerdaten ermitteln
         $type       = $exception instanceof \ErrorException ? 'Unexpected':'Unhandled';
         $exMessage  = trim(DebugTools::getBetterMessage($exception));
         $indent     = ' ';
         $traceStr   = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr  .= DebugTools::getBetterTraceAsString($exception, $indent);
         $file       = $exception->getFile();
         $line       = $exception->getLine();
         $cliMessage = '[FATAL] '.$type.' '.$exMessage.NL.$indent.'in '.$file.' on line '.$line.NL;

         // 2. Exception anzeigen
         if (self::$print) {
            if (CLI) {
               echoPre($cliMessage.NL.$traceStr.NL);
            }
            else {
               echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>[FATAL]</b> '.$type.' '.nl2br(htmlSpecialChars($exMessage, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
               echo '<br>'.printPretty($traceStr, true);
               echo '<br></div>'.NL;
            }
         }
         else {
            echoPre('application error');
         }

         // 3. Exception an die konfigurierten Adressen mailen
         if (self::$mail) {
            $mailMsg = $cliMessage.NL.$traceStr;

            if ($request=Request::me()) {
               $session = $request->isSession() ? print_r($_SESSION, true) : null;

               $ip   = $_SERVER['REMOTE_ADDR'];
               $host = getHostByAddr($ip);
               if ($host != $ip)
                  $ip = $host.' ('.$ip.')';

               $mailMsg .= NL
                        .  NL
                        . 'Request:'                                                                .NL
                        . '--------'                                                                .NL
                        .  $request                                                                 .NL
                        .                                                                            NL
                        . 'Session: '.($session ? NL.'--------'.NL.$session.NL : '  - no session -').NL
                        . 'Host: '.$ip                                                              .NL
                        . 'Time: '.date('Y-m-d H:i:s')                                              .NL;
            }
            else {
               $mailMsg .= NL.NL.'Shell:'.NL.'------'.NL.print_r($_SERVER, true).NL.NL;
            }
            $subject = 'PHP [FATAL] Unhandled Exception at '.($request ? $request->getHostname():'').$_SERVER['PHP_SELF'];
            self::mail_log($subject, $mailMsg);
         }


         // (4) Logmessage ins Error-Log schreiben, wenn sie nicht per Mail rausging
         else {
            self::error_log('PHP '.$cliMessage);
         }


         // (5) Logmessage per SMS verschicken
         if (self::$sms) {
            self::sms_log($cliMessage);
         }
      }
      catch (\Exception $secondary) {
         $file = $exception->getFile();
         $line = $exception->getLine();
         $msg  = 'PHP primary '.(string)$exception.' in '.$file.' on line '.$line;
         self::$print && echoPre($msg);
         self::error_log($msg);

         $file = $secondary->getFile();
         $line = $secondary->getLine();
         $msg  = 'PHP secondary '.(string)$secondary.' in '.$file.' on line '.$line;
         self::$print && echoPre($msg);
         self::error_log($msg);
      }
      finally {
         exit(1);                               // exit und signal the error
      }
   }


   /**
    *
    */
   public static function warn($arg1=null, $arg2=null, $arg3=null) {
      $argc = func_num_args();

      if ($argc == 2) return self::log($arg1,        L_WARN, $arg2);
      if ($argc == 3) return self::log($arg1, $arg2, L_WARN, $arg3);

      throw new InvalidArgumentException('Invalid number of arguments: '.$argc);
   }


   /**
    * Überladene Methode.  Loggt eine Message und/oder eine Exception.
    *
    * Signaturen:
    * -----------
    * Logger::log($message,             $level, $class)
    * Logger::log(          $exception, $level, $class)
    * Logger::log($message, $exception, $level, $class)
    */
   public static function log($arg1=null, $arg2=null, $arg3=null, $arg4=null) {
      $argc    = func_num_args();
      $message = $exception = $level = $class = null;

      if ($argc == 3) {
         $message = $exception = $arg1;
         $level   = $arg2;
         $class   = $arg3;
      }
      elseif ($argc == 4) {
         $message   = $arg1;
         $exception = $arg2;
         $level     = $arg3;
         $class     = $arg4;
      }
      else throw new InvalidArgumentException('Invalid number of arguments: '.$argc);

      if (!is_int($level))    throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));
      if (!is_string($class)) throw new IllegalTypeException('Illegal type of parameter $class: '.getType($class));

      // was der jeweilige Loglevel nicht abdeckt, wird ignoriert
      if ($level < self:: getLogLevel($class))
         return;

      // Aufruf mit drei Argumenten
      if ($argc == 3) {
         if (is_null($message) || is_string($message))
            return self:: log_1($message, null, $level);       // Logger::log($message  , $level, $class)
         if ($exception instanceof \Exception)
            return self:: log_1(null, $exception, $level);     // Logger::log($exception, $level, $class)
         throw new IllegalTypeException('Illegal type of first parameter: '.getType($arg1));
      }

      // Aufruf mit vier Argumenten
      if (!is_null($message) && !is_string($message))                throw new IllegalTypeException('Illegal type of parameter $message: '.(is_object($message) ? get_class($message) : getType($message)));
      if (!is_null($exception) && !$exception instanceof \Exception) throw new IllegalTypeException('Illegal type of parameter $exception: '.(is_object($exception) ? get_class($exception) : getType($exception)));

      return self:: log_1($message, $exception, $level);       // Logger::log($message, $exception, $level, $class)
   }


   /**
    * Loggt eine Message und/oder eine Exception.  Je nach aktueller Laufzeitumgebung wird die Logmeldung
    * entweder am Bildschirm angezeigt, an die konfigurierten E-Mailadressen gemailt oder ins PHP-Errorlog
    * geschrieben.
    *
    * @param  string    $message   - zu loggende Message
    * @param  Exception $exception - zu loggende Exception
    * @param  int       $level     - zu loggender Loglevel
    */
   public static function log_1($message, \Exception $exception=null, $level) {
      $plainMessage = null;

      try {
         if (!isSet(self::$logLevels[$level])) throw new InvalidArgumentException('Invalid log level: '.$level);

         // 1. Logdaten ermitteln
         $exMessage = null;
         if ($exception) {
            $message  .= ($message === null) ? (string) $exception : ' ('.get_class($exception).')';
            $exMessage = ($exception instanceof RosasurferException) ? (string) $exception : get_class($exception).': '.$exception->getMessage();;
         }

         if ($exception instanceof RosasurferException) {
            $trace = $exception->getStackTrace();
            $file  = $exception->getFile();
            $line  = $exception->getLine();
            $trace = 'Stacktrace:'.NL.'-----------'.NL.$exception->printStackTrace(true);
         }
         else {
            $trace = $exception ? $exception->getTrace() : debug_backtrace();
            $trace = RosasurferException::transformToJavaStackTrace($trace);
            // die Frames des Loggers selbst können weg
            if ($trace[0]['class'].$trace[0]['type'].$trace[0]['function'] == 'Logger::log_1') array_shift($trace);
            if ($trace[0]['class'].$trace[0]['type'].$trace[0]['function'] == 'Logger::log'  ) array_shift($trace);
            if ($trace[0]['class'].$trace[0]['type'].$trace[0]['function'] == 'Logger::warn' ) array_shift($trace);

            foreach ($trace as $f) {            // ersten Frame mit __FILE__ suchen
               if (isSet($f['file'])) {
                  $file = $f['file'];
                  $line = $f['line'];
                  break;
               }
            }
            $trace = 'Stacktrace:'.NL.'-----------'.NL.RosasurferException::formatStackTrace($trace);
            // TODO: vernestelte, einfache Exceptions geben fehlerhaften Stacktrace zurück
         }
         $plainMessage = self::$logLevels[$level].': '.$message.NL.'in '.$file.' on line '.$line.NL;


         // 2. Logmessage anzeigen
         if (self::$print) {
            if (!CLI) {
               echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.self::$logLevels[$level].'</b>: '.nl2br(htmlSpecialChars($message, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
               if ($exception)
                  echo '<br>'.htmlSpecialChars($exMessage, ENT_QUOTES).'<br>';
               if ($trace)
                  echo '<br>'.printPretty($trace, true).'<br>';
               echo '</div>';
               if ($request=Request ::me())
                  echo '<br>'.printPretty('Request:'.NL.'--------'.NL.$request, true).'<br></div><br>';
            }
            else {
               echo $plainMessage.($exception ? NL.$exMessage.NL:'').NL.($trace ? $trace.NL:'');
            }
            ob_get_level() && ob_flush();
         }


         // 3. Logmessage an die konfigurierten Adressen mailen
         if (self::$mail) {
            $mailMsg = $plainMessage.($exception ? NL.NL.$exMessage.NL:'').NL.NL.$trace;

            if ($request=Request ::me()) {
               $session = $request->isSession() ? print_r($_SESSION, true) : null;

               $ip   = $_SERVER['REMOTE_ADDR'];
               $host = getHostByAddr($ip);
               if ($host != $ip)
                  $ip = $host.' ('.$ip.')';

               $mailMsg .= NL.NL.NL.'Request:'.NL.'--------'.NL.$request.NL.NL.NL
                        .  'Session: '.($session ? NL.'--------'.NL.$session.NL.NL.NL : '  - no session -'.NL)
                        .  'Host: '.$ip.NL
                        .  'Time: '.date('Y-m-d H:i:s').NL;
            }
            else {
               $mailMsg .= NL.NL.NL.'Shell:'.NL.'------'.NL.print_r($_SERVER, true).NL.NL.NL;
            }
            $subject = 'PHP: '.self::$logLevels[$level].' at '.($request ? $request->getHostname():'').$_SERVER['PHP_SELF'];
            self ::mail_log($subject, $mailMsg);
         }


         // (4) Logmessage ins Error-Log schreiben, wenn sie nicht per Mail rausging
         else {
            self::error_log('PHP '.$plainMessage);
         }


         // (5) Logmessage per SMS verschicken, wenn der konfigurierte SMS-Loglevel erreicht ist
         if (self::$sms && $level >= self::$smsLogLevel) {
            self ::sms_log($plainMessage.($exception ? NL.$exMessage:''));
         }
      }
      catch (\Exception $ex) {
         $msg = 'PHP (0) '.$plainMessage ? $plainMessage:$message;
         self::$print && echoPre($msg);
         self::error_log($msg);
         throw $ex;
      }
   }


   /**
    * Schreibt eine Logmessage ins PHP-interne Error-Log.
    *
    * @param  string $message - zu loggende Message
    */
   private static function error_log($message) {
      $message = str_replace(["\r\n", "\n"], ' ', $message);   // Zeilenumbrüche entfernen
      $message = str_replace(chr(0), "*\x00*", $message);      // NUL-Bytes ersetzen (zerschießen Logfile)

      /**
       * ini_get('error_log')
       *
       * Name of the file where script errors should be logged. If the special value "syslog" is used, errors are sent
       * to the system logger instead. On Unix, this means syslog(3) and on Windows NT it means the event log.
       * If this directive is not set, errors are sent to the SAPI error logger. For example, it is an error log in Apache
       * or STDERR in CLI.
       */
      if (CLI && !ini_get('error_log')) {                      // TODO: Augabe auf STDERR nur in interaktiven Terminals
      }                                                        //       unterdrücken.
      else {
         error_log($message, ERROR_LOG_DEFAULT);
      }
   }


   /**
    * Verschickt eine Logmessage per E-Mail.
    *
    * @param  string $subject - Subject der E-Mail
    * @param  string $message - zu loggende Message
    */
   private static function mail_log($subject, $message) {
      $message = str_replace("\r\n", "\n", $message);                // Linux: Unix-Zeilenumbrüche
      if (WINDOWS)
         $message = str_replace("\n", "\r\n", $message);             // Windows: Windows-Zeilenumbrüche
      $message = str_replace(chr(0), "*\x00*", $message);            // NUL-Characters ersetzen (zerschießen E-Mail)

      foreach (self::$mailReceivers as $receiver) {
         // TODO: durch mail() ersetzen, damit Subject-Header von PHP nicht doppelt geschrieben wird
         error_log($message, ERROR_LOG_MAIL, $receiver, 'Subject: '.$subject);
      }
   }


   /**
    * Verschickt eine Logmessage per SMS an die konfigurierten Rufnummern.
    *
    * @param  string $message - zu loggende Message
    */
   private static function sms_log($message) {
      if (empty($message))
         return;
      $message  = str_replace("\r\n", "\n", $message);                     // Unix-Zeilenumbrüche
      $message  = subStr($message, 0, 150);                                // Länge der Message auf eine SMS begrenzen

      $username = self::$smsOptions['username'];
      $password = self::$smsOptions['password'];
      $api_id   = self::$smsOptions['api_id'  ];

      foreach (self::$smsReceivers as $receiver) {
         if (strStartsWith($receiver, '+' )) $receiver = subStr($receiver, 1);
         if (strStartsWith($receiver, '00')) $receiver = subStr($receiver, 2);
         if (!ctype_digit($receiver)) throw new InvalidArgumentException('Invalid argument $receiver: "'.$receiver.'"');

         $url = 'https://api.clickatell.com/http/sendmsg?user='.$username.'&password='.$password.'&api_id='.$api_id.'&to='.$receiver.'&text='.urlEncode($message);

         // TODO: CURL-Abhängigkeit möglichst durch interne URL-Funktionen ersetzen

         // HTTP-Request erzeugen und ausführen
         $request  = HttpRequest ::create()->setUrl($url);
         $options[CURLOPT_SSL_VERIFYPEER] = false;                            // das SSL-Zertifikat kann nicht überprüfbar oder ungültig sein
         $response = CurlHttpClient ::create($options)->send($request);
         $status   = $response->getStatus();
         $content  = $response->getContent();
         if ($status != 200) throw new RuntimeException('Unexpected HTTP status code from api.clickatell.com: '.$status.' ('.HttpResponse ::$sc[$status].')');

         // TODO: SMS ggf. auf zwei message parts verlängern
         if (striPos($content, 'ERR: 113') === 0)                             // ERR: 113, Max message parts exceeded
            return self ::sms_log(subStr($message, 0, strLen($message)-10));  // mit kürzerer Message wiederholen
      }
   }
}
Logger::init();
