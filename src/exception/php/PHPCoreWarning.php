<?php
namespace rosasurfer\exception\php;


/**
 * "rosasurfer" exception for a PHP core warning.
 */
class PHPCoreWarning extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP Core Warning';
   }
}
