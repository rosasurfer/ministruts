<?
/**
 * PHPErrorException
 *
 * Eine PHPErrorException sollte nur im globalen ErrorHandler oder in einer magischen PHP-Methode
 * erzeugt werden.
 */
class PHPErrorException extends NestableException {


   // Cache-Variable für den erzeugten Stacktrace
   private $trace;

   // Cache-Variable für den als String formatierten Stacktrace
   private $traceString;

   private $vars;


   /**
    * Constructor
    */
   public function __construct($message, $file, $line, array $vars) {
       parent:: __construct($message);

       $this->file =  $file;
       $this->line =  $line;
       $this->vars =& $vars;
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

         array_shift($trace);                                     // Der erste Frame kann weg, er ist der ErrorHandler.
         if (strToLower($trace[0]['function']) == '__autoload')
            array_shift($trace);                                  // Der nächste Frame kann weg, wenn er auf __autoload zeigt.

         $this->trace =& $trace;
      }

      return $trace;
   }
}
?>
