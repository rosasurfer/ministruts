<?php
declare(strict_types=1);

namespace rosasurfer\ministruts;

// load helper functions and constants which can't be auto-loaded
require __DIR__.'/functions.php';

// CLI mode: register a SIGINT handler to catch Ctrl-C
if (CLI && \function_exists('pcntl_signal')) {
    \pcntl_signal(SIGINT, function(int $signo, $signinfo = null): void {
        // calling exit() is sufficient to execute destructors
        exit(1);
    });
}
