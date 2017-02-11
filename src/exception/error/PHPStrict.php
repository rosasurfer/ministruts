<?php
namespace rosasurfer\exception\error;


/**
 * "rosasurfer" exception for a PHP strict message.
 */
class PHPStrict extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP Strict';
   }
}