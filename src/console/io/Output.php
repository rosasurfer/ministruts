<?php
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
     */
    public function out($message) {
        stdout(print_p($message, true));
    }


    /**
     * Write a message to STDERR.
     *
     * @param  mixed $message
     */
    public function error($message) {
        stderr(print_p($message, true));
    }
}
