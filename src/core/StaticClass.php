<?php
namespace rosasurfer\core;


/**
 * Super class for classes meant to be called only statical. Derived classes cannot be instantiated.
 */
abstract class StaticClass extends Object {


   /**
    * Locked constructor
    */
   private final function __construct() {/* you can't call me */}
}
