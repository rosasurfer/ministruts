<?php
namespace rosasurfer\di\proxy;


/**
 * Input
 */
class Input extends Proxy {


    /**
     * Get the identifier of the proxied instance.
     *
     * @return string
     */
    protected static function getProxiedIdentifier() {
        return \rosasurfer\console\io\Input::class;
    }
}
