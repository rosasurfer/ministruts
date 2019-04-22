<?php
namespace rosasurfer\console\io;

use rosasurfer\core\Object;

use function rosasurfer\printPretty;

use const rosasurfer\CLI;


/**
 * Output
 */
class Output extends Object {


    /**
     * Write a message to STDOUT.
     *
     * @param  mixed $message
     */
    public function out($message) {
        $message = printPretty($message, $return=true);

        $hStream = CLI ? \STDOUT : fopen('php://stdout', 'a');
        fwrite($hStream, $message);
        if (!CLI) fclose($hStream);

        echoPre($var);
    }


    /**
     * Write a message to STDERR.
     *
     * @param  mixed $message
     */
    public function error($message) {
        $message = printPretty($message, $return=true);

        $hStream = CLI ? \STDERR : fopen('php://stderr', 'a');
        fwrite($hStream, $message);
        if (!CLI) fclose($hStream);
    }
}
