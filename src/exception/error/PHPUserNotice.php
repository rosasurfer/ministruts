<?php
namespace rosasurfer\exception\error;


/**
 * "rosasurfer" exception for a PHP user notice.
 */
class PHPUserNotice extends PHPError {


   /**
    * Return the simple PHP type description of this PHPError.
    *
    * @return string
    */
   public function getSimpleType() {
      return 'PHP User Notice';
   }
}
