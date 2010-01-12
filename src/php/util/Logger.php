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

   const DEFAULT_LOGLEVEL = L_NOTICE;


   private static /*bool*/ $display,   // Ob das Ereignis angezeigt werden soll.
                  /*bool*/ $mail;      // Ob eine E-Mail verschickt werden soll.


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
      //echoPre(__METHOD__.'(): $display: '.(int)self::$display.', $mail: '.(int)self::$mail);
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
         if ($logLevels === (string)$logLevels)
            $logLevels = array('' => $logLevels);

         foreach ($logLevels as $className => $level) {
            if ($level!==(string)$level)
               throw new IllegalTypeException('Illegal log level type ('.getType($level).') for class: '.$className);

            if     ($level == '')                     $logLevels[$className] = self:: DEFAULT_LOGLEVEL;
            elseif (defined('L_'.strToUpper($level))) $logLevels[$className] = constant('L_'.strToUpper($level));
            else
               throw new InvalidArgumentException('Invalid log level for class '.$className.': '.$level);
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
    *                   weitergereicht werden soll, als wenn der Errorhandler nicht registriert wäre
    *
    * NOTE: The error handler must return FALSE to populate $php_errormsg.
    */
   public static function handleError($level, $message, $file, $line, array $context) {
      //echoPre(__METHOD__.'(): '.self::$logLevels[$level].' '.$message.', $file: '.$file.', $line: '.$line);

      // absichtlich unterdrückte und vom aktuellen Errorlevel nicht abgedeckte Fehler ignorieren
      $error_reporting = error_reporting();

      if ($error_reporting == 0)                 return false;    // 0: @-Operator (see NOTE)
      if (($error_reporting & $level) != $level) return true;


      // Fehler in Exception kapseln ...
      $GLOBALS['$__PHPErrorException_create'] = true;    // Marker für Konstruktor von PHPErrorException
      $exception = new PHPErrorException($message, $file, $line, $context);


      // ... und behandeln
      if     ($level == E_USER_NOTICE ) self:: _log(null, $exception, L_NOTICE);
      elseif ($level == E_USER_WARNING) self:: _log(null, $exception, L_WARN  );
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

         // Bei manuellem Aufruf aus einem Destruktor kann die Exception zurückgereicht werden, sofern wir nicht im Shutdown sind
         // (während des Shutdowns dürfen keine Exceptions mehr geworfen werden)
         if ($destructor && !isSet($GLOBALS['$__shutting_down']))
            return;


         // 1. Fehlerdaten ermitteln
         $message  = ($exception instanceof NestableException) ? (string) $exception : get_class($exception).': '.$exception->getMessage();
         $traceStr = ($exception instanceof NestableException) ? "Stacktrace:\n-----------\n".$exception->printStackTrace(true) : 'Stacktrace not available';
         // TODO: vernestelte, einfache Exceptions geben fehlerhaften Stacktrace zurück
         $file     =  $exception->getFile();
         $line     =  $exception->getLine();
         $plainMessage = '[FATAL] Uncaught '.$message."\nin ".$file.' on line '.$line."\n";


         // 2. Exception anzeigen (wenn $display TRUE ist)
         if (self::$display) {
            if (isSet($_SERVER['REQUEST_METHOD'])) {
               echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>[FATAL] Uncaught</b> '.nl2br(htmlSpecialChars($message, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
               echo '<br>'.printFormatted($traceStr, true);
               echo "<br></div>\n";

               // Wurde ein Redirect-Header gesendet, ist die Ausgabe verloren und muß zusätzlich gemailt werden
               // (kann vorher nicht zuverlässig ermittelt werden, da die Header noch nicht gesendet sein können)
               foreach (headers_list() as $header) {
                  if (striPos($header, 'Location: ') === 0) {
                     self::$display = false;
                     self::$mail    = true;
                     break;
                  }
               }
            }
            else {
               echo $plainMessage."\n".$traceStr."\n";   // PHP gibt den Fehler unter Linux zusätzlich auf stderr aus,
            }                                            // also auf der Konsole ggf. unterdrücken
         }


         // 3. Exception an die registrierten Adressen mailen (wenn $mail TRUE ist) ...
         if (self::$mail && ($addresses = Config ::get('mail.address.buglovers'))) {
            $mailMsg  = $plainMessage."\n".$traceStr;

            if ($request=Request ::me()) {
               $session = $request->isSession() ? print_r($_SESSION, true) : null;

               $ip   = $_SERVER['REMOTE_ADDR'];
               $host = getHostByAddr($ip);
               if ($host != $ip)
                  $ip = $host.' ('.$ip.')';

               $mailMsg .= "\n\n\nRequest:\n--------\n".$request."\n\n\n"
                        .  "Session: ".($session ? "\n--------\n".$session."\n\n\n" : "  - no session -\n")
                        .  "Host:      ".$ip."\n"
                        .  "Timestamp: ".date('Y-m-d H:i:s')."\n";
            }
            else {
               $mailMsg .= "\n\n\nShell:\n------\n".print_r($_SERVER, true)."\n\n\n";
            }

            $mailMsg = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $mailMsg)) : str_replace("\r\n", "\n", $mailMsg);
            $mailMsg = str_replace(chr(0), "*\x00*", $mailMsg);

            $old_sendmail_from = ini_get('sendmail_from');
            if (isSet($_SERVER['SERVER_ADMIN']))
               ini_set('sendmail_from', $_SERVER['SERVER_ADMIN']);                           // nur für Windows relevant

            $addresses = Config ::get('mail.address.forced-receiver', $addresses);

            foreach (explode(',', $addresses) as $address) {
               // TODO: Adressformat validieren
               if ($address) {
                  // TODO: Header mit Fehlermeldung hinzufügen, damit beim Empfänger Messagefilter unterstützt werden
                  $success = error_log($mailMsg, 1, $address, 'Subject: PHP: [FATAL] Uncaught Exception at '.($request ? $request->getHostname():'').$_SERVER['PHP_SELF']);
                  if (!$success) {
                     error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", $plainMessage)), 0);
                     break;
                  }
               }
            }
            ini_set('sendmail_from', $old_sendmail_from);
         }

         // ... oder Exception ins Error-Log schreiben, falls sie nicht schon angezeigt wurde
         elseif (!self::$display) {
            error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", $plainMessage)), 0);       // Zeilenumbrüche entfernen
         }
      }
      catch (Exception $second) {
         $file = $exception->getFile();
         $line = $exception->getLine();
         error_log('PHP (1) '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", (string) $exception).' in '.$file.' on line '.$line), 0);

         $file = $second->getFile();
         $line = $second->getLine();
         error_log('PHP (2) '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", (string) $second).' in '.$file.' on line '.$line), 0);
      }
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

      if ($level!==(int)$level)    throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));
      if ($class!==(string)$class) throw new IllegalTypeException('Illegal type of parameter $class: '.getType($class));

      // was der jeweilige Loglevel nicht abdeckt, wird ignoriert
      if ($level < self ::getLogLevel($class))
         return;

      // Aufruf mit drei Argumenten
      if ($args == 3) {
         if ($message===null || $message===(string)$message)
            return self:: _log($message, null, $level);        // Logger::log($message  , $level, $class)
         if ($exception instanceof Exception)
            return self:: _log(null, $exception, $level);      // Logger::log($exception, $level, $class)
         throw new IllegalTypeException('Illegal type of first parameter: '.getType($arg1));
      }

      // Aufruf mit vier Argumenten
      if ($message!==null && $message!==(string)$message)        throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));
      if ($exception!==null && !$exception instanceof Exception) throw new IllegalTypeException('Illegal type of parameter $exception: '.(is_object($exception) ? get_class($exception) : getType($exception)));

      return self:: _log($message, $exception, $level);        // Logger::log($message, $exception, $level, $class)
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
      $plainMessage = null;

      try {
         if (!isSet(self::$logLevels[$level])) throw new InvalidArgumentException('Invalid log level: '.$level);
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
            array_shift($trace);          // die ersten beiden Frames können weg: 1. Logger::_log(), 2: Logger::log()

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


         // 2. Logmessage anzeigen (wenn $display TRUE ist)
         if (self::$display) {
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
                     self::$display = false;
                     self::$mail    = true;
                     break;
                  }
               }
            }
            else {
               echo $plainMessage.($exception ? "\n".$exMessage."\n":'')."\n".$trace."\n";
            }
         }


         // 3. Logmessage an die registrierten Adressen mailen (wenn $mail TRUE ist) ...
         if (self::$mail && ($addresses = Config ::get('mail.address.buglovers'))) {
            $mailMsg = $plainMessage.($exception ? "\n\n".$exMessage."\n":'')."\n\n".$trace;

            if ($request=Request ::me()) {
               $session = $request->isSession() ? print_r($_SESSION, true) : null;

               $ip   = $_SERVER['REMOTE_ADDR'];
               $host = getHostByAddr($ip);
               if ($host != $ip)
                  $ip = $host.' ('.$ip.')';

               $mailMsg .= "\n\n\nRequest:\n--------\n".$request."\n\n\n"
                        .  "Session: ".($session ? "\n--------\n".$session."\n\n\n" : "  - no session -\n")
                        .  "Host:      ".$ip."\n"
                        .  "Timestamp: ".date('Y-m-d H:i:s')."\n";
            }
            else {
               $mailMsg .= "\n\n\nShell:\n------\n".print_r($_SERVER, true)."\n\n\n";
            }

            $mailMsg = WINDOWS ? str_replace("\n", "\r\n", str_replace("\r\n", "\n", $mailMsg)) : str_replace("\r\n", "\n", $mailMsg);
            $mailMsg = str_replace(chr(0), "*\x00*", $mailMsg);

            $old_sendmail_from = ini_get('sendmail_from');
            if (isSet($_SERVER['SERVER_ADMIN']))
               ini_set('sendmail_from', $_SERVER['SERVER_ADMIN']);                           // nur für Windows relevant

            $addresses = Config ::get('mail.address.forced-receiver', $addresses);

            foreach (explode(',', $addresses) as $address) {
               // TODO: Adressformat validieren
               if ($address) {
                  // TODO: Header mit Fehlermeldung hinzufügen, damit beim Empfänger Messagefilter unterstützt werden
                  $success = error_log($mailMsg, 1, $address, 'Subject: PHP: '.self::$logLevels[$level].' at '.($request ? $request->getHostname():'').$_SERVER['PHP_SELF']);
                  if (!$success) {
                     error_log('PHP '.str_replace(array("\r\n", "\n"), ' ', str_replace(chr(0), "*\x00*", $plainMessage)), 0);
                     break;
                  }
               }
            }
            ini_set('sendmail_from', $old_sendmail_from);
         }

         // ... oder Logmessage ins Error-Log schreiben, falls sie nicht schon angezeigt wurde
         elseif (!self::$display) {
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
