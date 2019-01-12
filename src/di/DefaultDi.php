<?php
namespace rosasurfer\di;


/**
 * DefaultDi
 *
 * A variant of the standard Di. By default it automatically registers all the services provided by the framework.
 */
class DefaultDi extends Di {


    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
}
