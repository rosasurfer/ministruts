<?php
namespace rosasurfer\core\error;


/**
 * An exception representing a PHP user warning.
 */
class PHPUserWarning extends PHPError {


    /**
     * Return the error type of this PHP error.
     *
     * @return string
     */
    public function getErrorType() {
        return 'PHP User Warning';
    }
}
