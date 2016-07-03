<?php
namespace rosasurfer\ministruts\exceptions;


/**
 * An interface defining common functionality for SCX exceptions.
 */
interface IScxException {


   /**
    * Returns the exception's message in a more readable way.
    *
    * @return string
    */
   public function getBetterMessage();


   /**
    * Returns the exception's stacktrace in a more readable way (Java-like).
    *
    * @return array
    */
   public function getBetterTrace();


   /**
    * Returns a text representation of the exception's stacktrace in a more readable way (Java-like).
    *
    * @return string
    */
   public function getBetterTraceAsString();
}
