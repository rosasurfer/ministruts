<?php
namespace rosasurfer\console\docopt\exception;

use rosasurfer\core\exception\RuntimeException;


/**
 * DocoptUserNotification
 *
 * An exception marking help infos and syntax errors to be shown to the end user of the CLI application.
 */
class DocoptUserNotification extends RuntimeException {


    /** @var int */
    public $status;


    /**
     * @param ?string $message [optional]
     * @param int     $status  [optional]
     */
    public function __construct($message=null, $status=1) {
        parent::__construct($message);
        $this->status = $status;
    }
}
