<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;


/**
 * URL generation helper
 */
class Url extends Object {


   /**
    * Constructor
    *
    * Create a new Url instance.
    */
   public function __construct() {
   }


   /**
    * Return a text presentation of this Url.
    *
    * @return string
    */
   public function __toString() {
      return print_r($this, true);
   }
}
