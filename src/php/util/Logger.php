<?
/**
 * Logger
 *
 * TODO: Logger muß erweitert werden können
 */
class Logger extends StaticClass {

   /**
    * Diese Klasse sollte möglichst wenige externe Abhängigkeiten haben, um während der Fehlerverarbeitung
    * auftretende weitere Fehler zu verhindern.
    */

   private static /*bool*/ $display;   // Ob das Ereignis angezeigt werden soll.
   private static /*bool*/ $mail;      // Ob eine E-Mail verschickt werden soll.


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
      if (self::$display !== null)
         return;

      $console  = !isSet($_SERVER['REQUEST_METHOD']); // ob wir in einer Shell laufen
      $terminal = WINDOWS || (bool) getEnv('TERM');   // ob wir ein Terminal haben

      self::$display = ($console && $terminal)
                    || (isSet($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']=='127.0.0.1')
                    || (bool) ini_get('display_errors');

      self::$mail = !self::$display || ($console && !$terminal);
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
         // Loglevel-Konfiguration verarbeiten
         $logLevels = Config ::get('logger', array());
         if (is_string($logLevels))
            $logLevels = array('' => $logLevels);

         foreach ($logLevels as $class => $level) {
            if (!is_string($level))
               throw new IllegalTypeException('Illegal log level type ('.getType($level).') for class : '.$class);
            $logLevels[$class] = constant('L_'.strToUpper($level));
         }
      }

      // Loglevel abfragen
      if (isSet($logLevels[$class]))
         return $logLevels[$class];

      return L_NOTICE;              // Default-Loglevel
   }


   /**
    * Gibt den angegebenen Errorlevel in lesbarer Form zurück.
    *
    * @param int $level - Errorlevel, ohne Angabe wird der aktuellen Errorlevel des laufenden Scriptes
    *                     ausgewertet.
    * @return string
    */
   public static function getErrorLevelAsString($level=null) {
      if (func_num_args() && !is_int($level)) throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));

      $levels = array();
      if (!$level)
         $level = error_reporting();

      if (($level & E_ERROR            ) == E_ERROR            ) $levels[] = 'E_ERROR';
      if (($level & E_WARNING          ) == E_WARNING          ) $levels[] = 'E_WARNING';
      if (($level & E_PARSE            ) == E_PARSE            ) $levels[] = 'E_PARSE';
      if (($level & E_NOTICE           ) == E_NOTICE           ) $levels[] = 'E_NOTICE';
      if (($level & E_CORE_ERROR       ) == E_CORE_ERROR       ) $levels[] = 'E_CORE_ERROR';
      if (($level & E_CORE_WARNING     ) == E_CORE_WARNING     ) $levels[] = 'E_CORE_WARNING';
      if (($level & E_COMPILE_ERROR    ) == E_COMPILE_ERROR    ) $levels[] = 'E_COMPILE_ERROR';
      if (($level & E_COMPILE_WARNING  ) == E_COMPILE_WARNING  ) $levels[] = 'E_COMPILE_WARNING';
      if (($level & E_USER_ERROR       ) == E_USER_ERROR       ) $levels[] = 'E_USER_ERROR';
      if (($level & E_USER_WARNING     ) == E_USER_WARNING     ) $levels[] = 'E_USER_WARNING';
      if (($level & E_USER_NOTICE      ) == E_USER_NOTICE      ) $levels[] = 'E_USER_NOTICE';
      if (($level & E_RECOVERABLE_ERROR) == E_RECOVERABLE_ERROR) $levels[] = 'E_RECOVERABLE_ERROR';
      if (($level & E_ALL              ) == E_ALL              ) $levels[] = 'E_ALL';
      if (($level & E_STRICT           ) == E_STRICT           ) $levels[] = 'E_STRICT';

      return join(' | ', $levels).' ('.$level.')';
   }


   /**
    * Globaler Handler für herkömmliche PHP-Fehler. Die Fehler werden in einer PHPErrorException
    * gekapselt und je nach Error-Level behandelt.  E_USER_NOTICE und E_USER_WARNING werden nur
    * geloggt (kein Scriptabbruch).
    *
    * @param int    $level   - Error-Level
    * @param string $message - Error-Message
    * @param string $file    - Datei, in der der Fehler auftrat
    * @param int    $line    - Zeile der Datei, in der der Fehler auftrat
    * @param array  $context - aktive Symboltabelle des Punktes, an dem der Fehler auftrat
    *
    * @return boolean - TRUE,  wenn der Fehler erfolgreich behandelt wurde, FALSE, wenn der Fehler
    *                   weitergereicht werden soll, als wenn der ErrorHandler nicht registriert wäre
    */
   public static function handleError($level, $message, $file, $line, array $context) {
      // absichtlich unterdrückte und vom aktuellen Errorlevel nicht abgedeckte Fehler ignorieren
      $error_reporting = error_reporting();     // 0: @-Operator

      if ($error_reporting==0 || ($error_reporting & $level) != $level)
         return true;


      // Fehler in Exception kapseln ...
      $GLOBALS['$__php_error_create'] = true;      // Marker für Konstruktor von PHPErrorException
      $exception = new PHPErrorException($message, $file, $line, $context);


      // ... und behandeln
      if     ($level == E_USER_NOTICE ) self:: _log(null, $exception, L_NOTICE);
      elseif ($level == E_USER_WARNING) self:: _log(null, $exception, L_WARN  );
      else {
         // TODO: Destructors müssen komplett in einem try-catch-Block gekapselt werden
         // prüfen, ob wir aus einem Destructor kommen
         $destructor = false;
         foreach ($exception->getStackTrace() as $frame) {
            if (isSet($frame['class']) && isSet($frame['function']) && strToLower($frame['function'])=='__destruct') {
               $destructor = true;
               break;
            }
         }

         // Spezialfälle, die nicht zurückgeworfen werden dürfen/können
         if ($level==E_STRICT || ($file=='Unknown' && $line==0) || $destructor) {
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
    * Der Aufruf kann automatisch (globaler ErrorHandler) oder manuell (Codeteile, die keine Exceptions werfen dürfen)
    * erfolgt sein.
    *
    * @param Exception $exception - die zu behandelnde Exception
    */
   public static function handleException(Exception $exception) {
      self ::init();

      // 1. Fehlerdaten ermitteln
      $message  = ($exception instanceof NestableException) ? (string) $exception : get_class($exception).': '.$exception->getMessage();
      $traceStr = ($exception instanceof NestableException) ? "Stacktrace:\n-----------\n".$exception->printStackTrace(true) : 'Stacktrace not available';
      $file     =  $exception->getFile();
      $line     =  $exception->getLine();
      $plainMessage = 'Uncaught '.$message."\nin ".$file.' on line '.$line."\n";


      // 2. Exception anzeigen (wenn $display TRUE ist)
      if (self::$display) {
         while (@ob_end_flush()) ;
         flush();

         if (isSet($_SERVER['REQUEST_METHOD'])) {
            echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>Uncaught</b> '.nl2br(htmlSpecialChars($message, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
            echo '<br>'.htmlSpecialChars($message, ENT_QUOTES).'<br><br>'.printFormatted($traceStr, true);
            echo "<br></div>\n";
         }
         else {
            echo $plainMessage."\n\n".$message."\n\n\n".$traceStr."\n";    // PHP gibt den Fehler unter Linux zusätzlich auf stderr aus,
         }                                                                 // also auf der Konsole ggf. unterdrücken
      }


      // 3. Exception an die registrierten Adressen mailen (wenn $mail TRUE ist) ...
      if (self::$mail && ($addresses = explode(',', Config ::get('mail.buglovers')))) {
         $mailMsg  = $plainMessage."\n\n".$message."\n\n\n".$traceStr;

         $request = Request ::me();
         $session = $request && $request->isSession() ? print_r($_SESSION, true) : null;

         $ip   = $_SERVER['REMOTE_ADDR'];
         $host = getHostByAddr($ip);
         if ($host != $ip)
            $ip = $host.' ('.$ip.')';

         $mailMsg .= "\n\n\nRequest:\n--------\n".$request."\n\n\n"
                  .  "Session: ".($session ? "\n--------\n".$session."\n\n\n" : "  (no session)\n")
                  .  "Host:      ".$ip."\n"
                  .  "Timestamp: ".date('Y-m-d H:i:s')."\n";

         $mailMsg = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $mailMsg)) : str_replace("\r\n", "\n", $mailMsg);

         $old_sendmail_from = ini_get('sendmail_from');
         if (isSet($_SERVER['SERVER_ADMIN']))
            ini_set('sendmail_from', $_SERVER['SERVER_ADMIN']);                           // nur für Windows relevant

         foreach ($addresses as $address) {
            // TODO: Adressformat validieren
            if ($address) {
               // TODO: Header mit Fehlermeldung hinzufügen, damit beim Empfänger Messagefilter unterstützt werden
               $success = error_log($mailMsg, 1, $address, 'Subject: PHP error_log: Uncaught Exception at '.(isSet($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '').$_SERVER['PHP_SELF']);
               if (!$success) {
                  error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', $plainMessage), 0);
                  break;
               }
            }
         }
         ini_set('sendmail_from', $old_sendmail_from);
      }

      // ... oder Exception ins Error-Log schreiben, falls sie nicht schon angezeigt wurde
      elseif (!self::$display) {
         error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', $plainMessage), 0);      // Zeilenumbrüche entfernen
      }


      // 4. Script beenden
      exit(1);
   }


   /**
    * Loggt eine Message und/oder eine Exception. Überladene Methode.
    *
    * Methodensignaturen:
    * -------------------
    * Logger::log($message,             $level, $class)
    * Logger::log(          $exception, $level, $class)
    * Logger::log($message, $exception, $level, $class)
    */
   public static function log($arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null) {
      $message = $exception = $level = $class = null;

      $args = func_num_args();
      if ($args == 3) {
         $message = $exception = $arg1;
         $level   = $arg2;
         $class   = $arg3;
      }
      elseif ($args == 4) {
         $message   = $arg1;
         $exception = $arg2;
         $level     = $arg3;
         $class     = $arg4;
      }
      else {
         throw new InvalidArgumentException('Invalid number of arguments: '.$args);
      }

      if (!is_int($level))    throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));
      if (!is_string($class)) throw new IllegalTypeException('Illegal type of parameter $class: '.getType($class));

      // was der jeweilige Loglevel nicht abdeckt, wird ignoriert
      if ($level < self ::getLogLevel($class))
         return;

      // Aufruf mit drei Argumenten
      if ($args == 3) {
         if ($message===null || is_string($message))
            return self:: _log($message, null, $level);           // Logger::log($message  , $level, $class)
         if ($exception instanceof Exception)
            return self:: _log(null, $exception, $level);         // Logger::log($exception, $level, $class)
         throw new IllegalTypeException('Illegal type of first parameter: '.getType($arg1));
      }

      // Aufruf mit vier Argumenten
      if ($message!==null && !is_string($message))               throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));
      if ($exception!==null && !$exception instanceof Exception) throw new IllegalTypeException('Illegal type of parameter $exception: '.(is_object($exception) ? get_class($exception) : getType($exception)));

      return self:: _log($message, $exception, $level);           // Logger::log($message, $exception, $level, $class)
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
   private static function _log($message, Exception $exception = null, $level) {
      if (!isSet(self::$logLevels[$level])) throw new InvalidArgumentException('Invalid log level: '.$level);
      self ::init();

      // 1. Logdaten ermitteln
      $exMessage = $exTraceStr = null;
      if ($exception) {
         $message   .= ($message === null) ? (string) $exception : ' ('.get_class($exception).')';
         $exMessage  = ($exception instanceof NestableException) ? (string) $exception : get_class($exception).': '.$exception->getMessage();;
         $exTraceStr = ($exception instanceof NestableException) ? "Stacktrace:\n-----------\n".$exception->printStackTrace(true) : 'Stacktrace not available';
      }

      $trace = debug_backtrace();

      while (!isSet($trace[1]['file']))      // wenn 'file' nicht existiert, kommt der Aufruf aus dem Kernel (z.B. Errorhandler)
         array_shift($trace);                // also Einstiegspunkt im User-Code suchen
      $file = $trace[1]['file'];
      $line = $trace[1]['line'];

      $plainMessage = self::$logLevels[$level].': '.$message."\nin ".$file.' on line '.$line."\n";


      // 2. Logmessage anzeigen (wenn $display TRUE ist)
      if (self::$display) {
         while (@ob_end_flush()) ;
         flush();

         if (isSet($_SERVER['REQUEST_METHOD'])) {
            echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.self::$logLevels[$level].'</b>: '.nl2br(htmlSpecialChars($message, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
            if ($exception)
               echo '<br>'.htmlSpecialChars($exMessage, ENT_QUOTES).'<br><br>'.printFormatted($exTraceStr, true);
            echo "<br></div>\n";
         }
         else {
            echo $plainMessage;
            if ($exception)
               echo "\n".$exMessage."\n\n".$exTraceStr."\n";
         }
      }


      // 3. Logmessage an die registrierten Adressen mailen (wenn $mail TRUE ist) ...
      if (self::$mail && ($addresses = explode(',', Config ::get('mail.buglovers')))) {
         $mailMsg = $plainMessage;
         if ($exception)
            $mailMsg .= "\n\n".$exMessage."\n\n\n".$exTraceStr;

         $request = Request ::me();
         $session = $request && $request->isSession() ? print_r($_SESSION, true) : null;

         $ip   = $_SERVER['REMOTE_ADDR'];
         $host = getHostByAddr($ip);
         if ($host != $ip)
            $ip = $host.' ('.$ip.')';

         $mailMsg .= "\n\n\nRequest:\n--------\n".$request."\n\n\n"
                  .  "Session: ".($session ? "\n--------\n".$session."\n\n\n" : "  (no session)\n")
                  .  "Host:      ".$ip."\n"
                  .  "Timestamp: ".date('Y-m-d H:i:s')."\n";

         $mailMsg = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $mailMsg)) : str_replace("\r\n", "\n", $mailMsg);

         $old_sendmail_from = ini_get('sendmail_from');
         if (isSet($_SERVER['SERVER_ADMIN']))
            ini_set('sendmail_from', $_SERVER['SERVER_ADMIN']);                           // nur für Windows relevant

         foreach ($addresses as $address) {
            // TODO: Adressformat validieren
            if ($address) {
               // TODO: Header mit Fehlermeldung hinzufügen, damit beim Empfänger Messagefilter unterstützt werden
               $success = error_log($mailMsg, 1, $address, 'Subject: PHP error_log: '.self::$logLevels[$level].' at '.(isSet($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '').$_SERVER['PHP_SELF']);
               if (!$success) {
                  error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', $plainMessage), 0);
                  break;
               }
            }
         }
         ini_set('sendmail_from', $old_sendmail_from);
      }

      // ... oder Logmessage ins Error-Log schreiben, falls sie nicht schon angezeigt wurde
      elseif (!self::$display) {
         error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', $plainMessage), 0);      // Zeilenumbrüche entfernen
      }
   }
}
?>
