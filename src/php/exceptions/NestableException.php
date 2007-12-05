<?
/**
 * NestableException
 *
 * Basisklasse für Exceptions, die weitere ursächliche Exceptions enthalten können.
 */
abstract class NestableException extends Exception {


   /**
    * Die Exception, die diese Exception ausgelöst hat, oder NULL, wenn diese Exception
    * nicht durch eine andere Exception ausgelöst wurde.
    */
   private $cause;

   /* Cachevariable für den erzeugten Stacktrace */
   private $trace;

   /* Cachevariable für den als String formatierten Stacktrace */
   private $traceString;


   /**
    * Erzeugt eine neue NestableException mit der angegebenen Nachricht und Ursache.
    *
    * @param mixed - Nachricht (String) oder Ursache (Exception) dieser Exception
    * @param cause - Ursache oder Auslöser dieser Exception
    */
   public function __construct($mixed = null, Exception $cause = null) {

      // Dieser Code implementiert 4 verschiedene Klassenkonstruktoren (wie für Java-Exceptions;
      // siehe: http://java.sun.com/j2se/1.5.0/docs/api/java/lang/Exception.html#constructor_summary)
      $args = func_num_args();

      if ($args == 0) {                                        // new NestableException()
         parent:: __construct();
      }
      elseif ($args == 1) {
         if ($mixed instanceof Exception) {                    // new NestableException(Exception $cause)
            parent:: __construct();
            $this->cause = $mixed;
         }
         else {                                                // new NestableException(String $message)
            parent:: __construct($mixed);
         }
      }
      else {                                                   // new NestableException(String $message, Exception $cause)
         parent:: __construct($mixed);
         $this->cause = $cause;
      }
   }


   /**
    * Gibt die Exception zurück, die diese Exception ausgelöst hat.
    *
    * @return exception - Ursache oder NULL, wenn diese Exception nicht durch eine andere Exception
    *                     ausgelöst wurde.
    */
   public function getCause() {
      return $this->cause;
   }


   /**
    * Gibt den Stacktrace dieser Exception zurück.
    *
    * @return array - java-ähnlicher Stacktrace
    */
   public function &getStackTrace() {
      $trace = $this->trace;

      // Originalstacktrace anpassen und zwischenspeichern
      if ($trace === null) {
         $trace = parent:: getTrace();

         $trace[] = array('function' => 'main');                     // Damit der Stacktrace mit Java übereinstimmt, wird ein zusätzlicher Frame fürs
         $size = sizeOf($trace);                                     // Hauptscript angefügt und alle FILE- und LINE-Felder einen Frame nach unten verschoben.

         for ($i=$size; $i-- > 0;) {
            if (isSet($trace[$i-1]['file']))
               $trace[$i]['file'] = $trace[$i-1]['file'];
            else
               unset($trace[$i]['file']);

            if (isSet($trace[$i-1]['line']))
               $trace[$i]['line'] = $trace[$i-1]['line'];
            else
               unset($trace[$i]['line']);
         }

         $trace[0]['file'] = $this->file;                            // Der erste Frame wird mit den Werten der Exception bestückt.
         $trace[0]['line'] = $this->line;

         // Wurde die Exception in Object::__set() geworfen, Stacktrace modifizieren, so daß der falsche Aufruf im ersten Frame steht.
         while (strToLower($trace[0]['function']) == '__set')
            array_shift($trace);

         // Wurde die Exception in Object::__call() geworfen, Stacktrace modifizieren, so daß der falsche Aufruf im ersten Frame steht.
         if (strToLower($trace[0]['function']) == '__call') {
            while (strToLower($trace[0]['function']) == '__call')
               array_shift($trace);
            array_shift($trace);
         }

         $this->trace =& $trace;
      }
      return $trace;
   }


   /**
    * Gibt den formatierten Stacktrace dieser Exception aus.
    *
    * @param boolean $return - ob die Ausgabe nicht auf STDOUT sondern als Rückgabe erfolgen soll
    *
    * @return string - NULL oder formatierter, java-ähnlicher Stacktrace
    */
   public function printStackTrace($return = false) {
      $string = $this->traceString;

      // Stacktrace formatieren und Ergebnis zwischenspeichern
      if ($string === null) {
         $trace =& $this->getStackTrace();

         $size = sizeOf($trace);
         $callLen = $lineLen = 0;

         for ($i=0; $i < $size; $i++) {                        // Spalten LINE und FILE untereinander ausrichten
            $frame =& $trace[$i];
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
            $string .= str_pad($trace[$i]['call'], $callLen).str_pad($trace[$i]['line'], $lineLen).$trace[$i]['file']."\n";
         }
         if ($this->cause !== null) {
            if ($this->cause instanceof NestableException)
               $string .= "\n\ncaused by\n".$this->cause."\n\nStacktrace:\n-----------\n".$this->cause->printStackTrace(true);
            else
               $string .= "\n\ncaused by\n".$this->cause."\n\nStacktrace not available\n";
         }

         $this->traceString = $string;
      }

      if ($return)
         return $string;

      echo $string;
      return null;
   }


   /**
    * Gibt eine Beschreibung dieser Exception zurück.
    *
    * @return string - String im Format Class:message
    */
   public function __toString() {
      $className = get_class($this);

      $message = parent:: getMessage();
      if ($message !== null)
         $message = ': '.$message;

      //if ($this->cause !== null)
      //   $message .= ' ('.get_class($this->cause).')';

      return $className.$message;
   }
}
?>
