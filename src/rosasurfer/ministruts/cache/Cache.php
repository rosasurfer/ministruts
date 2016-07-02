<?php
/**
 * Cache
 *
 * Factory für verschiedene Cache-Implementierungen.
 *
 * @see CachePeer
 */
final class Cache extends StaticClass {


   const /*int*/ EXPIRES_NEVER = 0;


   /**
    * Default-Cache-Implementierung
    */
   private static /*CachePeer*/   $default;


   /**
    * weitere Cache-Implementierungen
    */
   private static /*CachePeer[]*/ $caches;


   /**
    * Gibt die Cache-Implementierung für den angegebenen Bezeichner zurück. Verschiedene Bezeichner
    * stehen für verschiedene Cache-Implementierungen, z.B. APC-Cache, Dateisystem-Cache, MemCache.
    *
    * @param  string $label - Bezeichner
    *
    * @return CachePeer
    */
   public static function me($label = null) {
      // TODO: zufällige Verwendung des APPLICATION_NAME als label abfangen

      // Default-Cache
      if ($label === null) {
         if (!self::$default) {
            $key = '';

            // neuen Cache instantiieren
            if (extension_loaded('apc') && ini_get(isSet($_SERVER['REQUEST_METHOD']) ? 'apc.enabled':'apc.enable_cli')) {
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
         $class   = Config ::me()->get('cache.'.$label.'.class');
         $options = Config ::me()->get('cache.'.$label.'.options', null);

         self::$caches[$label] = new $class($label, $options);
      }
      return self::$caches[$label];
   }
}
