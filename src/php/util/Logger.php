<?
/**
 * Logger
 */
class Logger extends Object {


   /* Ob das Script in der Konsole läuft. */
   private static $console;

   /* Ob das Event angezeigt werden soll (im Browser nur, wenn Request von localhost kommt). */
   private static $display;

   /* Ob die Anzeige HTML-formatiert werden soll. */
   private static $displayHtml;

   /* Ob Benachrichtigungsmails an die Webmaster verschickt werden sollen. */
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
      if (Logger ::$console !== null)
         return;

      Logger ::$console     = !isSet($_SERVER['REQUEST_METHOD']);
      Logger ::$display     =  Logger ::$console || $_SERVER['REMOTE_ADDR']=='127.0.0.1';
      Logger ::$displayHtml =  Logger ::$display && !Logger ::$console;
      Logger ::$mailEvent   = !Logger ::$display;
   }


   /**
    * Globaler Handler für herkömmliche PHP-Fehler.  Die Fehler werden in einer PHPErrorException gekapselt und zurückgeworfen.
    *
    * Ausnahmen: E_USER_WARNING und E_STRICT: Fehler werden nur geloggt
    * ----------
    *
    * @param int    $level   -
    * @param string $message -
    * @param string $file    -
    * @param int    $line    -
    * @param array  $vars    -
    *
    * @return boolean - true, wenn der Fehler erfolgreich behandelt wurde
    *                   false, wenn der Fehler weitergereicht werden soll, als wenn der ErrorHandler nicht registriert wäre
    */
   public static function handleError($level, $message, $file, $line, array $vars) {
      $error_reporting = error_reporting();


      // Fehler, die der aktuelle Errorlevel nicht abdeckt, werden ignoriert
      if ($error_reporting==0 || ($error_reporting & $level) != $level)          // $error_reporting==0 bedeutet, der Fehler wurde durch den @-Operator unterdrückt
         return true;


      // Fehler in Exception kapseln ...
      $exception = new PHPErrorException($message, $file, $line, $vars);


      // ... und zur Behandlung weiterleiten
      if ($level == E_USER_WARNING) {                    // E_USER_WARNINGs werden nur geloggt
         Logger ::_log(null, $exception, L_WARN);
      }
      elseif ($level == E_STRICT) {                      // E_STRICT darf nicht zurückgeworfen werden und wird deshalb manuell weitergeleitet
         Logger ::handleException($exception);
      }
      else {
         throw $exception;                               // alles andere wird zurückgeworfen
      }

      return true;
   }


   /**
    * Globaler Handler für nicht abgefangene Exceptions. Die Exception wird geloggt und das Script beendet.
    *
    * @param Exception $exception - die zu behandelnde Exception
    */
   public static function handleException(Exception $exception) {
      Logger ::_log(null, $exception, L_FATAL);
      exit(1);
   }


   /**
    * Loggt eine Message und/oder eine Exception. Überladene Methode.
    *
    * Methodensignaturen:
    * -------------------
    * Logger::log($message)                        // Level L_ERROR
    * Logger::log($message, $level)
    *
    * Logger::log($exception)                      // Level L_ERROR
    * Logger::log($exception, $level)
    *
    * Logger::log($message, $exception)            // Level L_ERROR
    * Logger::log($message, $exception, $level)
    *
    * Ablauf:
    * -------
    * - prüfen, ob Message vom aktuellen Loglevel abgedeckt wird
    * - Anzeige der Message (im Browser nur, wenn der Request von 'localhost' kommt)
    * - Eintrag ins Errorlog
    * - Benachrichtigungsmail an alle registrierten Webmaster
    */
   public static function log() {
      $args = func_num_args();

      if ($args == 1) {
         $arg1 = func_get_arg(0);

         if (is_string($arg1))              return Logger ::_log($arg1);                // Logger::log($message)
         if ($arg1 instanceof Exception)    return Logger ::_log(null, $arg1);          // Logger::log($exception)
      }
      elseif ($args == 2) {
         $arg1 = func_get_arg(0);
         $arg2 = func_get_arg(1);

         if (is_string($arg1)) {
            if (is_int($arg2))              return Logger ::_log($arg1, null, $arg2);   // Logger::log($message, $level)
            if ($arg2 instanceof Exception) return Logger ::_log($arg1, $arg2);         // Logger::log($message, $exception)
         }
         elseif ($arg1 instanceof Exception) {
            if (is_int($arg2))              return Logger ::_log(null, $arg1, $arg2);   // Logger::log($exception, $level)
         }
      }
      elseif ($args == 3) {
         $arg1 = func_get_arg(0);
         $arg2 = func_get_arg(1);
         $arg3 = func_get_arg(2);

         if (is_string($arg1) && $arg2 instanceof Exception && is_int($arg3))
                                            return Logger ::_log($arg1, $arg2, $arg3);  // Logger::log($message, $exception, $level)
      }

      throw new InvalidArgumentException('Invalid arguments');
   }


   /**
    * Loggt eine Message und/oder eine Exception.
    *
    * Ablauf:
    * -------
    * - prüfen, ob Message vom aktuellen Loglevel abgedeckt wird
    * - Anzeige der Message (im Browser nur, wenn der Request von 'localhost' kommt)
    * - Eintrag ins Errorlog
    * - Benachrichtigungsmail an alle registrierten Webmaster
    *
    * @param string    $message   - die zu loggende Message
    * @param Exception $exception - die zu loggende Exception
    * @param int       $level     - der Loglevel, der mindestens aktiv sein muß
    */
   private static function _log($message, $exception = null, $level = L_ERROR) {
      if (!isSet(Logger ::$logLevels[$level])) throw new InvalidArgumentException('Invalid log level: '.$level);


      // Messages, die der aktuelle Loglevel nicht abdeckt, werden ignoriert
      //if (false)
      //   return;


      // Statische Klassenvariablen initialisieren
      if (Logger ::$console === null)
         Logger ::init();


      // Quellcode-Position der Ursache ermitteln
      $stackTrace = debug_backtrace();
      $frame =& $stackTrace[sizeOf($stackTrace)-1];               // der letzte Frame
      $file = $line = $uncaughtException = null;

      if ($message==null && isSet($frame['class']) && isSet($frame['type']) && isSet($frame['function']) && $frame['class'].$frame['type'].$frame['function'] == 'Logger::handleException') {
         $uncaughtException = true;
         $file = $frame['args'][0]->getFile();                    // Aufruf durch globalen Errorhandler
         $line = $frame['args'][0]->getLine();
      }
      else {
         $uncaughtException = false;                              // manueller Aufruf im Code
         $file = $stackTrace[1]['file'];
         $line = $stackTrace[1]['line'];                          // !!! Aufruf der Logmethode genauer tracen
      }


      // Logmessage zusammensetzen
      if ($exception) {
         if ($message === null) {
            $message = (string) $exception;
         }
         else {
            $message .= ' ('.get_class($exception).')';
         }
      }
      $plainMessage = Logger ::$logLevels[$level].': '.$message."\nin ".$file.' on line '.$line."\n";


      // Anzeige
      if (Logger ::$display) {
         ob_get_level() ? ob_flush() : flush();
         if (Logger ::$displayHtml) {
            echo '<div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.Logger ::$logLevels[$level].'</b>: '.nl2br($message)."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
            if ($exception) {
               if ($exception instanceof NestableException) {
                  echo '<br>'.$exception.'<br><br>'.printFormatted("Stacktrace:\n-----------\n".$exception->printStackTrace(true), true);
               }
               else {
                  echo '<br>'.get_class($exception).': '.$exception->getMessage().'<br><br>';
                  echo printFormatted('Stacktrace not available', true);
               }
            }
            echo "<br></div>\n";
         }
         else {
            echo $plainMessage;                                                  // PHP gibt den Fehler unter Linux zusätzlich auf stderr aus,
            if ($exception) {                                                    // also auf der Konsole ggf. unterdrücken ( 2>/dev/null )
               if ($exception instanceof NestableException)
                  printFormatted("\n$exception\n".$exception->printStackTrace(true));
               else
                  printFormatted("\n".get_class($exception).': '.$exception->getMessage()."\nStacktrace not available\n");
            }
         }
      }


      // Logmessage entweder an die registrierten Webmaster mailen ...
      if (Logger ::$mailEvent) {
         if ($exception) {
            if ($exception instanceof NestableException)
               $plainMessage .= "\n\n".$exception."\n\n\nStacktrace:\n-----------\n".$exception->printStackTrace(true);
            else
               $plainMessage .= "\n\n".get_class($exception).': '.$exception->getMessage()."\nStacktrace not available\n";
         }

         $plainMessage .= "\n\n\nRequest:\n--------\n".getRequest()."\n\n\nIP: ".$_SERVER['REMOTE_ADDR']."\n---\n";
         $plainMessage = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $plainMessage)) : str_replace("\r\n", "\n", $plainMessage);

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($plainMessage, 1, $webmaster, 'Subject: PHP error_log: '.Logger ::$logLevels[$level].' at '.(isSet($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$_SERVER['PHP_SELF']);
         }
      }
      // ... oder ins Error-Log schreiben
      else {
         error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', $plainMessage), 0);      // Zeilenumbrüche entfernen
      }
   }
}
?>
