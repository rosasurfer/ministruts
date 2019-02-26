<?php
namespace rosasurfer\core\exception\phperror;


/**
 * "rosasurfer" exception for a PHP user warning.
 */
class PHPUserWarning extends PHPError {


    /**
     * Return the simple PHP type description of this PHPError.
     *
     * @return string
     */
    public function getSimpleType() {
        return 'PHP User Warning';
    }
}
