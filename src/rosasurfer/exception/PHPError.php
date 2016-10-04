<?php
namespace rosasurfer\exception;

use rosasurfer\debug\ErrorHandler;
use rosasurfer\debug\Helper as DebugHelper;


/**
 * Rosasurfer exception for regular PHP errors wrapped in an exception
 */
class PHPError extends \ErrorException implements IRosasurferException {

   use /*trait*/ TRosasurferException;


   /** @var array */
   private $betterTrace;                     // better stacktrace


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
    * Return the exception's stacktrace in a more readable way (Java-like).
    *
    * @return array
    */
   public function getBetterTrace() {
      $trace = $this->betterTrace;

      if (!$trace) {
         // transform the original stacktrace into a better trace
         $trace = DebugHelper::fixTrace($this->getTrace());

         // drop the first frame if the exception was created in the registered error handler (it always should)
         if (DebugHelper::getFQFunctionName($trace[0]) == ErrorHandler::class.'::handleError') {
            array_shift($trace);

            // if error was triggered by include/require/_once: fix the next frame, it's simply wrong
            if (sizeOf($trace > 1)) {
               $function = DebugHelper::getFQFunctionName($trace[0]);
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
}
