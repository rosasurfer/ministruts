<?
/**
 * ErrorHandler
 */
class ErrorHandler {


   /**
    * @param level -
    * @param msg   -
    * @param file  -
    * @param line  -
    * @param vars  -
    *
    * @return boolean - true, wenn der Fehler erfolgreich behandelt wurde
    *                   false, wenn der Fehler weitergereicht werden soll, als wenn der ErrorHandler nicht registriert wäre
    */
   public static function handleError($level, $message, $file, $line, array $vars) {
      return false;
   }


   /**
    * @param exception - die zu behandelnde Exception
    */
   public static function handleException(Exception $exception) {
   }
}
?>
