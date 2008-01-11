<?
/**
 * ApcCache
 *
 * Cacht Objekte im APC-Cache.
 */
final class ApcCache extends AbstractCachePeer {


   // TODO: durch ReferencePool-Implementierung ersetzen
   private $pool = array();


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key       - Schlüssel
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return boolean
    */
   public function isCached($key, $namespace) {
      // Hier wird die eigentliche Arbeit gemacht. isCached() schaut nicht nur nach, ob ein Wert im Cache
      // existiert, sondern speichert ihn auch im lokalen Referenz-Pool. Folgende Abfragen müssen so nicht
      // ein weiteres Mal auf den Cache zugreifen, sondern können aus dem lokalen Pool bedient werden.
      // Existiert der Wert im Cache nicht, wird auch das vermerkt, sodaß intern nur ein einziger
      // Cache-Zugriff je Schlüssel benötigt wird.

      if (isSet($this->pool["$namespace::$key"])) {
         if ($this->pool["$namespace::$key"] === false)
            return false;

         $dependency = $this->pool["$namespace::$key"][2];
         if ($dependency && $dependency->isStatusChanged()) {
            $this->delete($key, $namespace);    // verfallenen Wert löschen
            return false;
         }
         return true;
      }

      $data = apc_fetch("$namespace::$key");
      if (!$data)                               // Cache-Miss, im Referenz-Pool FALSE setzen
         return $this->pool["$namespace::$key"] = false;

      $data[1] = unserialize($data[1]);         // Cache-Hit, Dependency prüfen
      $dependency = $data[1][1];

      if ($dependency && $dependency->isStatusChanged()) {
         $this->delete($key, $namespace);       // verfallenen Wert löschen
         return false;
      }
                                                // im Referenz-Pool speichern
      $this->pool["$namespace::$key"] = array($data[0], $data[1][0], $dependency);
      return true;
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
         return $this->pool["$namespace::$key"][1];

      return null;
      /*
      $data = apc_fetch("$namespace::$key");
      if ($data === false)
         return null;
      return unserialize($data[1]);
      */
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
      $this->pool["$namespace::$key"] = false;    // Marker für nächste Abfrage setzen
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

      // im Cache wird ein Array[creation_timestamp, value, dependency] gespeichert
      $time = time();
      $data = array($value, $dependency);

      if (!apc_store("$namespace::$key", array($time, serialize($data)), $expires))
         throw new RuntimeException('Unexpected APC error, apc_store() returned FALSE');

      $this->pool["$namespace::$key"] = array($time, $value, $dependency);
      return true;
   }
}
?>
