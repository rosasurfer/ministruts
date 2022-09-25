<?php
namespace rosasurfer\cache;

use rosasurfer\config\ConfigInterface as IConfig;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;

use function rosasurfer\ini_get_bool;

use const rosasurfer\CLI;


/**
 * Cache
 *
 * Factory fuer verschiedene Cache-Implementierungen.
 *
 * @see CachePeer
 */
final class Cache extends StaticClass {


    /** @var int */
    const EXPIRES_NEVER = 0;

    /** @var CachePeer - Default-Cache-Implementierung */
    private static $default;

    /** @var CachePeer[] - weitere Cache-Implementierungen */
    private static $caches;


    /**
     * Gibt die Cache-Implementierung fuer den angegebenen Bezeichner zurueck. Verschiedene Bezeichner
     * stehen fuer verschiedene Cache-Implementierungen, z.B. APC-Cache, Dateisystem-Cache, MemCache.
     *
     * @param  string $label [optional] - Bezeichner
     *
     * @return CachePeer
     */
    public static function me($label = null) {
        // TODO: zufaellige Verwendung der Application-ID als Label abfangen

        // Default-Cache
        if (!isset($label)) {
            if (!self::$default) {
                // neuen Cache instantiieren
                if (extension_loaded('apc') && ini_get_bool(CLI ? 'apc.enable_cli' : 'apc.enabled')) {
                    self::$default = new ApcCache($label);
                }
                else {
                    self::$default = new ReferencePool($label);
                }
            }
            return self::$default;
        }

        // spezifischer Cache
        Assert::string($label);

        if (!isset(self::$caches[$label])) {
            /** @var IConfig $config */
            $config = self::di('config');

            // Cache-Konfiguration auslesen und Cache instantiieren
            $class   = $config->get('cache.'.$label.'.class');
            $options = $config->get('cache.'.$label.'.options', null);

            self::$caches[$label] = new $class($label, $options);
        }
        return self::$caches[$label];
    }
}
