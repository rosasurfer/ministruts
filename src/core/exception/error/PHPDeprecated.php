<?php
namespace rosasurfer\core\exception\error;


/**
 * "rosasurfer" exception for a PHP deprecated message.
 */
class PHPDeprecated extends PHPError {


    /**
     * Return the simple PHP type description of this PHPError.
     *
     * @return string
     */
    public function getSimpleType() {
        return 'PHP Deprecated';
    }
}
