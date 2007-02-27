<?
/**
 * Logger
 */
class Logger extends Object {


   /* ob das Script in der Konsole läuft */
   private static $console;

   /* ob das Event angezeigt werden soll (im Browser nur, wenn Request von localhost kommt) */
   private static $displayEvent;

   /* ob die Anzeige HTML-formatiert werden soll */
   private static $displayHtml;

   /* ob Benachrichtigungsmails an die Webmaster verschickt werden sollen */
   private static $mailEvent;
   private static $logLevels = array(L_DEBUG  => '[Debug]',
                                     L_NOTICE => '[Notice]',
                                     L_INFO   => '[Info]',
                                     L_WARN   => '[Warn]',
                                     L_ERROR  => '[Error]',    // Default-Loglevel
                                     L_FATAL  => '[Fatal]',
   );


   /**
    * Initialisiert die statischen Klassenvariablen.
    */
   private static function init() {
      if (self::$console !== null)
         return;

      self::$console      = !isSet($_SERVER['REQUEST_METHOD']);
      self::$displayEvent = self::$console || $_SERVER['REMOTE_ADDR']=='127.0.0.1';
      self::$displayHtml  = self::$displayEvent && !self::$console;
      self::$mailEvent    = !self::$console && $_SERVER['REMOTE_ADDR']!='127.0.0.1';
   }


   /**
    * Loggt eine Exception.
    *
    * Ablauf:
    * -------
    * - Anzeige der Exception (im Browser nur, wenn der HttpRequest von 'localhost' kommt)
    * - Eintrag der Exception ins Errorlog
    * - Verschicken einer Benachrichtigungsmail an alle registrierten Webmaster
    *
    * @param Exception $exception - die zu loggende Exception
    */
   public static function logException(Exception $exception) {
      if (self::$console === null)
         self:: init();

      // 1. Exception anzeigen
      if (self::$displayEvent) {
         if (self::$displayHtml) {
            echo nl2br('<div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.get_class($exception).'</b>: '.$exception->getMessage()."\nin <b>".$exception->getFile().'</b> on line <b>'.$exception->getLine().'</b><br>');
            echo '<br>'.printFormatted("Stacktrace:\n-----------\n".$exception->printStackTrace(true)).'</div>';
         }
         else {
            ob_get_level() ? ob_flush() : flush();
            echo get_class($exception).': '.$exception->getMessage()."\nin ".$exception->getFile().' on line '.$exception->getLine()."\n\n";
            echo "Stacktrace:\n-----------\n";
            $exception->printStackTrace();
         }
      }


      // 2. Exception ins Error-Log eintragen
      $logMessage = get_class($exception).': '.$exception->getMessage().' in '.$exception->getFile().' on line '.$exception->getLine();
      $logMessage = 'PHP '.str_replace(array("\r\n", "\n"), ' ', $logMessage);      // Zeilenumbrüche entfernen
      error_log($logMessage, 0);


      // 3. Benachrichtigungsmail an die Webmaster verschicken
      if (self::$mailEvent && isSet($GLOBALS['webmasters'])) {
         $mailMessage  = get_class($exception).': '.$exception->getMessage()."\nin ".$exception->getFile().' on line '.$exception->getLine()."\n";
         $mailMessage .= "\nStacktrace:\n-----------\n".$exception->printStackTrace(true);
         $mailMessage .= "\nRequest:\n--------\n".getRequest()."\n\nIP: ".$_SERVER['REMOTE_ADDR']."\n---\n";
         $mailMessage = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $mailMessage)) : str_replace("\r\n", "\n", $mailMessage);

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($mailMessage, 1, $webmaster, 'Subject: PHP error_log: '.get_class($exception).' at '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
         }
      }
   }


   /**
    * Loggt eine Message und/oder eine Exception.
    *
    * Aufrufvarianten:
    * ----------------
    * Logger::log($message)                              // Loglevel: L_ERROR
    * Logger::log($message, $logLevel)
    * Logger::log($exception)                            // Loglevel: L_ERROR
    * Logger::log($exception, $logLevel)
    * Logger::log($message, $exception)                  // Loglevel: L_ERROR
    * Logger::log($message, $exception, $logLevel)
    *
    * Ablauf:
    * -------
    * - prüfen, ob Message vom aktuellen Loglevel abgedeckt wird
    * - Anzeige der Message (im Browser nur, wenn der Request von 'localhost' kommt)
    * - Eintrag ins Errorlog
    * - Benachrichtigungsmail an alle registrierten Webmaster
    */
   public static function log($mixed, $exception = null, $logLevel = L_ERROR) {
      $msg = $ex = null;
      $level = $logLevel;


      // Parameter validieren
      $args = func_num_args();
      if ($args == 1) {
         if ($mixed instanceof Exception) {                    // Logger::log($exception)
            $ex = $mixed;
         }
         else {                                                // Logger::log($message)
            $msg = $mixed;
         }
      }
      elseif ($args == 2) {
         if ($mixed instanceof Exception) {                    // Logger::log($exception, $logLevel)
            $ex = $mixed;
            $level = $exception;
            if (!isSet(self::$logLevels[$level]))
               throw new InvalidArgumentException('Invalid log level: '.$level);
         }
         elseif ($exception instanceof Exception) {            // Logger::log($message, $exception)
            $msg = $mixed;
            $ex = $exception;
         }
         else {                                                // Logger::log($message, $logLevel)
            $msg = $mixed;
            $level = $exception;
            if (!isSet(self::$logLevels[$level]))
               throw new InvalidArgumentException('Invalid log level: '.$level);
         }
      }
      elseif ($args == 3) {                                    // Logger::log($message, $exception, $logLevel)
         $msg = $mixed;
         if (!$exception instanceof Exception)
            throw new InvalidArgumentException('Invalid argument $exception: '.$exception);
         $ex = $exception;
         if (!isSet(self::$logLevels[$level]))
            throw new InvalidArgumentException('Invalid log level: '.$level);
      }
      else {
         throw new RuntimeException('Illegal number of arguments: '.$args);
      }


      // Statische Klassenvariablen initialisieren
      if (self::$console === null)
         self:: init();

      // Messages, die der aktuelle Loglevel nicht abdeckt, werden nicht geloggt
      //if (false)
      //   return;


      // Logmessage zusammensetzen
      if (is_object($msg) && method_exists($msg, '__toString')) {
         $msg = $msg->__toString();
      }
      elseif (is_object($msg) || is_array($msg)) {
         $msg = print_r($msg, true);
      }
      else {
         $msg = (string) $msg;
      }
      if ($ex) {
         $msg = $msg ? $msg.' ('.(string) $ex.')' : (string) $ex;
      }


      // Quellcode-Position der Loganweisung einfügen
      $stackTrace = debug_backtrace();
         $file = $stackTrace[0]['file'];
         $line = $stackTrace[0]['line'];
      $message = self::$logLevels[$level].': '.$msg."\nin ".$file.' on line '.$line."\n";


      // Anzeige
      if (self::$displayEvent) {
         ob_get_level() ? ob_flush() : flush();
         if (self::$displayHtml) {
            echo nl2br('<div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.self::$logLevels[$level].'</b>: '.$msg."\n in <b>".$file.'</b> on line <b>'.$line.'</b><br>');
            if ($ex) {
               if ($ex instanceof NestableException) {
                  echo '<br>'.$ex.printFormatted("Stacktrace:\n-----------\n".$ex->printStackTrace(true), true).'<br>';
               }
               else {
                  echo '<br>'.get_class($ex).': '.$ex->getMessage();
                  echo printFormatted('Stacktrace not available', true).'<br>';
                  //echoPre(print_r($ex, true));
               }
            }
            echo "</div>\n";
         }
         else {
            echo $message;                                                       // PHP gibt den Fehler unter Linux zusätzlich auf stderr aus,
            if ($ex) {                                                           // also auf der Konsole ggf. unterdrücken ( 2>/dev/null )
               if ($ex instanceof NestableException) {
                  printFormatted("\n$ex\n".$ex->printStackTrace(true));
               }
               else {
                  printFormatted("\n".get_class($ex).': '.$ex->getMessage()."\nStacktrace not available\n");
               }
            }
         }
      }


      // Logmessage ins Error-Log schreiben
      $logMsg = 'PHP '.str_replace(array("\r\n", "\n"), ' ', $message);       // Zeilenumbrüche entfernen
      error_log($logMsg, 0);


      // Logmessage an alle registrierten Webmaster mailen
      if (self::$mailEvent) {
         if ($ex) {
            if ($ex instanceof NestableException) {
               $message .= "\n\n$ex\n".$ex->printStackTrace(true);
            }
            else {
               $message .= "\n\n".get_class($ex).': '.$ex->getMessage()."\nStacktrace not available\n";
            }
         }
         $message .= "\n\nRequest:\n--------\n".getRequest()."\n\nIP: ".$_SERVER['REMOTE_ADDR']."\n---\n";
         $message = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $message)) : str_replace("\r\n", "\n", $message);

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($message, 1, $webmaster, 'Subject: PHP error_log: '.self::$logLevels[$level].' at '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
         }
      }
   }


   /**
    * Loggt ein Event.
    *
    * Signaturen (= Aufrufvarianten):
    * -------------------------------
    * Logger::log($message)                              // Loglevel: L_ERROR
    * Logger::log($message, $logLevel)
    * Logger::log($exception)                            // Loglevel: L_ERROR
    * Logger::log($exception, $logLevel)
    * Logger::log($message, $exception)                  // Loglevel: L_ERROR
    * Logger::log($message, $exception, $logLevel)
    *
    * Ablauf:
    * -------
    * - prüfen, ob das Event vom aktuellen Loglevel abgedeckt wird
    * - Anzeige des Events (im Browser nur, wenn der HttpRequest von 'localhost' kommt)
    * - Eintrag des Events ins Errorlog
    * - Benachrichtigungsmail an alle registrierten Webmaster
    */
   public static function logEvent($mixed, $exception = null, $logLevel = L_ERROR) {
      $message = null;
      $level = $logLevel;

      // 1. verwendete Methodensignatur ermitteln und Parameter zuordnen
      $args = func_num_args();
      if ($args == 1) {
         if ($mixed instanceof Exception) {                       // Logger::log($exception)
            $exception = $mixed;
         }
         else {                                                   // Logger::log($message)
            $message = $mixed;
         }
      }
      elseif ($args == 2) {
         if ($mixed instanceof Exception) {                       // Logger::log($exception, $logLevel)
            $level = $exception;
            if (!isSet(self::$logLevels[$level]))
               throw new InvalidArgumentException('Invalid log level: '.$level.' ('.getType($level).')');
            $exception = $mixed;
         }
         elseif ($exception instanceof Exception) {               // Logger::log($message, $exception)
            $message = $mixed;
         }
         else {                                                   // Logger::log($message, $logLevel)
            $message = $mixed;
            $level = $exception;
            if (!isSet(self::$logLevels[$level]))
               throw new InvalidArgumentException('Invalid log level: '.$level.' ('.getType($level).')');
            $exception = null;
         }
      }
      elseif ($args == 3) {                                       // Logger::log($message, $exception, $logLevel)
         $message = $mixed;
         if (!$exception instanceof Exception)
            throw new InvalidArgumentException('Invalid argument $exception: '.$exception.' ('.getType($exception).')');
         if (!isSet(self::$logLevels[$level]))
            throw new InvalidArgumentException('Invalid log level: '.$level.' ('.getType($level).')');
      }
      else {
         throw new RuntimeException('Illegal number of arguments: '.$args);
      }


      // 2. Prüfen, ob der angegebene Loglevel vom aktuell aktiven Loglevel abgedeckt wird
      if ($level < LOGLEVEL)
         return;

      if (self::$console === null)
         self:: init();

      // 3. Event anzeigen
      if (self::$displayEvent) {
         ob_get_level() ? ob_flush() : flush();
         if (self::$displayHtml) {
            //echo nl2br('<div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.$className.'</b>: '.$msg."\n in <b>".$file.'</b> on line <b>'.$line.'</b>');
            //echo '<br>'.printFormatted($trace, true).'<br></div>';
         }
         else {
            //echo $message;
            //printFormatted("\n".$trace);
         }
      }
      //    3.1 print($ex)
      //    3.2 $ex->printStackTrace()

      // 4. Eintrag ins Error-Log

      // 5. Benachrichtigungsmail an die Webmaster verschicken
   }
}
?>
