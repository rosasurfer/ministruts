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
     * Get the identifier of the object behind the proxy.
     *
     * @return string
     */
    protected static function getProxiedIdentifier() {
        throw new UnimplementedFeatureException(static::class.' must implement method Proxy::'.__FUNCTION__.'()');
    }


    /**
     * Get the object behind the proxy.
     *
     * @return object
     */
    public static function getProxiedInstance() {
        $id = static::getProxiedIdentifier();

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
        $instance = static::getProxiedInstance();
        return $instance->$method(...$args);
    }
}
