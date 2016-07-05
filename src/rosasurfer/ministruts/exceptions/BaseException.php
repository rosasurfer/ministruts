<?php
namespace rosasurfer\ministruts\exceptions;


/**
 * Base exception for all Rosasurfer exceptions
 */
class BaseException extends \Exception implements IRosasurferException {


   /** @var string */
   private $betterMessage;                   // better message

   /** @var array */
   private $betterTrace;                     // better stacktrace

   /** @var string */
   private $betterTraceAsString;             // better stacktrace as string


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
    * Return the exception's message in a more readable way.
    *
    * @return string - message
    */
   public function getBetterMessage() {
      if (!$this->betterMessage)
         $this->betterMessage = \DebugTools::getBetterMessage($this);
      return $this->betterMessage;
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
         $betterTrace = \DebugTools::fixTrace($this->getTrace(), $this->getFile(), $this->getLine());

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


   /**
    * Return a text representation of the exception's stacktrace in a more readable way (Java-like).
    * The representation also contains informations about nested exceptions.
    *
    * @return string
    */
   public function getBetterTraceAsString() {
      if (!$this->betterTraceAsString)
         $this->betterTraceAsString = \DebugTools::getBetterTraceAsString($this);
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
