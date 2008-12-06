<?
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
    * Gibt die Cache-Implementierung für den angegebenen Bezeichner zurück. Unterschiedliche Bezeichner
    * stehen für verschiedene Cache-Implementierungen, z.B. APC, Dateisystem-Cache, MemCache.
    *
    * @param string $label - Bezeichner
    *
    * @return CachePeer
    */
   public static function me($label = null) {
      /*
      Undocumented:
      -------------
      Die Konfiguration wird im Cache gespeichert und der Cache wird mit Hilfe der Konfiguration
      initialisiert.  Dadurch kommt es zu zirkulären Aufrufen zwischen Config::me() und Cache::me().
      Bei solchen zirkulären Aufrufen (und nur dann) wird NULL zurückgegeben.
      @see Config::me()
      */

      // TODO: zufällige Verwendung des APPLICATION_NAME als label abfangen

      static /*array*/ $currentCreations;
      static /*array*/ $circularCalls;


      // Default-Cache
      if ($label === null) {
         if (!self::$default) {
            $key = '';

            // rekursive Aufrufe während der Instantiierung abfangen
            if (isSet($currentCreations[$key])) {
               $circularCalls[$key] = true;
               return null;
            }

            // Flag zur Erkenung rekursiver Aufrufe setzen
            $currentCreations[$key] = true;

            // neuen Cache instantiieren
            if (extension_loaded('apc') && ini_get(isSet($_SERVER['REQUEST_METHOD']) ? 'apc.enabled' : 'apc.enable_cli'))
               self::$default = new ApcCache($label);
            else
               self::$default = new ReferencePool($label);

            // Flag zurücksetzen
            unset($currentCreations[$key]);

            // trat ein rekursiver Aufruf auf, muß die Config evt. noch gecacht werden
            if (isSet($circularCalls[$key])) {
               // Die Config wird bei jedem Request einmal neu eingelesen und erst dann durch die gecachte
               // Variante ersetzt. Abhilfe: Logger-Anweisungen aus der Cache- und den abhängigen Klassen entfernen
               Logger ::log(new RuntimeException('Circular method call, performance is degraded'), L_WARN, __CLASS__);

               unset($circularCalls[$key]);
               Config ::me();
            }
         }
         return self::$default;
      }


      // spezifischer Cache
      if (!is_string($label)) throw new IllegalTypeException('Illegal type of argument $label: '.getType($label));


      if (!isSet(self::$caches[$label])) {
         // rekursive Aufrufe während der Instantiierung abfangen
         if (isSet($currentCreations[$label])) {
            $circularCalls[$label] = true;
            return null;
         }

         // Cache-Konfiguration auslesen
         $class   = Config ::me()->get('cache.'.$label.'.class');
         $options = Config ::me()->get('cache.'.$label.'.options', null);


         // Cache instantiieren
         $currentCreations[$label] = true;
         self::$caches[$label] = new $class($label, $options);
         unset($currentCreations[$label]);


         // trat ein rekursiver Aufruf auf, muß die Config evt. noch gecacht werden
         if (isSet($circularCalls[$label])) {
            // Die Config wird bei jedem Request einmal neu eingelesen und erst dann durch die gecachte
            // Variante ersetzt. Abhilfe: Logger-Anweisungen aus der Cache- und den abhängigen Klassen entfernen
            Logger ::log(new RuntimeException('Circular method call, performance is degraded'), L_WARN, __CLASS__);

            unset($circularCalls[$label]);
            Config ::me();
         }
      }
      return self::$caches[$label];
   }
}
