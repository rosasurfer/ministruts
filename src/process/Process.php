<?php
namespace rosasurfer\process;

use rosasurfer\core\StaticClass;


/**
 * Process handling functionalities.
 */
class Process extends StaticClass {


    /**
     * Call signal handlers for pending signals.
     * On platforms which do not support signal handling (e.g. on Windows) the call does nothing.
     *
     * @return bool - success status
     */
    public static function dispatchSignals() {
        if (function_exists('pcntl_signal_dispatch')) {
            return pcntl_signal_dispatch();
        }
        return false;
    }
}
