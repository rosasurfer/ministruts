<?
/**
 * PHPErrorException
 *
 * Eine PHPErrorException darf nur im globalen ErrorHandler erzeugt werden. Eigentlich müßte
 * PHPErrorException daher eine private, innere Klasse des Errorhandlers sein.  Da dies in PHP nicht
 * möglich ist, setzt der Errorhandler vor dem Erzeugen einer neuen PHPErrorException eine Markierung
 * (globale Variable $__PHPErrorException_create), die im Konstruktor der Exception sofort wieder
 * gelöscht wird.
 *
 * @see Logger::handleError()
 */
class PHPErrorException extends NestableException {


   private /*arry*/   $context;
   private /*array*/  $trace;          // Stacktrace
   private /*string*/ $traceString;    // Stacktrace als String formatiert


   /**
    * Constructor
    */
   public function __construct($message, $file, $line, array $context) {
      // prüfen, ob wir außerhalb des ErrorHandler aufgerufen wurden
      if (!isSet($GLOBALS['$__PHPErrorException_create']))
         throw new RuntimeException('Illegal access to non-public constructor of '.__CLASS__.' (see documentation)');

      unset($GLOBALS['$__PHPErrorException_create']);    // Marker entfernen

      parent:: __construct($message);

      $this->file =  $file;
      $this->line =  $line;
      $this->vars =& $context;
   }


   /**
    * Gibt den Stacktrace dieser Exception zurück.
    *
    * @return array - Stacktrace
    */
   public function getStackTrace() {
      $trace =& $this->trace;

      if ($trace === null) {
         $trace = parent ::transformToJavaStackTrace(parent:: getTrace());

         // Die ersten beiden Frames können weg: 1. ErrorHandler (Logger::handleError), 2: Handlerdefinition (__lambda_func)
         array_shift($trace);
         array_shift($trace);

         // Der nächste Frame kann weg, wenn er auf __autoload zeigt.
         $frame = $trace[0];
         if (!isSet($frame['class']) && strToLower($frame['function'])=='__autoload')
            array_shift($trace);

         $this->trace =& $trace;
      }

      return $trace;
   }
}
?>
