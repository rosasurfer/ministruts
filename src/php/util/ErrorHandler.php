<?
/**
 * ErrorHandler
 */
class ErrorHandler extends Object {


   /**
    * Constructor
    */
   private function __construct() {
      throw new Exception('Do not instantiate this class.');
   }


   /**
    * Globaler Handler für herkömmliche PHP-Fehler.  Die Fehler werden in eine Exception vom Typ PHPErrorException umgewandelt
    * und zurückgeworfen.
    *
    * Ausnahmen: E_USER_WARNINGs und Fehler, die in __autoload() ausgelöst wurden, werden weiterhin wie herkömmliche
    *            Fehler behandelt, d.h.:
    *
    *  - Fehleranzeige (im Browser nur, wenn der Request von 'localhost' kommt)
    *  - Loggen im Errorlog
    *  - Verschicken von Fehler-Emails an alle registrierten Webmaster
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
      if ($error_reporting==0 || ($error_reporting & $level) != $level)          // 0 bedeutet, der @-Operator hat den Fehler ausgelöst (@statement)
         return true;


      // Level-spezifische Behandlung
      if ($level != E_USER_WARNING && $level != E_STRICT) {                      // werden nur geloggt
         $exception = new PHPErrorException($message, $file, $line, $vars);          // Fehler in Exception kapseln und zurückwerfen
         $trace = $exception->getTrace();                                        // (wenn nicht in __autoload ausgelöst)
         $frame =& $trace[1];
         if (isSet($frame['class']) || (strToLower($frame['function'])!='__autoload' && $frame['function']!='trigger_error'))
            throw $exception;
         if ($frame['function']=='trigger_error' && (!isSet($trace[2]) || isSet($trace[2]['class']) || strToLower($trace[2]['function'])!='__autoload'))
            throw $exception;
      }


      // Behandlung von Fehlern, die nicht in eine Exception umgewandelt werden
      $console     = !isSet($_SERVER['REQUEST_METHOD']);                         // ob das Script in der Konsole läuft
      $display     = $console || $_SERVER['REMOTE_ADDR']=='127.0.0.1';           // ob der Fehler angezeigt werden soll (im Browser nur, wenn Request von localhost kommt)
      $displayHtml = $display && !$console;                                      // ob die Ausgabe HTML-formatiert werden muß
      $logErrors   = (ini_get('log_errors'));                                    // ob der Fehler geloggt werden soll
      $mailErrors  = !$console && $_SERVER['REMOTE_ADDR']!='127.0.0.1';          // ob Fehler-Mails verschickt werden sollen


      // Stacktrace generieren
      $stackTrace   = debug_backtrace();
      $stackTrace[] = array('function' => 'main');                               // Damit der Stacktrace wie in Java aussieht, wird ein
      $size = sizeOf($stackTrace);                                               // zusätzlicher Frame fürs Hauptscript angefügt und alle
      for ($i=$size; $i-- > 0;) {                                                // FILE- und LINE-Felder um einen Frame nach unten verschoben.
         if (isSet($stackTrace[$i-1]['file']))
            $stackTrace[$i]['file'] = $stackTrace[$i-1]['file'];
         else
            unset($stackTrace[$i]['file']);

         if (isSet($stackTrace[$i-1]['line']))
            $stackTrace[$i]['line'] = $stackTrace[$i-1]['line'];
         else
            unset($stackTrace[$i]['line']);
      }

      array_shift($stackTrace);                                                  // Der erste Frame kann weg, er ist der Errorhandler selbst.
      if (!isSet($stackTrace[0]['file']))
         array_shift($stackTrace);                                               // Ist der zweite Frame ein interner PHP-Fehler, kann auch dieser weg.


      // Stacktrace lesbar formatieren
      $trace = null;
      $size = sizeOf($stackTrace);
      if ($size > 1) {
         $trace  = "Stacktrace:\n";
         $trace .= "-----------\n";
         $callLen = $lineLen = 0;

         for ($i=0; $i < $size; $i++) {                                          // Spalten LINE und FILE untereinander ausrichten
            $frame =& $stackTrace[$i];
            $call = null;
            if (isSet($frame['class']))
               $call = $frame['class'].$frame['type'];
            $call .= $frame['function'].'():';
            $callLen = max($callLen, strLen($call));
            $frame['call'] = $call;

            $frame['line'] = isSet($frame['line']) ? " # line $frame[line]," : '';
            $lineLen = max($lineLen, strLen($frame['line']));

            $frame['file'] = isSet($frame['file']) ? " file: $frame[file]" : ' [php]';
         }
         for ($i=0; $i < $size; $i++) {
            $trace .= str_pad($stackTrace[$i]['call'], $callLen).str_pad($stackTrace[$i]['line'], $lineLen).$stackTrace[$i]['file']."\n";
         }
      }


      static $levels = null;
      if ($levels === null) {
         $levels = array(E_PARSE             => 'Parse Error',
                         E_COMPILE_ERROR     => 'Compile Error',
                         E_COMPILE_WARNING   => 'Compile Warning',
                         E_CORE_ERROR        => 'Core Error',
                         E_CORE_WARNING      => 'Core Warning',
                         E_RECOVERABLE_ERROR => 'Error',
                         E_ERROR             => 'Error',
                         E_WARNING           => 'Warning',
                         E_NOTICE            => 'Notice',
                         E_STRICT            => 'Runtime Notice',
                         E_USER_ERROR        => 'Error',
                         E_USER_WARNING      => 'Warning',
                         E_USER_NOTICE       => 'Notice');
      }

      // Fehleranzeige
      $message = trim($message);
      $fullMessage = $levels[$level].': '.$message."\nin ".$file.' on line '.$line."\n";

      if ($display) {
         ob_get_level() ? ob_flush() : flush();
         if ($displayHtml) {
            echo nl2br('<div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.$levels[$level].'</b>: '.$message."\n in <b>".$file.'</b> on line <b>'.$line.'</b>');
            if ($trace)
               echo '<br>'.printFormatted($trace, true).'<br>';
            echo '</div>';
         }
         else {
            echo $fullMessage;                                                   // PHP gibt den Fehler unter Linux zusätzlich auf stderr aus,
            if ($trace)                                                          // also auf der Konsole ggf. unterdrücken ( 2>/dev/null )
               printFormatted("\n".$trace);
         }
      }

      // Fehler ins Error-Log schreiben
      if ($logErrors) {
         $logMsg = 'PHP '.str_replace(array("\r\n", "\n"), ' ', $fullMessage);   // alle Zeilenumbrüche entfernen
         error_log($logMsg, 0);
      }

      // Fehler-Email an alle registrierten Webmaster schicken
      if ($mailErrors) {
         if ($trace)
            $fullMessage .= "\n\n".$trace;
         $fullMessage .= "\n\nRequest:\n--------\n".getRequest()."\n\nIP: ".$_SERVER['REMOTE_ADDR']."\n---\n";
         $fullMessage = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $fullMessage)) : str_replace("\r\n", "\n", $fullMessage);

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($fullMessage, 1, $webmaster, 'Subject: PHP error_log: '.$levels[$level].' at '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
         }
      }

      // E_USER_WARNING und E_STRICT werden nur geloggt
      if ($level==E_USER_WARNING || $level==E_STRICT)
         return true;

      // Script beenden
      exit(1);
   }


   /**
    * Globaler Handler für nicht abgefangene Exceptions.
    *
    * Ablauf: - Anzeige der Exception (im Browser nur, wenn der Request von 'localhost' kommt)
    *         - Loggen im Errorlog
    *         - Verschicken von Fehler-Emails an alle registrierten Webmaster
    *         - Beenden des Scriptes
    *
    * @param Exception $exception - die zu behandelnde Exception
    */
   public static function handleException(Exception $exception) {
      $console     = !isSet($_SERVER['REQUEST_METHOD']);                         // ob das Script in der Konsole läuft
      $display     = $console || $_SERVER['REMOTE_ADDR']=='127.0.0.1';           // ob die Exception angezeigt werden soll (im Browser nur, wenn Request von localhost kommt)
      $displayHtml = $display && !$console;                                      // ob die Ausgabe HTML-formatiert werden muß
      $logErrors   = (ini_get('log_errors'));                                    // ob der Fehler geloggt werden soll
      $mailErrors  = !$console && $_SERVER['REMOTE_ADDR']!='127.0.0.1';          // ob Fehler-Mails verschickt werden sollen

      $msg  = $exception->getMessage();
      $file = $exception->getFile();
      $line = $exception->getLine();


      // Stacktrace generieren
      $stackTrace = $exception->getTrace();
      if ($exception instanceof PHPErrorException)                               // Ist die Exception eine PHPErrorException, kann der erste Frame weg.
         array_shift($stackTrace);                                               // (er ist der Errorhandler selbst)
      $stackTrace[] = array('function'=>'main');                                 // Damit der Stacktrace mit Java übereinstimmt, wird ein
      $size = sizeOf($stackTrace);                                               // zusätzlicher Frame fürs Hauptscript angefügt und alle
      for ($i=$size; $i-- > 0;) {                                                // FILE- und LINE-Felder um einen Frame nach unten verschoben.
         if (isSet($stackTrace[$i-1]['file']))
            $stackTrace[$i]['file'] = $stackTrace[$i-1]['file'];
         else
            unset($stackTrace[$i]['file']);

         if (isSet($stackTrace[$i-1]['line']))
            $stackTrace[$i]['line'] = $stackTrace[$i-1]['line'];
         else
            unset($stackTrace[$i]['line']);
      }
      $stackTrace[0]['file'] = $file;                                            // der erste Frame wird mit den Werten der Exception bestückt
      $stackTrace[0]['line'] = $line;


      // Stacktrace lesbar formatieren
      $size = sizeOf($stackTrace);
      $trace  = "Stacktrace:\n";
      $trace .= "-----------\n";
      $callLen = $lineLen = 0;

      for ($i=0; $i < $size; $i++) {                                             // Spalten LINE und FILE untereinander ausrichten
         $frame =& $stackTrace[$i];
         $call = null;
         if (isSet($frame['class']))
            $call = $frame['class'].$frame['type'];
         $call .= $frame['function'].'():';
         $callLen = max($callLen, strLen($call));
         $frame['call'] = $call;

         $frame['line'] = isSet($frame['line']) ? " # line $frame[line]," : '';
         $lineLen = max($lineLen, strLen($frame['line']));

         $frame['file'] = isSet($frame['file']) ? " file: $frame[file]" : ' [php]';
      }
      for ($i=0; $i < $size; $i++) {
         $trace .= str_pad($stackTrace[$i]['call'], $callLen).str_pad($stackTrace[$i]['line'], $lineLen).$stackTrace[$i]['file']."\n";
      }


      // Fehleranzeige
      $className = get_class($exception);
      $message = $className.': '.$msg."\nin ".$file.' on line '.$line."\n";
      if ($display) {
         if ($displayHtml) {
            ob_get_level() ? ob_flush() : flush();
            echo nl2br('<div align="left" style="font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.$className.'</b>: '.$msg."\n in <b>".$file.'</b> on line <b>'.$line.'</b>');
            echo '<br>'.printFormatted($trace, true).'<br></div>';
         }
         else {
            echo $message;
            printFormatted("\n".$trace);
         }
      }


      // Fehler ins Error-Log schreiben
      if ($logErrors) {
         $logMsg = 'PHP '.str_replace(array("\r\n", "\n"), ' ', $message);        // alle Zeilenumbrüche entfernen
         error_log($logMsg, 0);
      }


      // Fehler-Email an alle registrierten Webmaster schicken
      if ($mailErrors) {
         $message .= "\n\n".$trace;
         $message .= "\n\nRequest:\n--------\n".getRequest()."\n\nIP: ".$_SERVER['REMOTE_ADDR']."\n---\n";
         $message = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $message)) : str_replace("\r\n", "\n", $message);

         foreach ($GLOBALS['webmasters'] as $webmaster) {
            error_log($message, 1, $webmaster, 'Subject: PHP error_log: '.$className.' at '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
         }
      }

      // Script immer beenden
      exit(1);
   }
}
?>
