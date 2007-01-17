<?
/**
 * Logger
 */
class Logger extends Object {


   /**
    * Constructor
    */
   private function __construct() {
      throw new Exception('Do not instantiate this class.');
   }


   /**
    * Loggt eine Message und/oder eine Exception.
    *
    * Aufrufvarianten:
    * ----------------
    * Logger::log($message)                              // Loglevel: L_WARN
    * Logger::log($message, $logLevel)
    * Logger::log($exception)                            // Loglevel: L_WARN
    * Logger::log($exception, $logLevel)
    * Logger::log($message, $exception)                  // Loglevel: L_WARN
    * Logger::log($message, $exception, $logLevel)
    *
    * Ablauf:
    * -------
    * - prüfen, ob Message vom aktuellen Loglevel abgedeckt wird
    * - Anzeige der Message (im Browser nur, wenn der Request von 'localhost' kommt)
    * - Eintrag ins Errorlog
    * - Mailen der Message an alle registrierten Webmaster
    */
   public static function log($mixed, $exception = null, $logLevel = L_WARN) {
      // Statische Variablen initialisieren
      static $logLevels = null;
      if ($logLevels === null) {
         $logLevels = array(L_DEBUG        => '[Debug]',
                            L_NOTICE       => '[Notice]',
                            L_INFO         => '[Info]',
                            L_WARN         => '[Warn]',
                            L_ERROR        => '[Error]',
                            L_FATAL        => '[Fatal]',
                            E_NOTICE       => '[Notice]',                     // es können auch die üblichen PHP-Errorkonstanten verwendet werden
                            E_WARNING      => '[Warn]',
                            E_ERROR        => '[Error]',
                            E_USER_NOTICE  => '[Notice]',
                            E_USER_WARNING => '[Warn]',
                            E_USER_ERROR   => '[Error]',
         );
         $console     = !isSet($_SERVER['REQUEST_METHOD']);                   // ob das Script in der Konsole läuft
         $display     = $console || $_SERVER['REMOTE_ADDR']=='127.0.0.1';     // ob die Message angezeigt werden soll (im Browser nur, wenn Request von localhost kommt)
         $displayHtml = $display && !$console;                                // ob die Ausgabe HTML-formatiert werden muß
         $mail        = !$console && $_SERVER['REMOTE_ADDR']!='127.0.0.1';    // ob Mails verschickt werden sollen
      }

      $msg = $ex = null;
      $level = L_WARN;        // Default-Loglevel


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
            if (!is_int($level) || !isSet($logLevels[$level]))
               throw new InvalidArgumentException('Invalid log level: '.$level);
         }
         elseif ($exception instanceof Exception) {            // Logger::log($message, $exception)
            $msg = $mixed;
            $ex = $exception;
         }
         else {                                                // Logger::log($message, $logLevel)
            $msg = $mixed;
            $level = $exception;
            if (!is_int($level) || !isSet($logLevels[$level]))
               throw new InvalidArgumentException('Invalid log level: '.$level);
         }
      }
      elseif ($args == 3) {                                    // Logger::log($message, $exception, $logLevel)
         $msg = $mixed;
         if (!$exception instanceof Exception)
            throw new InvalidArgumentException('Invalid argument $exception: '.$exception);
         $ex = $exception;
         $level = $logLevel;
         if (!is_int($level) || !isSet($logLevels[$level]))
            throw new InvalidArgumentException('Invalid log level: '.$level);
      }
      else {
         throw new RuntimeException('Illegal number of arguments: '.$args);
      }


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


      // Quellcode-Position der Loganweisung einfügen
      $stackTrace = debug_backtrace();
         $file = $stackTrace[0]['file'];
         $line = $stackTrace[0]['line'];
      $message = $logLevels[$level].': '.$msg."\nin ".$file.' on line '.$line."\n";


      // Anzeige
      if ($display) {
         ob_get_level() ? ob_flush() : flush();
         if ($displayHtml) {
            echo nl2br('<div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.$logLevels[$level].'</b>: '.$msg."\n in <b>".$file.'</b> on line <b>'.$line.'</b><br>');
            if ($ex) {
               echo '<br>'.$ex->__toString();
               echo printFormatted($ex->getReadableStackTrace(), true).'<br>';
            }
            echo "</div>\n";
         }
         else {
            echo $message;                                                       // PHP gibt den Fehler unter Linux zusätzlich auf stderr aus,
            if ($ex) {                                                           // also auf der Konsole ggf. unterdrücken ( 2>/dev/null )
               printFormatted("\n".$ex->__toString()."\n".$ex->getReadableStackTrace());
            }
         }
      }


      // Logmessage ins Error-Log schreiben
      $logMsg = 'PHP '.str_replace(array("\r\n", "\n"), ' ', $message);       // Zeilenumbrüche entfernen
      error_log($logMsg, 0);


      // Logmessage an alle registrierten Webmaster mailen
      if ($mail) {
         if ($ex) {
            $message .= "\n\n".$ex->__toString()."\n".$ex->getReadableStackTrace();
         }
         $message .= "\n\nRequest:\n--------\n".getRequest()."\n\nIP: ".$_SERVER['REMOTE_ADDR']."\n---\n";
         $message = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $message)) : str_replace("\r\n", "\n", $message);

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($message, 1, $webmaster, 'Subject: PHP error_log: '.$logLevels[$level].' at '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
         }
      }
   }
}
?>
