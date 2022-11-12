<?php
namespace rosasurfer\console\io;

use rosasurfer\core\CObject;

use function rosasurfer\pp;
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
        stdout(pp($message, $return=true));
    }


    /**
     * Write a message to STDERR.
     *
     * @param  mixed $message
     */
    public function error($message) {
        stderr(pp($message, $return=true));
    }
}
