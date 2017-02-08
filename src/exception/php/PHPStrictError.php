<?php
namespace rosasurfer\exception\php;


/**
 * "rosasurfer" exception for a strict PHP error.
 */
class PHPStrictError extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP Strict';
   }
}
