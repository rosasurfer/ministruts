<?php
/**
 * NestableException
 *
 * Basisklasse für Exceptions, die weitere ursächliche Exceptions kapseln können.
 */
abstract class NestableException extends Exception {


   /**
    * Die Exception, die diese Exception ausgelöst hat, oder NULL, wenn diese Exception
    * nicht durch eine andere Exception ausgelöst wurde.
    */
   private /*Exception*/ $cause;


   /* Cachevariable für den erzeugten Stacktrace */
   private /*string[][]*/ $trace;


   /* Cachevariable für den als String formatierten Stacktrace */
   private /*string*/ $traceString;


   /**
    * Erzeugt eine neue NestableException mit der angegebenen Nachricht und Ursache.
    *
    * Überladene Signatur:
    * --------------------
    *    1) new NestableException()
    *    2) new NestableException(Exception $cause)
    *    3) new NestableException(string $message)
    *    4) new NestableException(string $message, Exception $cause)
    *
    * @param mixed     $message - Nachricht (string) oder ursächliche Exception
    * @param Exception $cause   - ursächliche Exception
    */
   public function __construct($message = null, Exception $cause = null) {
      $args = func_num_args();

      if ($args == 0) {                         // new NestableException()
         parent:: __construct();
      }
      elseif ($args == 1) {
         if ($message instanceof Exception) {   // new NestableException(Exception $cause)
            $cause = $message;
            parent:: __construct();
            $this->cause = $cause;
         }
         else {                                 // new NestableException(string $message)
            parent:: __construct($message);
         }
      }
      else {                                    // new NestableException(string $message, Exception $cause)
         parent:: __construct($message);
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
    * Wandelt den übergebenen Stacktrace in einen Java-ähnlichen Stacktrace um.
    *
    * @parameter string[][] $trace - zu bearbeitender Stacktrace
    *
    * @return string[][] - java-ähnlicher Stacktrace
    */
   final public static function transformToJavaStackTrace(array $trace) {
      $trace[] = array('function' => 'main');      // Für die Java-Ähnlichkeit wird ein zusätzlicher Frame fürs Hauptscript angefügt und
                                                   // alle FILE- und LINE-Felder um eine Position nach hinten verschoben.
      for ($i=sizeOf($trace); $i--;) {
         if (isSet($trace[$i-1]['file']))
            $trace[$i]['file'] = $trace[$i-1]['file'];
         else
            unset($trace[$i]['file']);

         if (isSet($trace[$i-1]['line']))
            $trace[$i]['line'] = $trace[$i-1]['line'];
         else
            unset($trace[$i]['line']);
      }

      return $trace;
   }


   /**
    * Gibt den Stacktrace dieser Exception zurück.
    *
    * @return array - java-ähnlicher Stacktrace
    */
   public function getStackTrace() {
      $trace =& $this->trace;

      // Stacktrace anpassen und zwischenspeichern
      if ($trace === null) {
         $trace = self ::transformToJavaStackTrace(parent:: getTrace());

         // Der erste Frame wird mit den Werten der Exception bestückt.
         $trace[0]['file'] = $this->file;
         $trace[0]['line'] = $this->line;

         // Wurde die Exception in Object::__set() ausgelöst, Stacktrace modifizieren, so daß der falsche Aufruf im ersten Frame steht.
         while (strToLower($trace[0]['function']) == '__set')
            array_shift($trace);

         // Wurde die Exception in Object::__call() ausgelöst, Stacktrace modifizieren, so daß der falsche Aufruf im ersten Frame steht.
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
    * @param bool $return - ob die Ausgabe nicht auf STDOUT sondern als Rückgabe erfolgen soll
    *
    * @return string - NULL oder formatierter, java-ähnlicher Stacktrace
    */
   final public function printStackTrace($return = false) {
      $result =& $this->traceString;

      // Stacktrace formatieren und Ergebnis zwischenspeichern
      if ($result === null) {
         $result = self ::formatStackTrace($this->getStackTrace());

         if ($this->cause !== null) {
            $result .= "\n\n\ncaused by\n".$this->cause."\n\nStacktrace:\n-----------\n";
            if ($this->cause instanceof NestableException)
               $result .= $this->cause->printStackTrace(true);
            else {
               $result .= self ::formatStackTrace(self ::transformToJavaStackTrace($this->cause->getTrace()));
            }
         }
         $this->traceString =& $result;
      }

      if ($return)
         return $result;

      echo $result;
      return null;
   }


   /**
    * Gibt eine formatierte, lesbare Version eines Stacktrace zurück.
    *
    * @param array $trace - Stacktrace
    *
    * @return string - lesbarer Stacktrace
    */
   final public static function formatStackTrace(array $trace) {
      $result = null;

      $size = sizeOf($trace);
      $callLen = $lineLen = 0;

      for ($i=0; $i < $size; $i++) {                        // FILE und LINE untereinander ausrichten
         $frame =& $trace[$i];
         $call = null;
         if (isSet($frame['class']))
            $call = $frame['class'].$frame['type'];
         $call .= $frame['function'].'() ';
         $callLen = max($callLen, strLen($call));
         $frame['call'] = $call;

         $frame['line'] = isSet($frame['line']) ? " # line $frame[line]," : '';
         $lineLen = max($lineLen, strLen($frame['line']));

         $frame['file'] = isSet($frame['file']) ? " file: $frame[file]" : ' [php]';
      }

      for ($i=0; $i < $size; $i++) {
         $result .= str_pad($trace[$i]['call'], $callLen).str_pad($trace[$i]['line'], $lineLen).$trace[$i]['file']."\n";
      }

      return $result;
   }


   /**
    * Gibt eine Beschreibung dieser Exception zurück.
    *
    * @return string - String im Format Class:message
    */
   public function __toString() {
      $message = parent ::getMessage();

      if ($message !== null)
         $message = ': '.$message;

      return get_class($this).$message;
   }
}
?>
