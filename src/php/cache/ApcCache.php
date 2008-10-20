<?
/**
 * ApcCache
 *
 * Cacht Objekte im APC-Cache.
 *
 * TODO: Cache-Values in Wrapperobjekt speichern und CREATED, EXPIRES etc. verarbeiten
 */
final class ApcCache extends CachePeer {


   private /*ReferencePool*/ $pool;


   /**
    * Gibt den lokalen ReferencePool zurück.
    *
    * @return ReferencePool
    */
   private function getPool() {
      if (!$this->pool)
         $this->pool = new ReferencePool();

      return $this->pool;
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key       - Schlüssel
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return boolean
    */
   public function isCached($key, $namespace) {
      // Hier wird die eigentliche Arbeit gemacht. Die Methode prüft nicht nur, ob der Wert im Cache
      // existiert, sondern speichert ihn auch im lokalen ReferencePool. Folgende Abfragen müssen so
      // nicht ein weiteres Mal auf den Cache zugreifen, sondern können aus dem lokalen Pool bedient
      // werden.

      // ReferencePool abfragen
      if ($this->getPool()->isCached($key, $namespace)) {
         return true;
      }
      else {
         // APC abfragen
         $data = apc_fetch("$namespace::$key");
         if (!$data)          // Cache-Miss
            return false;

         // Cache-Hit, $data Format: array(timestamp, array($value, $dependency))
         $timestamp  = $data[0];
         $data[1]    = unserialize($data[1]);
         $value      = $data[1][0];
         $dependency = $data[1][1];

         // Dependency prüfen und Wert ggf. löschen
         if ($dependency && $dependency->isStatusChanged()) {
            $this->delete($key, $namespace);
            return false;
         }

         // ok, Wert im ReferencePool speichern
         $this->getPool()->set($key, $value, Cache ::EXPIRES_NEVER, $dependency, $namespace);
         return true;
      }
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert ist
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 Wird im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    *
    * @see ApcCache::isCached()
    */
   public function get($key, $namespace) {
      if ($this->isCached($key, $namespace))
         return $this->getPool()->get($key, $namespace);

      return null;
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert ist
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return boolean - TRUE bei Erfolg, FALSE, falls kein solcher Schlüssel existiert
    */
   public function delete($key, $namespace) {
      $this->getPool()->delete($key, $namespace);

      return apc_delete("$namespace::$key");
   }


   /**
    * Implementierung von set/add/replace (protected)
    */
   protected function store($action, $key, &$value, $expires, IDependency $dependency = null, $namespace) {
      if ($action == 'add' && $this->isCached($key, $namespace))
         return false;

      if ($action == 'replace' && !$this->isCached($key, $namespace))
         return false;

      if ($action == 'set') {
         // im Cache wird ein array(timestamp, array(value, dependency)) gespeichert
         $time = time();
         $data = array($value, $dependency);

         if (!apc_store("$namespace::$key", array($time, serialize($data)), $expires))
            throw new RuntimeException('Unexpected APC error, apc_store() returned FALSE');

         $this->getPool()->store($action, $key, $value, $expires, $dependency, $namespace);
         return true;
      }

      throw new InvalidArgumentException('Invalid argument $action: '.$action);
   }
}
?>
