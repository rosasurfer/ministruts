<?php
namespace rosasurfer\di\proxy;

use rosasurfer\core\StaticClass;
use rosasurfer\core\exception\UnimplementedFeatureException;


/**
 * Proxy
 */
abstract class Proxy extends StaticClass {


    /** @var object[] - resolved instances of proxied objects */
    protected static $resolvedInstances;


    /**
     * Return the service identifier of the proxied instance.
     *
     * @return string
     */
    protected static function getServiceId() {
        throw new UnimplementedFeatureException(static::class.' must implement Proxy::'.__FUNCTION__.'()');
    }


    /**
     * Get the instance behind the proxy.
     *
     * @return object
     */
    public static function instance() {
        $id = static::getServiceId();

        if (isset(static::$resolvedInstances[$id]))
            return static::$resolvedInstances[$id];

        return static::$resolvedInstances[$id] = self::di()->get($id);
    }


    /**
     * Forward static method calls to the object behind the proxy.
     *
     * @param  string $method - method name
     * @param  array  $args   - arguments passed to the method call
     *
     * @return mixed
     */
    public static function __callStatic($method, array $args) {
        $instance = static::instance();
        return $instance->$method(...$args);
    }
}
