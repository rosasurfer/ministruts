<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\exception;

use rosasurfer\ministruts\core\exception\RuntimeException;

/**
 * DocoptUserNotification
 *
 * An exception marking help infos and syntax errors to be shown to the end user of the CLI application.
 */
class DocoptUserNotification extends RuntimeException {

    /** @var int */
    public int $status;


    /**
     * @param string $message [optional]
     * @param int    $status  [optional]
     */
    public function __construct(string $message = '', int $status = 1) {
        parent::__construct($message);
        $this->status = $status;
    }
}
