<?php
namespace rosasurfer\console;

use rosasurfer\core\Object;
use rosasurfer\core\assert\Assert;

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
    public function out($message) {
        Assert::string($message);

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
    public function error($message) {
        Assert::string($message);

        $len = strlen($message);
        if ($len && $message[$len-1]!=NL)
            $message .= NL;

        $hStream = CLI ? \STDERR : fopen('php://stderr', 'a');
        fwrite($hStream, $message);
        if (!CLI) fclose($hStream);
    }
}
