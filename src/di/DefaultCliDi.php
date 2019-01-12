<?php
namespace rosasurfer\di;


/**
 * Default DI for command-line interface applications.
 *
 * A variant of the standard Di. By default it automatically registers all the services provided by the framework.
 * This class is especially suitable for CLI applications.
 */
class DefaultCliDi extends DefaultDi {


    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
}
