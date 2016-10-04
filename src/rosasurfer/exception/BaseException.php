<?php
namespace rosasurfer\exception;

use rosasurfer\debug\Helper as DebugHelper;


/**
 * Base exception for all Rosasurfer exceptions
 */
class BaseException extends \Exception implements RosasurferExceptionInterface {

   use RosasurferExceptionTrait;


   /** @var array - better stacktrace */
   private $betterTrace;


   /**
    * Create a new instance. Parameters are identical to the built-in PHP Exception and passed on.
    *
    * @param  string    $message - exception description                           (default: null)
    * @param  int       $code    - exception identifier, usually an application id (default: null)
    * @param  Exception $cause   - another exception causing this exception
    */
   public function __construct($message=null, $code=null, \Exception $cause=null) {
      parent::__construct($message, $code, $cause);
   }


   /**
    * Return the exception's stacktrace in a more readable way (Java-like).
    *
    * @return array
    */
   public function getBetterTrace() {
      $betterTrace = $this->betterTrace;

      if (!$betterTrace) {
         // transform the original stacktrace into a better trace
         $betterTrace = DebugHelper::fixTrace($this->getTrace(), $this->getFile(), $this->getLine());

         /*
         // if the exception was thrown in a magic "__set()" shift frames until we reach the erroneous assignment
         while (strToLower($trace[0]['function']) == '__set') {
            array_shift($trace);
         }

         // if the exception was thrown in a magic "__call()" shift frames until we reach the erroneous call
         if (strToLower($trace[0]['function']) == '__call') {
            while (strToLower($trace[0]['function']) == '__call') {
               array_shift($trace);
            }
            array_shift($trace);                               // that's one level more than for "__set()"
         }
         */

         // store the new stacktrace
         $this->betterTrace = $betterTrace;
      }
      return $betterTrace;
   }
}
