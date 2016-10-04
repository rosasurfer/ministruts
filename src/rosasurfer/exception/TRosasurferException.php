<?php
namespace rosasurfer\exception;

use rosasurfer\debug\Helper as DebugHelper;


/**
 * A trait implementing common functionality for all Rosasurfer exceptions.
 */
trait TRosasurferException {


   /** @var string */
   private $betterMessage;                   // better message

   /** @var string */
   private $betterTraceAsString;             // better stacktrace as string


   /**
    * Return the exception's message in a more readable way.
    *
    * @return string - message
    */
   public function getBetterMessage() {
      if (!$this->betterMessage)
         $this->betterMessage = DebugHelper::getBetterMessage($this);
      return $this->betterMessage;
   }


   /**
    * Return a text representation of the exception's stacktrace in a more readable way (Java-like).
    *
    * @return string
    */
   public function getBetterTraceAsString() {
      if (!$this->betterTraceAsString)
         $this->betterTraceAsString = DebugHelper::getBetterTraceAsString($this);
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
