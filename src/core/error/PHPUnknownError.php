<?php
namespace rosasurfer\core\error;


/**
 * "rosasurfer" exception for an unknown PHP error (error levels to be implemented in the future).
 */
class PHPUnknownError extends PHPError {


    /**
     * Return the simple PHP type description of this PHPError.
     *
     * @return string
     */
    public function getSimpleType() {
        return 'PHP Unknown Error';
    }
}
