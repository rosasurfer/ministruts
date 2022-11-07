<?php
namespace rosasurfer\core\error;


/**
 * "rosasurfer" exception for a PHP user deprecated message.
 */
class PHPUserDeprecated extends PHPError {


    /**
     * Return the simple PHP type description of this PHPError.
     *
     * @return string
     */
    public function getSimpleType() {
        return 'PHP User Deprecated';
    }
}
