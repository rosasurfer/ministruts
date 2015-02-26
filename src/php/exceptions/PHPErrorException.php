<?php
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


   private /*mixed[]   */ $context;
   private /*string[][]*/ $trace;         // Stacktrace
   private /*string    */ $traceString;   // Stacktrace als String formatiert


   /**
    * Constructor
    *
    * @param mixed[] $context - aktive Symboltabelle des Punktes, an dem die Exception auftrat
    *                           (An array that points to the active symbol table at the point the error occurred. In other words, $context will contain an array
    *                            of every variable that existed in the scope the error was triggered in. User error handler must not modify error context.)
    */
   public function __construct($message, $file, $line, array $context) {
      // prüfen, ob wir außerhalb des ErrorHandler aufgerufen wurden
      if (!isSet($GLOBALS['$__PHPErrorException_create']))
         throw new plRuntimeException('Illegal access to non-public constructor of '.__CLASS__.' (see documentation)');

      unset($GLOBALS['$__PHPErrorException_create']);    // Marker entfernen

      parent:: __construct($message);

      $this->file =  $file;
      $this->line =  $line;
      $this->vars =& $context;   // ??? statt $this->context wird $this->vars gesetzt ???
   }


   /**
    * Gibt den Stacktrace dieser Exception zurück.
    *
    * @return string[][] - Stacktrace
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
