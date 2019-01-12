<?php
namespace rosasurfer\di\def;                        // namespaces are not allowed to contain the word "default"

use rosasurfer\di\Di;


/**
 * Default DI for command-line interface applications.
 *
 * A variant of the standard Di. By default it automatically registers all the services provided by the framework.
 * This class is especially suitable for CLI applications.
 */
class DefaultCliDi extends Di {


    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
}
