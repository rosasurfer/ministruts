<?php
namespace rosasurfer\exception\error;


/**
 * "rosasurfer" exception for a PHP warning.
 */
class PHPWarning extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP Warning';
   }
}
