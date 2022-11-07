<?php
namespace rosasurfer\core\error;


/**
 * An exception representing a PHP strict error.
 */
class PHPStrict extends PHPError {


    /**
     * Return the error type of this PHP error.
     *
     * @return string
     */
    public function getErrorType() {
        return 'PHP Strict';
    }
}
