<?php
namespace rosasurfer\cache;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;

use function rosasurfer\ini_get_bool;

use const rosasurfer\CLI;


/**
 * Cache
 *
 * Instance factory for different cache implementations.
 *
 * @see CachePeer
 */
final class Cache extends StaticClass {


    /** @var int */
    const EXPIRES_NEVER = 0;


    /** @var ?CachePeer - default cache implementation */
    private static $default = null;


    /** @var CachePeer[] - array of further cache implementations */
    private static $caches;


    /**
     * Return the {@link Cache} implementation for the specified identifier. Multiple identifiers may represent different
     * cache implementations, e.g. APC cache, filesystem cache, MemCache...
     *
     * @param  string $label [optional] - identifier
     *
     * @return CachePeer
     */
    public static function me($label = null) {
        // TODO: prevent accidental usage of the application id as cache identifier

        // default cache
        if ($label === null) {
            if (!self::$default) {
                // create new instance
                if (extension_loaded('apc') && ini_get_bool(CLI ? 'apc.enable_cli':'apc.enabled')) {
                    self::$default = new ApcCache($label);
                }
                else {
                    self::$default = new ReferencePool($label);
                }
            }
            return self::$default;
        }

        // specific cache
        Assert::string($label);

        if (!isset(self::$caches[$label])) {
            /** @var ConfigInterface $config */
            $config = self::di('config');

            // get cache configuration and create new instance
            $class   = $config->get('cache.'.$label.'.class');
            $options = $config->get('cache.'.$label.'.options', null);

            self::$caches[$label] = new $class($label, $options);
        }
        return self::$caches[$label];
    }
}
