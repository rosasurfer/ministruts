<?php
use rosasurfer\exception\IllegalTypeException;


/**
 * ApcCache
 *
 * Cacht Objekte im APC-Cache.
 *
 * TODO: Cache-Values in Wrapperobjekt speichern und CREATED, EXPIRES etc. verarbeiten
 */
final class ApcCache extends CachePeer {


   /**
    * Constructor.
    *
    * @param  string $label   - Cache-Bezeichner
    * @param  array  $options - zusätzliche Optionen
    */
   public function __construct($label = null, array $options = null) {
      $this->label     = $label;
      $this->namespace = ($label===null) ? APPLICATION_ID : $label;
      $this->options   = $options;
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param  string $key - Schlüssel
    *
    * @return bool
    */
   public function isCached($key) {
      // Hier wird die eigentliche Arbeit gemacht. Die Methode prüft nicht nur, ob der Wert im Cache
      // existiert, sondern holt ihn auch gleich und speichert eine Referenz im ReferencePool. Folgende
      // Abfragen können so sofort aus dem ReferencePool bedient werden.

      // ReferencePool abfragen
      if ($this->getReferencePool()->isCached($key)) {
         return true;
      }
      else {
         // APC abfragen
         $data = apc_fetch($this->namespace.'::'.$key);
         if (!$data)          // Cache-Miss
            return false;

         // Cache-Hit, Datenformat: array(created, expires, serialize(array($value, $dependency)))
         $created = $data[0];
         $expires = $data[1];

         // expires prüfen
         if ($expires && $created+$expires < time()) {
            $this->drop($key);
            return false;
         }

         $data[2]    = unserialize($data[2]);
         $value      = $data[2][0];
         $dependency = $data[2][1];

         // Dependency prüfen
         if ($dependency) {
            $minValid = $dependency->getMinValidity();

            if ($minValid) {
               if (time() > $created+$minValid) {
                  if (!$dependency->isValid()) {
                     $this->drop($key);
                     return false;
                  }
                  // created aktualisieren (Wert praktisch neu in den Cache schreiben)
                  return $this->set($key, $value, $expires, $dependency);
               }
            }
            elseif (!$dependency->isValid()) {
               $this->drop($key);
               return false;
            }
         }

         // ok, Wert im ReferencePool speichern
         $this->getReferencePool()->set($key, $value, Cache ::EXPIRES_NEVER, $dependency);
         return true;
      }
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.  Existiert der Wert nicht, wird der angegebene Defaultwert
    * zurückgegeben.
    *
    * @param  string $key     - Schlüssel, unter dem der Wert gespeichert ist
    * @param  mixed  $default - Defaultwert (kann selbst auch NULL sein)
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 Achtung: Ist im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    */
   public function get($key, $default = null) {
      if ($this->isCached($key))
         return $this->getReferencePool()->get($key);

      return $default;
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param  string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return bool - TRUE bei Erfolg, FALSE, falls kein solcher Schlüssel existiert
    */
   public function drop($key) {
      $this->getReferencePool()->drop($key);

      return apc_delete($this->namespace.'::'.$key);
   }


   /**
    * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schlüssel wird
    * überschrieben.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param  string     $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param  mixed      $value      - der zu speichernde Wert
    * @param  int        $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param  Dependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    *
    * @return bool - TRUE bei Erfolg, FALSE andererseits
    */
   public function set($key, &$value, $expires = Cache ::EXPIRES_NEVER, Dependency $dependency = null) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));

      // im Cache wird ein array(created, expires, serialize(array(value, dependency))) gespeichert
      $fullKey = $this->namespace.'::'.$key;
      $created = time();
      $data    = array($value, $dependency);

      /**
       * PHP 5.3.3/APC 3.1.3
       * -------------------
       * Bug 1: warning "Potential cache slam averted for key '...'"
       *        apc_add() und apc_store() geben FALSE zurück, wenn sie innerhalb eines Requests für denselben Key
       *        mehrmals aufgerufen werden
       *
       *        @see http://bugs.php.net/bug.php?id=58832
       *        @see http://stackoverflow.com/questions/4983370/php-apc-potential-cache-slam-averted-for-key
       *
       *        Lösung für APC >= 3.1.7: re-introduced setting apc.slam_defense=0
       *        keine Lösung für APC 3.1.3 - 3.1.6 gefunden
       *
       *        @see http://serverfault.com/questions/342295/apc-keeps-crashing
       *        @see http://stackoverflow.com/questions/1670034/why-would-apc-store-return-false
       *        @see http://notmysock.org/blog/php/user-cache-timebomb.html
       */


      // Wert speichern:
      // - möglichst apc_add() benutzen (weniger Speicherfragmentierung, minimiert Lock-Wait)
      // - keine APC-TTL setzen, die tatsächliche TTL wird in self::isCached() geprüft (diverse APC-Bugs bei gesetzter TTL)

      // TODO: http://phpdevblog.niknovo.com/2009/11/serialize-vs-var-export-vs-json-encode.html

      if (function_exists('apc_add')) {                                                // APC >= 3.0.13
         if (function_exists('apc_exists')) $isKey =        apc_exists($fullKey);      // APC >= 3.1.4
         else                               $isKey = (bool) apc_fetch ($fullKey);
         if ($isKey)
            apc_delete($fullKey);      // apc_delete()+apc_add() fragmentieren den Speicher weniger als apc_store()

         if (!apc_add($fullKey, array($created, $expires, serialize($data)))) {
            //Logger::log('apc_add() unexpectedly returned FALSE for $key "'.$fullKey.'" '.($isKey ? '(did exist and was deleted)':'(did not exist)'), null, L_WARN, __CLASS__);
            return false;
         }
      }
      else if (!apc_store($fullKey, array($created, $expires, serialize($data)))) {
         //Logger::log('apc_store() unexpectedly returned FALSE for $key "'.$fullKey.'" '.($isKey ? '(did exist and was deleted)':'(did not exist)'), null, L_WARN, __CLASS__);
         return false;
      }


      $this->getReferencePool()->set($key, $value, $expires, $dependency);

      return true;
   }
}
