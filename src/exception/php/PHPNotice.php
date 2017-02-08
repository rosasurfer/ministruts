<?php
namespace rosasurfer\exception\php;


/**
 * "rosasurfer" exception for a PHP notice.
 */
class PHPNotice extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP Notice';
   }
}
