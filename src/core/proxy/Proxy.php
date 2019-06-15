<?php
namespace rosasurfer\core\proxy;

use rosasurfer\core\StaticClass;
use rosasurfer\core\exception\IllegalAccessException;
use rosasurfer\core\exception\UnimplementedFeatureException;


/**
 * A {@link Proxy} forwards API calls from one object to another. It doesn't modify the called API.
 *
 * In MiniStruts it forwards static method calls to actual instances. The standard behavior to resolve proxied instances is
 * a look-up in the application's default service container. Override {@link Proxy::instance()} to change that behavior.
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
     * Get the object instance behind the proxy. The standard implementation looks-up the instance in the application's
     * default service container. Override this method to change that behavior.
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
     * Forward static method calls to the object instance behind the proxy.
     *
     * @param  string $method - method name
     * @param  array  $args   - arguments passed to the method call
     *
     * @return mixed
     */
    public static function __callStatic($method, array $args) {
        if (substr($method, 0, 2) == '__')
            throw new IllegalAccessException('Cannot forward to internal method '.get_class(static::instance()).'::'.$method.'()');

        $instance = static::instance();
        return $instance->$method(...$args);
    }
}
