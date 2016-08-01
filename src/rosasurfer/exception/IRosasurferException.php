<?php
namespace rosasurfer\exception;


/**
 * An interface defining common functionality for all Rosasurfer exceptions.
 */
interface IRosasurferException {


   /**
    * Return the exception's message in a more readable way.
    *
    * @return string
    */
   public function getBetterMessage();


   /**
    * Return the exception's stacktrace in a more readable way (Java-like).
    *
    * @return array
    */
   public function getBetterTrace();


   /**
    * Return a text representation of the exception's stacktrace in a more readable way (Java-like).
    *
    * @return string
    */
   public function getBetterTraceAsString();
}
