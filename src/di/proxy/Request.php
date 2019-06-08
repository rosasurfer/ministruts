<?php
namespace rosasurfer\di\proxy;


/**
 * Request
 *
 * Proxy for the "request" implementation currently registered in the service container.
 *
 * Default implementation: {@link \rosasurfer\ministruts\Request}
 */
class Request extends Proxy {


    /**
     * Return the service identifier of the proxied instance.
     *
     * @return string
     */
    protected static function getServiceId() {
        return \rosasurfer\ministruts\Request::class;
    }
}
