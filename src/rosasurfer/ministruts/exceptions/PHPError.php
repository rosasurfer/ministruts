<?php
namespace rosasurfer\ministruts\exceptions;


/**
 * Rosasurfer exception for regular PHP errors wrapped in an exception
 */
class PHPError extends \ErrorException implements IRosasurferException {


   /** @var string */
   private $betterMessage;                   // better message

   /** @var array */
   private $betterTrace;                     // better stacktrace

   /** @var string */
   private $betterTraceAsString;             // better stacktrace as string


   /**
    * Create a new instance. Parameters are identical to the built-in PHP ErrorException and passed on.
    *
    * @param  string $message  - error description
    * @param  int    $code     - error identifier, usually an application id
    * @param  int    $severity - the error reporting of the PHP error
    * @param  string $file     - the name of the file where the error occurred
    * @param  int    $line     - the line number in the file where the error occurred
    */
   public function __construct($message, $code, $severity, $file, $line) {
      parent::__construct($message, $code, $severity, $file, $line, $cause=null);
   }


   /**
    * Return the exception's message in a more readable way.
    *
    * @return string - message
    */
   public function getBetterMessage() {
      if (!$this->betterMessage)
         $this->betterMessage = \Debug::getBetterMessage($this);
      return $this->betterMessage;
   }


   /**
    * Return the exception's stacktrace in a more readable way (Java-like).
    *
    * @return array
    */
   public function getBetterTrace() {
      $trace = $this->betterTrace;

      if (!$trace) {
         // transform the original stacktrace into a better trace
         $trace = \Debug::fixTrace($this->getTrace());

         // drop the first frame if the error was constructed in the error handler (it always should)
         if (\Debug::getFQFunctionName($trace[0]) == 'scx\commons\SCX::handleError') {
            array_shift($trace);

            // if error was triggered by include/require/_once: fix the next frame, it's simply wrong
            if (sizeOf($trace > 1)) {
               $function = \Debug::getFQFunctionName($trace[0]);
               if ($function=='include' || $function=='include_once' || $function=='require' || $function=='require_once') {
                  if (isSet($trace[0]['file']) && isSet($trace[1]['file'])) {
                     if ($trace[0]['file'] == $trace[1]['file']) {
                        if (isSet($trace[0]['line']) && isSet($trace[1]['line'])) {
                           if ($trace[0]['line'] == $trace[1]['line']) {
                              unset($trace[0]['file'], $trace[0]['line']);
                           }
                        }
                     }
                  }
               }
            }
         }
         // store the new stacktrace
         $this->betterTrace = $trace;
      }
      return $trace;
   }


   /**
    * Return a text representation of the exception's stacktrace in a more readable way (Java-like).
    * The representation also contains informations about nested exceptions.
    *
    * @return string
    */
   public function getBetterTraceAsString() {
      if (!$this->betterTraceAsString)
         $this->betterTraceAsString = \Debug::getBetterTraceAsString($this);
      return $this->betterTraceAsString;
   }


   /**
    * Return a description of the exception.
    *
    * @return string - description
    */
   public function __toString() {
      return $this->getBetterMessage();
   }
}
