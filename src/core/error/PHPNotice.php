<?php
namespace rosasurfer\core\error;


/**
 * An exception representing a PHP notice.
 */
class PHPNotice extends PHPError {


    /**
     * Return the error type of this PHP error.
     *
     * @return string
     */
    public function getErrorType() {
        return 'PHP Notice';
    }
}
