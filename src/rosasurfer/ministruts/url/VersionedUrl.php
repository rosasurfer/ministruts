<?php
namespace rosasurfer\ministruts\url;


/**
 * URL generation helper
 */
class VersionedUrl extends Url {


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
