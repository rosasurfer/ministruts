<?
/**
 * PHPErrorException
 *
 * Eine PHPErrorException darf nur im globalen ErrorHandler erzeugt werden. Eigentlich müßte
 * PHPErrorException daher eine innere Klasse des ErrorHandlers mit privatem Konstruktor sein.
 * Das ist in PHP nicht möglich. Deshalb setzt der ErrorHandler vor dem Erzeugen einer Instanz
 * einen Marker ($GLOBALS['$__php_error_create']), der im Konstruktor der Exception sofort
 * wieder gelöscht wird.
 *
 * @see Logger::handleError()
 */
class PHPErrorException extends NestableException {


   // Cache-Variable für den erzeugten Stacktrace
   private $trace;

   // Cache-Variable für den als String formatierten Stacktrace
   private $traceString;


   private $context;


   /**
    * Constructor
    */
   public function __construct($message, $file, $line, array $context) {
      // prüfen, ob wir außerhalb des ErrorHandler aufgerufen wurden
      if (!isSet($GLOBALS['$__php_error_create']))
         throw new RuntimeException('Illegal access to non-public constructor of '.__CLASS__.' (see documentation)');

      unset($GLOBALS['$__php_error_create']);      // Marker entfernen

      parent:: __construct($message);

      $this->file =  $file;
      $this->line =  $line;
      $this->vars =& $context;
   }


   /**
    * Gibt den Stacktrace dieser Exception zurück.
    *
    * @return array - Java-ähnlicher Stacktrace
    */
   public function &getStackTrace() {
      $trace = $this->trace;

      if ($trace === null) {
         $trace =& parent ::getStackTrace();

         // Der erste Frame (ErrorHandler) und alle weiteren Frames der ErrorHandlerkette können weg.
         array_shift($trace);
         while ($this->file!=$trace[0]['file'] || $this->line!=$trace[0]['line'])
            array_shift($trace);

         // Der nächste Frame kann weg, wenn er auf __autoload zeigt.
         if (strToLower($trace[0]['function']) == '__autoload')
            array_shift($trace);

         $this->trace =& $trace;
      }

      return $trace;
   }
}
?>
