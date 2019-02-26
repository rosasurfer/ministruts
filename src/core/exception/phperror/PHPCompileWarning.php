<?php
namespace rosasurfer\core\exception\phperror;


/**
 * "rosasurfer" exception for a PHP compile warning.
 */
class PHPCompileWarning extends PHPError {


    /**
     * Return the simple PHP type description of this PHPError.
     *
     * @return string
     */
    public function getSimpleType() {
        return 'PHP Compile Warning';
    }
}
