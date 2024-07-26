<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\process;

use rosasurfer\ministruts\core\StaticClass;


/**
 * Process handling functionalities.
 */
class Process extends StaticClass {


    /**
     * Call signal handlers to handle pending signals.
     * On platforms which do not support POSIX signal handling (e.g&#46; on Windows) the call does nothing.
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
