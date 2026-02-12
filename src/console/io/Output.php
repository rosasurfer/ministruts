<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\io;

use rosasurfer\ministruts\core\CObject;

use function rosasurfer\ministruts\stderr;
use function rosasurfer\ministruts\stdout;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\toString;

use const rosasurfer\ministruts\NL;

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
    public function out($message): void {
        if (!is_string($message)) {
            $message = toString($message);
        }
        if (!strEndsWith($message, NL)) {
            $message .= NL;
        }
        stdout($message);
    }


    /**
     * Write a message to STDERR.
     *
     * @param  mixed $message
     *
     * @return void
     */
    public function error($message): void {
        if (!is_string($message)) {
            $message = toString($message);
        }
        if (!strEndsWith($message, NL)) {
            $message .= NL;
        }
        stderr($message);
    }
}
