<?php
namespace rosasurfer\console\docopt\exception;

use rosasurfer\exception\RuntimeException;

use const rosasurfer\NL;


/**
 * UserNotification
 *
 * An exception marking help screens and syntax errors to be shown to the end user of the CLI application.
 */
class UserNotification extends RuntimeException {


    /** @var string */
    public static $usage;

    /** @var int */
    public $status;


    /**
     * @param string $message [optional]
     * @param int    $status  [optional]
     */
    public function __construct($message=null, $status=1) {
        parent::__construct(trim($message.NL.static::$usage));
        $this->status = $status;
    }
}
