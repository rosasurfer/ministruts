<?php
namespace rosasurfer\core\exception\error;


/**
 * "rosasurfer" exception for a recoverable PHP error.
 */
class PHPRecoverableError extends PHPError {


    /**
     * Return the simple PHP type description of this PHPError.
     *
     * @return string
     */
    public function getSimpleType() {
        return 'PHP Recoverable Error';
    }
}
