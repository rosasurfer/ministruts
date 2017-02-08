<?php
namespace rosasurfer\exception\php;


/**
 * "rosasurfer" exception for a PHP core error.
 */
class PHPCoreError extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP Core Error';
   }
}
