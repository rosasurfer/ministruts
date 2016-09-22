<?php
namespace rosasurfer\cache;

use rosasurfer\config\Config;
use rosasurfer\core\StaticClass;
use rosasurfer\exception\IllegalTypeException;

use const rosasurfer\CLI;


/**
 * Cache
 *
 * Factory für verschiedene Cache-Implementierungen.
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
    * Gibt die Cache-Implementierung für den angegebenen Bezeichner zurück. Verschiedene Bezeichner
    * stehen für verschiedene Cache-Implementierungen, z.B. APC-Cache, Dateisystem-Cache, MemCache.
    *
    * @param  string $label - Bezeichner
    *
    * @return CachePeer
    */
   public static function me($label = null) {
      // TODO: zufällige Verwendung der APPLICATION_ID als Label abfangen

      // Default-Cache
      if ($label === null) {
         if (!self::$default) {
            $key = '';

            // neuen Cache instantiieren
            if (extension_loaded('apc') && ini_get(CLI ? 'apc.enable_cli':'apc.enabled')) {
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
         // Cache-Konfiguration auslesen und Cache instantiieren
         $class   = Config::getDefault()->get('cache.'.$label.'.class');
         $options = Config::getDefault()->get('cache.'.$label.'.options', null);

         self::$caches[$label] = new $class($label, $options);
      }
      return self::$caches[$label];
   }
}
