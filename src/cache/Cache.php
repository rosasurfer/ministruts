<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\cache;

use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\core\proxy\Config;

use function rosasurfer\ministruts\ini_get_bool;

use const rosasurfer\ministruts\CLI;

/**
 * Cache
 *
 * Instance factory for different cache implementations.
 *
 * @see CachePeer
 */
final class Cache extends StaticClass {

    public const EXPIRES_NEVER = 0;

    /** @var CachePeer|null - default cache implementation */
    private static ?CachePeer $default = null;

    /** @var CachePeer[] - array of further cache implementations */
    private static array $caches;


    /**
     * Return the {@link Cache} implementation for the specified identifier. Multiple identifiers may represent different
     * cache implementations, e.g. APC cache, filesystem cache, MemCache...
     *
     * @param  ?string $label [optional] - identifier
     *
     * @return CachePeer
     */
    public static function me(?string $label = null): CachePeer {
        // TODO: prevent accidental usage of the application id as cache identifier

        // default cache
        if (!isset($label)) {
            self::$default ??= extension_loaded('apc') && ini_get_bool(CLI ? 'apc.enable_cli' : 'apc.enabled')
                               ? new ApcCache($label)
                               : new ReferencePool($label);
            return self::$default;
        }

        // specific cache
        if (!isset(self::$caches[$label])) {
            // get cache configuration and create new instance
            $class   = Config::string("cache.$label.class");
            $options = Config::array("cache.$label.options", []);

            if (!is_subclass_of($class, CachePeer::class)) {
                throw new RuntimeException('Not a subclass of '.CachePeer::class.": $class");
            }
            self::$caches[$label] = new $class($label, $options);
        }
        return self::$caches[$label];
    }
}
