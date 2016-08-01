<?php
namespace rosasurfer\core;


/**
 * Super class for classes meant to be called only statical. Derived classes cannot be instantiated.
 */
abstract class StaticClass extends Object {


   /**
    * Locked constructor
    */
   final private function __construct() {/* you can't call me */}
}
