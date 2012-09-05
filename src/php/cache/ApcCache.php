<?php
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
    * @param string $label   - Cache-Bezeichner
    * @param array  $options - zusätzliche Optionen
    */
   public function __construct($label = null, array $options = null) {
      $this->label     = $label;
      $this->namespace = ($label===null) ? APPLICATION_NAME : $label;
      $this->options   = $options;
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
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
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert ist
    * @param mixed  $default - Defaultwert (kann selbst auch NULL sein)
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
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
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
    * @param string     $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed      $value      - der zu speichernde Wert
    * @param int        $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param Dependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
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
       * PHP 5.3.3/APC 3.1.3p1
       * ---------------------
       * Bug 1: apc_add() und apc_store() geben FALSE zurück, wenn sie innerhalb eines Requests für denselben Key
       *        mehrmals aufgerufen werden. Keine Lösung gefunden
       *
       * Bug 2: Apache error log is filled by "[apc-warning] Potential cache slam averted for key '...'"
       *        @see https://bugs.php.net/bug.php?id=58832
       *        @see http://stackoverflow.com/questions/4983370/php-apc-potential-cache-slam-averted-for-key
       *        @see http://notmysock.org/blog/php/user-cache-timebomb.html
       *        @see http://serverfault.com/questions/342295/apc-keeps-crashing
       *
       *        @see http://stackoverflow.com/questions/1670034/why-would-apc-store-return-false
       */


      // wegen Bugs in apc_store() einen existierenden Wert zuerst löschen
      if (function_exists('apc_exists')) $isKey =        apc_exists($fullKey);   // APC >= 3.1.4
      else                               $isKey = (bool) apc_fetch ($fullKey);

      if ($isKey && !apc_delete($fullKey))
         Logger ::log('apc_delete() unexpectedly returned FALSE for key "'.$fullKey.'"', L_WARN, __CLASS__);


      // Wert speichern:
      // - möglichst apc_add() benutzen (minimiert "[apc-warning] Potential cache slam averted for key '...'")
      // - wegen diverser Bugs keine APC-TTL setzen, die tatsächliche TTL wird in self::isCached() geprüft
      if (function_exists('apc_add')) {                                          // APC >= 3.0.13
         if (!apc_add($fullKey, array($created, $expires, serialize($data)))) {
            Logger ::log('apc_add() unexpectedly returned FALSE for $key "'.$fullKey.'" '.($isKey ? '(did exist and was deleted)':'(did not exist)'), L_WARN, __CLASS__);
            return false;
         }
      }
      else if (!apc_store($fullKey, array($created, $expires, serialize($data)))) {
         Logger ::log('apc_store() unexpectedly returned FALSE for $key "'.$fullKey.'" '.($isKey ? '(did exist and was deleted)':'(did not exist)'), L_WARN, __CLASS__);
         return false;
      }

      $this->getReferencePool()->set($key, $value, $expires, $dependency);

      return true;
   }
}
?>
