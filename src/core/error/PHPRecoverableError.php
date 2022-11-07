<?php
namespace rosasurfer\core\error;


/**
 * An exception representing a recoverable PHP error.
 */
class PHPRecoverableError extends PHPError {


    /**
     * Return the error type of this PHP error.
     *
     * @return string
     */
    public function getErrorType() {
        return 'PHP Recoverable Error';
    }
}
