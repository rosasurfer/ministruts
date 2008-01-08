<?
/**
 * ApcCache
 *
 * Cacht Objekte im APC-Cache.
 */
final class ApcCache extends AbstractCachePeer {


   // Referenz-Pool
   private $pool = array();


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
    * @param string $ns  - Namensraum innerhalb des Caches
    *
    * @return boolean
    */
   public function isCached($key, $ns) {
      // Hier wird die eigentliche Arbeit gemacht. isCached() schaut nicht nur nach, ob ein Wert im Cache
      // existiert, sondern speichert ihn auch im lokalen Referenz-Pool. Folgende Abfragen müssen so nicht
      // ein weiteres Mal auf den Cache zugreifen, sondern können aus dem lokalen Pool bedient werden.
      // Existiert der Wert im Cache nicht, wird auch das vermerkt, sodaß intern nur ein einziger
      // Cache-Zugriff je Schlüssel benötigt wird.

      if (isSet($this->pool["$ns:$key"]))
         return ($this->pool["$ns:$key"] !== false);

      $data = apc_fetch("$ns:$key");
      if ($data === false)                      // Cache-Miss, im Referenz-Pool FALSE setzen
         return $this->pool["$ns:$key"] = false;

      $data[1] = unserialize($data[1]);         // Cache-Hit, im Referenz-Pool speichern
      $this->pool["$ns:$key"] =& $data;

      return true;
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    * @param string $ns  - Namensraum innerhalb des Caches
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 Wird im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    *
    * @see ApcCache::isCached()
    */
   public function get($key, $ns) {
      if ($this->isCached($key, $ns))
         return $this->pool["$ns:$key"][1];
      return null;

      /*
      $data = apc_fetch("$ns:$key");
      if ($data === false)
         return null;
      return unserialize($data[1]);
      */
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    * @param string $ns  - Namensraum innerhalb des Caches
    *
    * @return boolean - TRUE bei Erfolg,
    *                   FALSE, falls kein solcher Schlüssel existiert
    */
   public function delete($key, $ns) {
      $this->pool["$ns:$key"] = false;    // Marker für nächste Abfrage setzen
      return apc_delete("$ns:$key");
   }


   /**
    * Implementierung von set/add/replace (protected)
    */
   protected function store($action, $key, &$value, $expires, $ns) {
      if ($action == 'add' && $this->isCached($key, $ns))
         return false;

      if ($action == 'replace' && !$this->isCached($key, $ns))
         return false;

      // im Cache wird ein Array[creation_timestamp, value] gespeichert
      $data = array(time(), $value);

      if (!apc_store("$ns:$key", array($data[0], serialize($data[1])), $expires))
         throw new RuntimeException('Unexpected APC error, apc_store() returned FALSE');

      $this->pool["$ns:$key"] =& $data;
      return true;
   }
}
?>
