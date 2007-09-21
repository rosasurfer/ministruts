<?
/**
 * Logger
 */
class Logger extends Object {


   /* Ob das Script in der Konsole läuft. */
   private static $console;

   /* Ob das Event angezeigt werden soll. */
   private static $display;

   /* Ob eine Anzeige HTML-formatiert werden soll. */
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
    * Initialisiert die statischen Klassenvariablen (siehe oben).
    */
   private static function init() {
      if (self::$console !== null)
         return;

      self::$console     = !isSet($_SERVER['REQUEST_METHOD']);
      self::$display     =  self::$console || $_SERVER['REMOTE_ADDR']=='127.0.0.1' || (ini_get('display_errors'));
      self::$displayHtml =  self::$display && !self::$console;
      self::$mailEvent   = !self::$display;
   }


   /**
    * Globaler Handler für herkömmliche PHP-Fehler.  Die Fehler werden in einer PHPErrorException gekapselt und zurückgeworfen.
    *
    * Ausnahme: E_USER_WARNING und E_STRICT werden nur geloggt
    * ---------
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
      if ($error_reporting==0 || ($error_reporting & $level) != $level)       // $error_reporting==0 bedeutet, der Fehler wurde durch @-Operator unterdrückt
         return true;


      // Fehler in Exception kapseln ...
      $exception = new PHPErrorException($message, $file, $line, $vars);


      // ... und zur Behandlung weiterleiten
      if ($level == E_USER_WARNING) {                    // E_USER_WARNINGs werden nur geloggt
         self:: _log(null, $exception, L_WARN);
      }
      elseif ($level == E_STRICT) {                      // E_STRICT darf nicht zurückgeworfen werden und wird deshalb manuell weitergeleitet
         self:: handleException($exception);             // (kann also nicht mit try-catch abgefangen werden)
      }
      else {
         throw $exception;                               // alles andere wird zurückgeworfen (und kann abgefangen werden)
      }

      return true;
   }


   /**
    * Globaler Handler für nicht abgefangene Exceptions. Die Exception wird geloggt und das Script beendet.
    * Der Aufruf kann automatisch (globaler ErrorHandler) oder manuell (Codeteile, die keine Exceptions werfen dürfen)
    * erfolgt sein.
    *
    * @param Exception $exception - die zu behandelnde Exception
    */
   public static function handleException(Exception $exception) {
      // 1. Klasse initialisieren
      self:: init();


      // 2. Fehlerdaten ermitteln
      $message  = ($exception instanceof NestableException) ? (string) $exception : get_class($exception).': '.$exception->getMessage();
      $traceStr = ($exception instanceof NestableException) ? "Stacktrace:\n-----------\n".$exception->printStackTrace(true) : 'Stacktrace not available';
      $file     =  $exception->getFile();
      $line     =  $exception->getLine();
      $plainMessage = 'Uncaught '.$message."\nin ".$file.' on line '.$line."\n";


      // 3. Exception anzeigen
      if (self::$display) {
         ob_get_level() ? ob_flush() : flush();

         if (self::$displayHtml) {
            echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>Uncaught</b> '.nl2br($message)."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
            echo '<br>'.$message.'<br><br>'.printFormatted($traceStr, true);
            echo "<br></div>\n";
         }
         else {
            echo $plainMessage."\n\n".$message."\n\n\n".$traceStr."\n";    // PHP gibt den Fehler unter Linux zusätzlich auf stderr aus,
         }                                                           // also auf der Konsole ggf. unterdrücken
      }


      // 4. Exception an die registrierten Adressen mailen ...
      if (self::$mailEvent) {
         $mailMsg  = $plainMessage."\n\n".$message."\n\n\n".$traceStr;

         $session = isSession() ? print_r($_SESSION, true) : null;

         $ip   = $_SERVER['REMOTE_ADDR'];
         $host = getHostByAddr($ip);
         if ($host != $ip)
            $ip = $host.' ('.$ip.')';

         $mailMsg .= "\n\n\nRequest:\n--------\n".getRequest()."\n\n\n"
                  .  "Session: ".(isSession() ? '('.(isSessionNew() ? '':'not ')."new)\n--------\n".$session."\n\n\n" : "  (no session)\n")
                  .  "Host (IP): ".$ip."\n"
                  .  "Timestamp: ".date('Y-m-d H:i:s')."\n";

         $mailMsg = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $mailMsg)) : str_replace("\r\n", "\n", $mailMsg);

         $old_sendmail_from = ini_get('sendmail_from');
         if (isSet($_SERVER['SERVER_ADMIN']))
            ini_set('sendmail_from', $_SERVER['SERVER_ADMIN']);                           // wirkt sich nur unter Windows aus

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($mailMsg, 1, $webmaster, 'Subject: PHP error_log: Uncaught Exception at '.(isSet($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$_SERVER['PHP_SELF']);
         }
         ini_set('sendmail_from', $old_sendmail_from);
      }
      // ... oder ins Error-Log schreiben
      else {
         error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', $plainMessage), 0);      // Zeilenumbrüche entfernen
      }


      // 5. Script beenden
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
    */
   public static function log() {
      $args = func_num_args();

      // Aufruf mit einem Argument
      if ($args == 1) {
         $arg1 = func_get_arg(0);

         if ($arg1 instanceof Exception)                  return self:: _log(null, $arg1);            // Logger::log($exception)
         else                                             return self:: _log($arg1, null);            // Logger::log($message)
      }
      // Aufruf mit zwei Argumenten
      elseif ($args == 2) {
         $arg1 = func_get_arg(0);
         $arg2 = func_get_arg(1);

         if ($arg1 instanceof Exception) {
            if (is_int($arg2))                            return self:: _log(null, $arg1, $arg2);     // Logger::log($exception, $level)
         }
         elseif (is_int($arg2))                           return self:: _log($arg1, null, $arg2);     // Logger::log($message, $level)
         elseif ($arg2 instanceof Exception)              return self:: _log($arg1, $arg2);           // Logger::log($message, $exception)
      }
      // Aufruf mit drei Argumenten
      elseif ($args == 3) {
         $arg1 = func_get_arg(0);
         $arg2 = func_get_arg(1);
         $arg3 = func_get_arg(2);

         if ($arg2 instanceof Exception && is_int($arg3)) return self:: _log($arg1, $arg2, $arg3);    // Logger::log($message, $exception, $level)
      }

      throw new InvalidArgumentException('Invalid arguments');
   }


   /**
    * Loggt eine Message und/oder eine Exception.
    *
    * Ablauf:
    * -------
    * - prüfen, ob Message vom aktuellen Loglevel abgedeckt wird
    * - Anzeige der Message
    * - entweder Benachrichtigungsmail verschicken oder Message ins Errorlog schreiben
    *
    * @param mixed     $message   - die zu loggende Message
    * @param Exception $exception - die zu loggende Exception
    * @param int       $level     - der Mindest-Loglevel, der aktiv sein muß
    */
   private static function _log($message, $exception = null, $level = L_ERROR) {
      if (!isSet(self::$logLevels[$level])) throw new InvalidArgumentException('Invalid log level: '.$level);


      // Messages, die der aktuelle Loglevel nicht abdeckt, ignorieren
      //if (false) return;


      // 1. Klasse initialisieren
      self:: init();


      // 2. Logdaten ermitteln
      $exMessage = $exTraceStr = null;
      if ($exception) {
         $message   .= ($message === null) ? (string) $exception : ' ('.get_class($exception).')';
         $exMessage  = ($exception instanceof NestableException) ? (string) $exception : get_class($exception).': '.$exception->getMessage();;
         $exTraceStr = ($exception instanceof NestableException) ? "Stacktrace:\n-----------\n".$exception->printStackTrace(true) : 'Stacktrace not available';
      }
      $trace = debug_backtrace();
      $file  = $trace[1]['file'];
      $line  = $trace[1]['line'];
      $plainMessage = self::$logLevels[$level].': '.$message."\nin ".$file.' on line '.$line."\n";


      // 3. Anzeige
      if (self::$display) {
         ob_get_level() ? ob_flush() : flush();

         if (self::$displayHtml) {
            echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.self::$logLevels[$level].'</b>: '.nl2br($message)."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
            if ($exception)
               echo '<br>'.$exMessage.'<br><br>'.printFormatted($exTraceStr, true);
            echo "<br></div>\n";
         }
         else {
            echo $plainMessage;
            if ($exception)
               echo "\n".$exMessage."\n\n".$exTraceStr."\n";
         }
      }


      // Logmessage entweder an die registrierten Adressen mailen ...
      if (self::$mailEvent) {
         $mailMsg = $plainMessage;
         if ($exception)
            $mailMsg .= "\n\n".$exMessage."\n\n\n".$exTraceStr;

         $session = isSession() ? print_r($_SESSION, true) : null;

         $ip   = $_SERVER['REMOTE_ADDR'];
         $host = getHostByAddr($ip);
         if ($host != $ip)
            $ip = $host.' ('.$ip.')';

         $mailMsg .= "\n\n\nRequest:\n--------\n".getRequest()."\n\n\n"
                  .  "Session: ".(isSession() ? '('.(isSessionNew() ? '':'not ')."new)\n--------\n".$session."\n\n\n" : "  (no session)\n")
                  .  "Host (IP): ".$ip."\n"
                  .  "Timestamp: ".date('Y-m-d H:i:s')."\n";

         $mailMsg = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $mailMsg)) : str_replace("\r\n", "\n", $mailMsg);

         $old_sendmail_from = ini_get('sendmail_from');
         if (isSet($_SERVER['SERVER_ADMIN']))
            ini_set('sendmail_from', $_SERVER['SERVER_ADMIN']);                           // wirkt sich nur unter Windows aus

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($mailMsg, 1, $webmaster, 'Subject: PHP error_log: '.self::$logLevels[$level].' at '.(isSet($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$_SERVER['PHP_SELF']);
         }
         ini_set('sendmail_from', $old_sendmail_from);
      }
      // ... oder ins Error-Log schreiben
      else {
         error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', $plainMessage), 0);      // Zeilenumbrüche entfernen
      }
   }
}
?>
