<?php
namespace rosasurfer\console\io;

use rosasurfer\core\CObject;

use function rosasurfer\print_p;
use function rosasurfer\stderr;
use function rosasurfer\stdout;


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
