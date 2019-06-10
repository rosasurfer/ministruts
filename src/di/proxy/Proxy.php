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
     * Return the name of a proxied service.
     *
     * @return string
     */
    protected static function getServiceName() {
        throw new UnimplementedFeatureException(static::class.' must implement Proxy::'.__FUNCTION__.'()');
    }


    /**
     * Get the instance behind the proxy. The default implementation looks-up the instance in the service container.
     *
     * @return object
     */
    public static function instance() {
        $key = static::getServiceName();

        if (isset(static::$resolvedInstances[$key]))
            return static::$resolvedInstances[$key];

        return static::$resolvedInstances[$key] = self::di()->get($key);
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
