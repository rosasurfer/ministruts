<?php
namespace rosasurfer\core\exception\error;


/**
 * "rosasurfer" exception for a PHP parse error.
 */
class PHPParseError extends PHPError {


    /**
     * Return the simple PHP type description of this PHPError.
     *
     * @return string
     */
    public function getSimpleType() {
        return 'PHP Parse Error';
    }
}
