<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\phpstan;

use rosasurfer\ministruts\core\CObject;

use function rosasurfer\ministruts\normalizeEOL;

use const rosasurfer\ministruts\NL;
use const rosasurfer\ministruts\WINDOWS;


/**
 * Helper for custom PHPStan extensions.
 */
abstract class Extension extends CObject {

    /**
     * Log a message to the system logger.
     *
     * @param  string $message
     *
     * @return void
     */
    protected function log(string $message): void {
        $message = str_replace(chr(0), '\0', $message);         // replace NUL bytes which mess up the logfile
        $message = normalizeEOL($message);
        if (WINDOWS) {
            $message = str_replace(NL, PHP_EOL, $message);
        }
        error_log($message);
    }
}
