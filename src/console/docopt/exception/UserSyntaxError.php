<?php
namespace rosasurfer\console\docopt\exception;

use rosasurfer\exception\RuntimeException;


/**
 * UserSyntaxError
 *
 * An exception marking errors made by the end user of the CLI application. The application was launched with invalid
 * arguments.
 */
class UserSyntaxError extends RuntimeException {


    /** @var string */
    public static $usage;

    /** @var int */
    public $status;

    /**
     * @param string $message [optional]
     * @param int    $status  [optional]
     */
    public function __construct($message=null, $status=1) {
        parent::__construct(trim($message.PHP_EOL.static::$usage));
        $this->status = $status;
    }
}
