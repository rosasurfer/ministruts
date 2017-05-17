<?php
namespace rosasurfer\cache;

use rosasurfer\config\Config;

use rosasurfer\core\StaticClass;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\util\PHP;

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
     * @param  string|null $label - Bezeichner
     *
     * @return CachePeer
     */
    public static function me($label = null) {
        // TODO: zufaellige Verwendung der Application-ID als Label abfangen

        // Default-Cache
        if ($label === null) {
            if (!self::$default) {
                $key = '';

                // neuen Cache instantiieren
                if (extension_loaded('apc') && PHP::ini_get_bool(CLI ? 'apc.enable_cli':'apc.enabled')) {
                    self::$default = new ApcCache($label);
                }
                else {
                    self::$default = new ReferencePool($label);
                }
            }
            return self::$default;
        }

        // spezifischer Cache
        if (!is_string($label)) throw new IllegalTypeException('Illegal type of parameter $label: '.getType($label));


        if (!isSet(self::$caches[$label])) {
            if (!$config=Config::getDefault())
                throw new RuntimeException('Service locator returned invalid default config: '.getType($config));

            // Cache-Konfiguration auslesen und Cache instantiieren
            $class   = $config->get('cache.'.$label.'.class');
            $options = $config->get('cache.'.$label.'.options', null);

            self::$caches[$label] = new $class($label, $options);
        }
        return self::$caches[$label];
    }
}
