<?php
namespace rosasurfer\core\exception\error;


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
