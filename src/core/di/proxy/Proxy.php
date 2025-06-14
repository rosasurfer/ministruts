<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\proxy;

use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\exception\IllegalAccessException;
use rosasurfer\ministruts\core\exception\UnimplementedFeatureException;

/**
 * A {@link Proxy} forwards API calls from one object to another. It doesn't modify the called API.
 *
 * In MiniStruts it forwards static method calls to actual instances. The standard behavior to resolve proxied instances is
 * a look-up in the application's default service container. Override {@link Proxy::instance()} to change that behavior.
 */
abstract class Proxy extends StaticClass {

    /** @var object[] - resolved instances of proxied objects */
    protected static array $resolvedInstances;


    /**
     * Return the name of a proxied service.
     *
     * @return string
     */
    protected static function getServiceName(): string {
        throw new UnimplementedFeatureException(static::class.' must implement Proxy::'.__FUNCTION__.'()');
    }


    /**
     * Get the object instance behind the proxy. The standard implementation looks-up the instance in the application's
     * default service container. Override this method to change that behavior.
     *
     * @return object
     */
    public static function instance(): object {
        $key = static::getServiceName();

        if (isset(static::$resolvedInstances[$key]))
            return static::$resolvedInstances[$key];

        return static::$resolvedInstances[$key] = self::di()->get($key);
    }


    /**
     * Forward static method calls to the object instance behind the proxy.
     *
     * @param  string  $method - method name
     * @param  mixed[] $args   - arguments passed to the method call
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $args) {
        if (substr($method, 0, 2) == '__') {
            throw new IllegalAccessException('Cannot call internal method '.get_class(static::instance()).'::'.$method.'()');
        }
        $instance = static::instance();
        return $instance->$method(...$args);
    }
}
