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
      if (self::$console !== null)
         return;

      self::$console     = !isSet($_SERVER['REQUEST_METHOD']);
      self::$display     = self::$console || $_SERVER['REMOTE_ADDR']=='127.0.0.1';
      self::$displayHtml = self::$display && !self::$console;
      self::$mailEvent   = !self::$display;
   }


   /**
    * Loggt eine Message und/oder eine Exception.
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

         if (is_string($arg1))              return self:: _log($arg1);                 // Logger::log($message)
         if ($arg1 instanceof Exception)    return self:: _log(null, $arg1);           // Logger::log($exception)
      }
      elseif ($args == 2) {
         $arg1 = func_get_arg(0);
         $arg2 = func_get_arg(1);

         if (is_string($arg1)) {
            if (is_int($arg2))              return self:: _log($arg1, null, $arg2);    // Logger::log($message, $level)
            if ($arg2 instanceof Exception) return self:: _log($arg1, $arg2);          // Logger::log($message, $exception)
         }
         elseif ($arg1 instanceof Exception) {
            if (is_int($arg2))              return self:: _log(null, $arg1, $arg2);    // Logger::log($exception, $level)
         }
      }
      elseif ($args == 3) {
         $arg1 = func_get_arg(0);
         $arg2 = func_get_arg(1);
         $arg3 = func_get_arg(2);

         if (is_string($arg1) && $arg2 instanceof Exception && is_int($arg3))
                                            return self:: _log($arg1, $arg2, $arg3);   // Logger::log($message, $exception, $level)
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
    */
   private static function _log($message, $exception = null, $level = L_ERROR) {
      if (!isSet(self::$logLevels[$level])) throw new InvalidArgumentException('Invalid log level: '.$level);


      // Messages, die der aktuelle Loglevel nicht abdeckt, werden ignoriert
      //if (false)
      //   return;


      // Statische Klassenvariablen initialisieren
      if (self::$console === null)
         self:: init();


      // Quellcode-Position der Ursache ermitteln
      $stackTrace = debug_backtrace();
      $frame =& $stackTrace[sizeOf($stackTrace)-1];               // der letzte Frame
      $file = $line = $uncaughtException = null;

      /*
      foreach ($stackTrace as $frame) {
         if (isSet($frame['args'])) {
            unset($frame['args']);
         }
         echoPre($frame);
      }
      */

      if ($message==null && isSet($frame['class']) && isSet($frame['type']) && isSet($frame['function']) && $frame['class'].$frame['type'].$frame['function'] == 'ErrorHandler::handleException') {
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
      $plainMessage = self::$logLevels[$level].': '.$message."\nin ".$file.' on line '.$line."\n";


      // Anzeige
      if (self::$display) {
         ob_get_level() ? ob_flush() : flush();
         if (self::$displayHtml) {
            echo '<div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.self::$logLevels[$level].'</b>: '.nl2br($message)."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
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


      // Logmessage ins Error-Log schreiben
      error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', $plainMessage), 0);      // Zeilenumbrüche entfernen


      // Logmessage an alle registrierten Webmaster mailen
      if (self::$mailEvent) {
         if ($exception) {
            if ($exception instanceof NestableException)
               $plainMessage .= "\n\n".$exception."\n\n\nStacktrace:\n-----------\n".$exception->printStackTrace(true);
            else
               $plainMessage .= "\n\n".get_class($exception).': '.$exception->getMessage()."\nStacktrace not available\n";
         }

         $plainMessage .= "\n\n\nRequest:\n--------\n".getRequest()."\n\n\nIP: ".$_SERVER['REMOTE_ADDR']."\n---\n";
         $plainMessage = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $plainMessage)) : str_replace("\r\n", "\n", $plainMessage);

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($message, 1, $webmaster, 'Subject: PHP error_log: '.self::$logLevels[$level].' at '.(isSet($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$_SERVER['PHP_SELF']);
         }
      }
   }
}
?>
