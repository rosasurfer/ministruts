<?php
namespace rosasurfer\exception\error;


/**
 * "rosasurfer" exception for a PHP user error.
 */
class PHPUserError extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP User Error';
   }
}
