<?php
namespace rosasurfer\core\error;


/**
 * An exception representing a PHP core error.
 */
class PHPCoreError extends PHPError {


    /**
     * Return the error type of this PHP error.
     *
     * @return string
     */
    public function getErrorType() {
        return 'PHP Core Error';
    }
}
