<?php
namespace rosasurfer\core;


/**
 * Superclass for all classes to be used in a static context only. Derived classes cannot be instantiated.
 */
abstract class StaticClass extends CObject {


    /**
     * Locked constructor
     */
    final private function __construct() {}
}
