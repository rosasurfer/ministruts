<?php
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
 *  log.level.MyClassA = warn                # Messages in MyClassA werden geloggt, wenn sie mindestens den Level L_WARN erreichen.
 *  log.level.MyClassB = debug               # Messages in MyClassB werden geloggt, wenn sie mindestens den level L_DEBUG erreichen.
 *
 *  log.mail.receiver = address1@domain.tld,address2@domain.tld
 *                                           # Logmessages werden an ein oder mehrere E-Mailempfänger verschickt.
 *
 *  log.sms.receiver = +41123456,+417890123  # Logmessages per SMS gehen an ein oder mehrere Rufnummern (internationales Format).
 *  log.sms.loglevel = error                 # Logmessages werden per SMS verschickt, wenn sie mindestens den Level L_ERROR erreichen.
 *
 *
 * TODO: Logger muß erweitert werden können
 */
class Logger extends StaticClass {
   /**
    * NOTE:
    * Diese Klasse muß möglichst wenige externe Abhängigkeiten haben, um das Auftreten weiterer Fehler während der
    * Fehlerverarbeitung möglichst zu verhindern.
    */

   // Default-Konfiguration (kann angepaßt werden, siehe Klassenbeschreibung)
   private static /*bool    */ $print         = null;       // ob die Nachricht am Bildschirm angezeigt werden soll
   private static /*bool    */ $mail;                       // ob die Nachricht per E-Mail verschickt werden soll (alle Loglevel)
   private static /*string[]*/ $mailReceivers = array();    // E-Mailempfänger
   private static /*bool    */ $sms;                        // ob die Nachricht per SMS verschickt werden soll
   private static /*string[]*/ $smsReceivers  = array();    // SMS-Empfänger
   private static /*int     */ $smsLogLevel   = L_FATAL;    // zusätzlicher Loglevel, der den SMS-Versand aktiviert



   // Default-Loglevel (kann angepaßt werden, siehe Klassenbeschreibung)
   const DEFAULT_LOGLEVEL = L_NOTICE;


   // Beschreibungen der verfügbaren Loglevel
   private static $logLevels = array(L_DEBUG  => '[Debug]' ,
                                     L_INFO   => '[Info]'  ,
                                     L_NOTICE => '[Notice]',
                                     L_WARN   => '[Warn]'  ,
                                     L_ERROR  => '[Error]' ,
                                     L_FATAL  => '[Fatal]' ,
   );


   /**
    * Initialisiert die statischen Klassenvariablen.
    */
   private static function init() {
      if (!is_null(self::$print))
         return;

      // Standardmäßig ist die Ausgabe am Bildschirm bei lokalem Zugriff an und bei Remote-Zugriff aus, es sei denn,
      // in der php.ini ist ausdrücklich etwas anderes konfiguriert.
      self::$print = !isSet($_SERVER['REQUEST_METHOD'])                                            // Konsole
                  || (isSet($_SERVER['REMOTE_ADDR'   ]) && $_SERVER['REMOTE_ADDR']=='127.0.0.1')   // Webrequest vom lokalen Rechner
                  || (bool) ini_get('display_errors');


      // Der E-Mailversand ist aktiv, wenn in der Projektkonfiguration mindestens eine E-Mailadresse angegeben wurde.
      self::$mail          = false;
      self::$mailReceivers = array();
      $receivers = Config ::get('mail.address.forced-receiver', '');    // Zum mailen wird keine Klasse, sondern die PHP-interne
      if (strLen($receivers) == 0)                                      // mail()-Funktion benutzt. Die Konfiguration muß deshalb hier
         $receivers = Config ::get('log.mail.receiver', '');            // selbst auf "mail.address.forced-receiver" geprüft werden.
      foreach (explode(',', $receivers) as $receiver) {
         // TODO: Adressformat validieren
         if ($receiver=trim($receiver))
            self::$mailReceivers[] = $receiver;
      }
      self::$mail = (bool) self::$mailReceivers;


      // Der SMS-Versand ist aktiv, wenn in der Projektkonfiguration mindestens eine Rufnummer und ein SMS-Loglevel angegeben wurden.
      self::$sms          = false;
      self::$smsReceivers = array();
      $receivers = Config ::get('log.sms.receiver', null);
      foreach (explode(',', $receivers) as $receiver) {
         if ($receiver=trim($receiver))
            self::$smsReceivers[] = $receiver;
      }
      $logLevel = Config ::get('log.sms.loglevel', null);
      if (strLen($logLevel) > 0) {
         if (defined('L_'.strToUpper($logLevel))) self::$smsLogLevel = constant('L_'.strToUpper($logLevel));
         else                                     self::$smsLogLevel = null;
      }
      self::$sms = (self::$smsReceivers && self::$smsLogLevel);
   }


   /**
    * Gibt den Loglevel der angegebenen Klasse zurück.
    *
    * @param string $class - Klassenname
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
            if     ($level == '')                     $logLevels[$className] = self:: DEFAULT_LOGLEVEL;
            elseif (defined('L_'.strToUpper($level))) $logLevels[$className] = constant('L_'.strToUpper($level));
            else                    throw new plInvalidArgumentException('Invalid log level for class '.$className.': '.$level);
         }
      }

      // Loglevel abfragen
      if (isSet($logLevels[$class]))
         return $logLevels[$class];

      return self:: DEFAULT_LOGLEVEL;
   }


   /**
    * Gibt den angegebenen Errorlevel in lesbarer Form zurück.
    *
    * @param int $level - Errorlevel, ohne Angabe wird der aktuellen Errorlevel des laufenden Scriptes
    *                     ausgewertet.
    * @return string
    */
   public static function getErrorLevelAsString($level=null) {
      if (func_num_args() && $level!==(int)$level) throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));

      $levels = array();
      if (!$level)
         $level = error_reporting();

      if ($level & E_ERROR            ) $levels[] = 'E_ERROR';
      if ($level & E_WARNING          ) $levels[] = 'E_WARNING';
      if ($level & E_PARSE            ) $levels[] = 'E_PARSE';
      if ($level & E_NOTICE           ) $levels[] = 'E_NOTICE';
      if ($level & E_DEPRECATED       ) $levels[] = 'E_DEPRECATED';
      if ($level & E_CORE_ERROR       ) $levels[] = 'E_CORE_ERROR';
      if ($level & E_CORE_WARNING     ) $levels[] = 'E_CORE_WARNING';
      if ($level & E_COMPILE_ERROR    ) $levels[] = 'E_COMPILE_ERROR';
      if ($level & E_COMPILE_WARNING  ) $levels[] = 'E_COMPILE_WARNING';
      if ($level & E_USER_ERROR       ) $levels[] = 'E_USER_ERROR';
      if ($level & E_USER_WARNING     ) $levels[] = 'E_USER_WARNING';
      if ($level & E_USER_NOTICE      ) $levels[] = 'E_USER_NOTICE';
      if ($level & E_USER_DEPRECATED  ) $levels[] = 'E_USER_DEPRECATED';
      if ($level & E_RECOVERABLE_ERROR) $levels[] = 'E_RECOVERABLE_ERROR';
      if ($level & E_ALL              ) $levels[] = 'E_ALL';
      if ($level & E_STRICT           ) $levels[] = 'E_STRICT';

      return join(' | ', $levels).' ('.$level.')';
   }


   /**
    * Globaler Handler für herkömmliche PHP-Fehler. Die Fehler werden in einer PHPErrorException
    * gekapselt und je nach Error-Level behandelt.  E_USER_NOTICE und E_USER_WARNING werden nur
    * geloggt (kein Scriptabbruch).
    *
    * @param int     $level   - Error-Level
    * @param string  $message - Error-Message
    * @param string  $file    - Datei, in der der Fehler auftrat
    * @param int     $line    - Zeile der Datei, in der der Fehler auftrat
    * @param mixed[] $context - aktive Symboltabelle des Punktes, an dem der Fehler auftrat
    *                           (An array that points to the active symbol table at the point the error occurred. In other words, $context will contain an array
    *                            of every variable that existed in the scope the error was triggered in. User error handler must not modify error context.)
    *
    * @return bool - TRUE,  wenn der Fehler erfolgreich behandelt wurde, FALSE, wenn der Fehler
    *                weitergereicht werden soll, als wenn der Errorhandler nicht registriert wäre
    *
    * NOTE: The error handler must return FALSE to populate $php_errormsg.
    */
   public static function handleError($level, $message, $file, $line, array $context) {
      //echoPre(__METHOD__.'(): '.self::$logLevels[$level].' '.$message.', $file: '.$file.', $line: '.$line);

      // absichtlich unterdrückte und vom aktuellen Errorlevel nicht abgedeckte Fehler ignorieren
      $error_reporting = error_reporting();

      if ($error_reporting == 0)                 return false;    // 0: @-Operator (see NOTE)
      if (($error_reporting & $level) != $level) return true;


      /**
       * PHP v5.3 bug: An error triggered at compile-time disables __autoload(), and spl_autoload() at the same time.
       *               Won't be fixed for PHP5.3 as it may cause lots of other problems.
       *
       * @see https://bugs.php.net/bug.php?id=47987
       *
       * Wir müssen alle im Errorhandler evt. benötigten Klassen samt Hierarchie manuell laden.
       * Danke, PHP-Team!
       */
      __autoload('NestableException', true);
      __autoload('PHPErrorException', true);


      // Fehler in Exception kapseln ...
      $GLOBALS['$__PHPErrorException_create'] = true;    // Marker für Konstruktor von PHPErrorException
      $exception = new PHPErrorException($message, $file, $line, $context);


      // ... und behandeln
      if     ($level == E_USER_NOTICE ) self:: log_1(null, $exception, L_NOTICE);
      elseif ($level == E_USER_WARNING) self:: log_1(null, $exception, L_WARN  );
      else {
         // Spezialfälle, die nicht zurückgeworfen werden dürfen/können
         if ($level==E_STRICT || ($file=='Unknown' && $line==0)) {
            self ::handleException($exception);
            exit(1);
         }

         // alles andere zurückwerfen
         throw $exception;
      }

      return true;
   }


   /**
    * Globaler Handler für nicht abgefangene Exceptions. Die Exception wird geloggt und das Script beendet.
    * Der Aufruf kann automatisch (durch globalen Errorhandler) oder manuell (durch Code, der selbst keine Exceptions werfen darf)
    * erfolgen.
    *
    * NOTE: PHP bricht das Script nach Aufruf dieses Handlers automatisch ab.
    *
    * @param Exception $exception - die zu behandelnde Exception
    */
   public static function handleException(Exception $exception, $destructor = false) {
      try {
         self ::init();

         // TODO: Fatal error: Ignoring exception from ***::__destruct() while an exception is already active (Uncaught PHPErrorException in E:\Projekte\ministruts\src\php\file\image\barcode\test\image.php on line 19)
         //                    in E:\Projekte\ministruts\src\php\file\image\barcode\test\image.php on line 33

         // Bei manuellem Aufruf aus einem Destruktor kann die Exception zurückgereicht werden (STIMMT DAS MIT OBIGEM BUG???), sofern wir nicht im Shutdown sind
         // (während des Shutdowns dürfen keine Exceptions mehr geworfen werden)
         if ($destructor && !isSet($GLOBALS['$__shutting_down']))
            return;


         // 1. Fehlerdaten ermitteln
         $message  = ($exception instanceof NestableException) ? (string)$exception : get_class($exception).': '.$exception->getMessage();
         $traceStr = ($exception instanceof NestableException) ? "Stacktrace:\n-----------\n".$exception->printStackTrace(true) : 'Stacktrace not available';
         // TODO: vernestelte, einfache Exceptions geben fehlerhaften Stacktrace zurück
         $file     =  $exception->getFile();
         $line     =  $exception->getLine();
         $plainMessage = '[FATAL] Uncaught '.$message."\nin ".$file.' on line '.$line."\n";


         // 2. Exception anzeigen (wenn $print TRUE ist)
         if (self::$print) {
            if (isSet($_SERVER['REQUEST_METHOD'])) {
               echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>[FATAL] Uncaught</b> '.nl2br(htmlSpecialChars($message, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
               echo '<br>'.printFormatted($traceStr, true);
               echo "<br></div>\n";

               // Wurde ein Redirect-Header gesendet (oder gesetzt), ist die soeben gemachte Ausgabe verloren. Die Ausgabe kann nur durch zusätzliches Mailen "gerettet" werden.
               // Dies kann vor echo() jedoch nicht zuverlässig ermittelt werden, da der Redirect-Header zwar schon gesetzt, die Header aber evt. noch nicht gesendet sein können.
               foreach (headers_list() as $header) {
                  if (striPos($header, 'Location: ') === 0) {
                     self::$print = false;
                     break;
                  }
               }
            }
            else {
               echo $plainMessage."\n".$traceStr."\n";   // PHP gibt den Fehler unter Linux zusätzlich auf stderr aus,
            }                                            // also auf der Konsole ggf. unterdrücken
         }


         // 3. Exception an die registrierten Adressen mailen (wenn $mail TRUE ist) ...
         if (self::$mail) {
            $mailMsg  = $plainMessage."\n".$traceStr;

            if ($request=Request ::me()) {
               $session = $request->isSession() ? print_r($_SESSION, true) : null;

               $ip   = $_SERVER['REMOTE_ADDR'];
               $host = getHostByAddr($ip);
               if ($host != $ip)
                  $ip = $host.' ('.$ip.')';

               $mailMsg .= "\n\n\nRequest:\n--------\n".$request."\n\n\n"
                        .  "Session: ".($session ? "\n--------\n".$session."\n\n\n" : "  - no session -\n")
                        .  "Host: ".$ip."\n"
                        .  "Time: ".date('Y-m-d H:i:s')."\n";
            }
            else {
               $mailMsg .= "\n\n\nShell:\n------\n".print_r($_SERVER, true)."\n\n\n";
            }

            $mailMsg = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $mailMsg)) : str_replace("\r\n", "\n", $mailMsg);
            $mailMsg = str_replace(chr(0), "*\x00*", $mailMsg);

            foreach (self::$mailReceivers as $address) {
               // TODO: Header mit Fehlermeldung hinzufügen, damit beim Empfänger Messagefilter unterstützt werden
               $success = error_log($mailMsg, 1, $address, 'Subject: PHP: [FATAL] Uncaught Exception at '.($request ? $request->getHostname():'').$_SERVER['PHP_SELF']);
               if (!$success) {
                  error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", $plainMessage)), 0);
                  break;
               }
            }
         }

         // Exception ins Error-Log schreiben, wenn sie nicht per Mail rausgegangen ist
         else {
            error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", $plainMessage)), 0);       // Zeilenumbrüche entfernen
         }
      }
      catch (Exception $secondEx) {
         $file = $exception->getFile();
         $line = $exception->getLine();
         error_log('PHP primary '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", (string)$exception).' in '.$file.' on line '.$line), 0);

         $file = $secondEx->getFile();
         $line = $secondEx->getLine();
         error_log('PHP secondary '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", (string)$secondEx).' in '.$file.' on line '.$line), 0);
      }
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
      else throw new plInvalidArgumentException('Invalid number of arguments: '.$argc);

      if (!is_int($level))    throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));
      if (!is_string($class)) throw new IllegalTypeException('Illegal type of parameter $class: '.getType($class));

      // was der jeweilige Loglevel nicht abdeckt, wird ignoriert
      if ($level < self ::getLogLevel($class))
         return;

      // Aufruf mit drei Argumenten
      if ($argc == 3) {
         if (is_null($message) || is_string($message))
            return self:: log_1($message, null, $level);       // Logger::log($message  , $level, $class)
         if ($exception instanceof Exception)
            return self:: log_1(null, $exception, $level);     // Logger::log($exception, $level, $class)
         throw new IllegalTypeException('Illegal type of first parameter: '.getType($arg1));
      }

      // Aufruf mit vier Argumenten
      if (!is_null($message) && !is_string($message))               throw new IllegalTypeException('Illegal type of parameter $message: '.(is_object($message) ? get_class($message) : getType($message)));
      if (!is_null($exception) && !$exception instanceof Exception) throw new IllegalTypeException('Illegal type of parameter $exception: '.(is_object($exception) ? get_class($exception) : getType($exception)));

      return self:: log_1($message, $exception, $level);       // Logger::log($message, $exception, $level, $class)
   }


   /**
    * Loggt eine Message und/oder eine Exception.  Je nach aktueller Laufzeitumgebung wird die Logmeldung
    * entweder am Bildschirm angezeigt, an die konfigurierten E-Mailadressen gemailt oder ins PHP-Errorlog
    * geschrieben.
    *
    * @param string    $message   - zu loggende Message
    * @param Exception $exception - zu loggende Exception
    * @param int       $level     - zu loggender Loglevel
    */
   private static function log_1($message, Exception $exception=null, $level) {
      $plainMessage = null;

      try {
         if (!isSet(self::$logLevels[$level])) throw new plInvalidArgumentException('Invalid log level: '.$level);
         self ::init();

         // 1. Logdaten ermitteln
         $exMessage = null;
         if ($exception) {
            $message  .= ($message === null) ? (string) $exception : ' ('.get_class($exception).')';
            $exMessage = ($exception instanceof NestableException) ? (string) $exception : get_class($exception).': '.$exception->getMessage();;
         }

         if ($exception instanceof NestableException) {
            $trace = $exception->getStackTrace();
            $file  = $exception->getFile();
            $line  = $exception->getLine();
            $trace = "Stacktrace:\n-----------\n".$exception->printStackTrace(true);
         }
         else {
            $trace = $exception ? $exception->getTrace() : debug_backtrace();
            $trace = NestableException ::transformToJavaStackTrace($trace);
            array_shift($trace);
            array_shift($trace);          // die ersten beiden Frames können weg: 1. Logger::log_1(), 2: Logger::log()

            foreach ($trace as $f) {      // ersten Frame mit __FILE__ suchen
               if (isSet($f['file'])) {
                  $file = $f['file'];
                  $line = $f['line'];
                  break;
               }
            }
            $trace = "Stacktrace:\n-----------\n".NestableException ::formatStackTrace($trace);
            // TODO: vernestelte, einfache Exceptions geben fehlerhaften Stacktrace zurück
         }

         $plainMessage = self::$logLevels[$level].': '.$message."\nin ".$file.' on line '.$line."\n";


         // 2. Logmessage anzeigen (wenn $print TRUE ist)
         if (self::$print) {
            if (isSet($_SERVER['REQUEST_METHOD'])) {
               echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.self::$logLevels[$level].'</b>: '.nl2br(htmlSpecialChars($message, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
               if ($exception)
                  echo '<br>'.htmlSpecialChars($exMessage, ENT_QUOTES).'<br>';
               echo '<br>'.printFormatted($trace, true)."<br></div>";
               if ($request=Request ::me())
                  echo '<br>'.printFormatted("Request:\n--------\n".$request, true)."<br></div><br>";

               // Wurde ein Redirect-Header gesendet, ist die Ausgabe verloren und muß zusätzlich gemailt werden
               // (kann vorher nicht zuverlässig ermittelt werden, da die Header noch nicht gesendet sein können)
               foreach (headers_list() as $header) {
                  if (striPos($header, 'Location: ') === 0) {
                     self::$print = false;
                     break;
                  }
               }
            }
            else {
               echo $plainMessage.($exception ? "\n".$exMessage."\n":'')."\n".$trace."\n";
            }
         }


         // 3. Logmessage an die registrierten Adressen mailen (wenn $mail TRUE ist) ...
         if (self::$mail) {
            $mailMsg = $plainMessage.($exception ? "\n\n".$exMessage."\n":'')."\n\n".$trace;

            if ($request=Request ::me()) {
               $session = $request->isSession() ? print_r($_SESSION, true) : null;

               $ip   = $_SERVER['REMOTE_ADDR'];
               $host = getHostByAddr($ip);
               if ($host != $ip)
                  $ip = $host.' ('.$ip.')';

               $mailMsg .= "\n\n\nRequest:\n--------\n".$request."\n\n\n"
                        .  "Session: ".($session ? "\n--------\n".$session."\n\n\n" : "  - no session -\n")
                        .  "Host: ".$ip."\n"
                        .  "Time: ".date('Y-m-d H:i:s')."\n";
            }
            else {
               $mailMsg .= "\n\n\nShell:\n------\n".print_r($_SERVER, true)."\n\n\n";
            }

            $mailMsg = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $mailMsg)) : str_replace("\r\n", "\n", $mailMsg);
            $mailMsg = str_replace(chr(0), "*\x00*", $mailMsg);

            foreach (self::$mailReceivers as $address) {
               // TODO: Header mit Fehlermeldung hinzufügen, damit beim Empfänger Messagefilter unterstützt werden
               $success = error_log($mailMsg, 1, $address, 'Subject: PHP: '.self::$logLevels[$level].' at '.($request ? $request->getHostname():'').$_SERVER['PHP_SELF']);
               if (!$success) {
                  error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", $plainMessage)), 0);
                  break;
               }
            }
         }

         // ... oder Logmessage ins Error-Log schreiben, falls sie nicht schon angezeigt wurde
         elseif (!self::$print) {
            error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", $plainMessage)), 0);                           // Zeilenumbrüche entfernen
         }
      }
      catch (Exception $ex) {
         error_log('PHP (0) '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", $plainMessage ? $plainMessage:$message)), 0); // Zeilenumbrüche entfernen
         throw $ex;
      }
   }
}
?>
