<?php
namespace rosasurfer\core\error;


/**
 * An exception representing an unknown PHP error (if any in the future).
 */
class PHPUnknownError extends PHPError {


    /**
     * Return the error type of this PHP error.
     *
     * @return string
     */
    public function getErrorType() {
        return 'PHP Unknown Error';
    }
}
