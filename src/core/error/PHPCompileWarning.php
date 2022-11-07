<?php
namespace rosasurfer\core\error;


/**
 * An exception representing a PHP compile warning.
 */
class PHPCompileWarning extends PHPError {


    /**
     * Return the error type of this PHP error.
     *
     * @return string
     */
    public function getErrorType() {
        return 'PHP Compile Warning';
    }
}
