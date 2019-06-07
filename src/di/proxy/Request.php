<?php
namespace rosasurfer\di\proxy;


/**
 * Request
 */
class Request extends Proxy {


    /**
     * Get the identifier of the proxied instance.
     *
     * @return string
     */
    protected static function getProxiedId() {
        return \rosasurfer\ministruts\Request::class;
    }
}
