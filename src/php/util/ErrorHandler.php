<?
/**
 * ErrorHandler
 */
class ErrorHandler extends Object {


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
         Logger ::log($exception, L_WARN);
      }
      elseif ($level == E_STRICT) {                      // E_STRICT darf nicht zurückgeworfen werden und wird deshalb manuell weitergeleitet
         self:: handleException($exception);
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
      Logger ::log($exception, L_FATAL);
      exit(1);
   }
}
?>
