<?php
namespace rosasurfer\console;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;

use const rosasurfer\CLI;
use const rosasurfer\NL;


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
        if (!is_string($message)) throw new IllegalTypeException('Illegal type of parameter $message: '.gettype($message));

        $len = strlen($message);
        if ($len && $message[$len-1]!=NL)
            $message .= NL;

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
        if (!is_string($message)) throw new IllegalTypeException('Illegal type of parameter $message: '.gettype($message));

        $len = strlen($message);
        if ($len && $message[$len-1]!=NL)
            $message .= NL;

        $hStream = CLI ? \STDERR : fopen('php://stderr', 'a');
        fwrite($hStream, $message);
        if (!CLI) fclose($hStream);
    }
}
