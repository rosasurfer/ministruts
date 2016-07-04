<?php
namespace rosasurfer\ministruts\exceptions;


/**
 * PHPError
 *
 * Ein PHPError darf nur im globalen ErrorHandler erzeugt werden. Eigentlich müßte
 * PHPError daher eine private, innere Klasse des Errorhandlers sein.  Da dies in PHP nicht
 * möglich ist, setzt der Errorhandler vor dem Erzeugen eines neuen PHPError eine Markierung
 * (globale Variable $__PHPError_create), die im Constructor der Exception sofort wieder
 * gelöscht wird.
 *
 * @see Logger::handleError()
 */
class PHPError extends \Exception {


   private /*mixed[]   */ $context;
   private /*string[][]*/ $trace;         // Stacktrace
   private /*string    */ $traceString;   // Stacktrace als String formatiert


   /**
    * Constructor
    *
    * @param  mixed[] $context - aktive Symboltabelle des Punktes, an dem die Exception auftrat
    *                            An array that points to the active symbol table at the point the error occurred. In other words,
    *                            $context will contain an array of every variable that existed in the scope the error was triggered
    *                            in. User error handler must not modify error context.
    */
   public function __construct($message, $file, $line, array $context) {
      parent::__construct($message);

      $this->file    = $file;
      $this->line    = $line;
      $this->context = $context;
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
