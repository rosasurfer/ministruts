<?php
namespace rosasurfer\console\io;

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
     * @param  mixed $message
     */
    public function out($message) {
        $var = $message;
        if (is_object($var) && method_exists($var, '__toString') && !$var instanceof \SimpleXMLElement) {
            $str = (string) $var;
        }
        elseif (is_object($var) || is_array($var)) {
            $str = print_r($var, true);
        }
        elseif ($var === null) {
            $str = '(null)';                                // analogous to typeof(null) = 'NULL'
        }
        elseif (is_bool($var)) {
            $str = ($var ? 'true':'false').' (bool)';
        }
        else {
            $str = (string) $var;
        }

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
        if (!is_string($message)) throw new IllegalTypeException('Illegal type of parameter $message: '.gettype($message));

        $len = strlen($message);
        if ($len && $message[$len-1]!=NL)
            $message .= NL;

        $hStream = CLI ? \STDERR : fopen('php://stderr', 'a');
        fwrite($hStream, $message);
        if (!CLI) fclose($hStream);
    }
}
