<?php
namespace rosasurfer\console;

use rosasurfer\core\Object;

use const rosasurfer\CLI;


/**
 * Output
 */
class Output extends Object {


    /**
     * Write a message to STDOUT.
     *
     * @param  string $message
     */
    public function stdout($message) {
        $hStream = CLI ? \STDOUT : fopen('php://stdout', 'a');
        fwrite($hStream, $message);
        if (!CLI) fclose($hStream);
    }


    /**
     * Write a message to STDERR.
     *
     * @param  string $message
     */
    public function stderr($message) {
        $hStream = CLI ? \STDERR : fopen('php://stderr', 'a');
        fwrite($hStream, $message);
        if (!CLI) fclose($hStream);
    }
}
