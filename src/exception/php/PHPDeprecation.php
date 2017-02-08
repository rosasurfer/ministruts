<?php
namespace rosasurfer\exception\php;


/**
 * "rosasurfer" exception for a PHP deprecation message.
 */
class PHPDeprecation extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP Deprecated';
   }
}
