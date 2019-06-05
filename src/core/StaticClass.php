<?php
namespace rosasurfer\core;


/**
 * Parent class for all classes meant to be used only statical. Derived classes cannot be instantiated.
 */
abstract class StaticClass extends CObject {


    /**
     * Locked constructor
     */
    final private function __construct() {}
}
