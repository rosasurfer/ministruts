<?php
namespace rosasurfer\exception\php;


/**
 * "rosasurfer" exception for a PHP compile error.
 */
class PHPCompileError extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP Compile Error';
   }
}
