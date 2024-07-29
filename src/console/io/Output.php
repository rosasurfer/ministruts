<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\io;

use rosasurfer\ministruts\core\CObject;

use function rosasurfer\ministruts\print_p;
use function rosasurfer\ministruts\stderr;
use function rosasurfer\ministruts\stdout;


/**
 * Output
 */
class Output extends CObject {


    /**
     * Write a message to STDOUT.
     *
     * @param  mixed $message
     *
     * @return void
     */
    public function out($message) {
        stdout(print_p($message, true));
    }


    /**
     * Write a message to STDERR.
     *
     * @param  mixed $message
     *
     * @return void
     */
    public function error($message) {
        stderr(print_p($message, true));
    }
}
