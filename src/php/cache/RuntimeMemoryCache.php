<?
/**
 * RuntimeMemoryCache
 *
 * Prozess-Cache. Cacht Objekte nur im Speicher (RAM) des aktuellen Prozesses, nicht Request-übergreifend.
 * Nützlich als Fallback, wenn ein Cache benötigt wird, jedoch keiner installiert ist.
 */
final class RuntimeMemoryCache extends AbstractCachePeer {


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
      return isSet($this->pool["$ns:$key"]);
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
    * @see RuntimeMemoryCache::isCached()
    */
   public function get($key, $ns) {
      if ($this->isCached($key, $ns))
         return $this->pool["$ns:$key"][1];
      return null;
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
      if ($this->isCached($key, $ns)) {
         unSet($this->pool["$ns:$key"]);
         return true;
      }
      return false;
   }


   /**
    * Implementierung von set/add/replace (protected), $expires wird ignoriert (hat hier keine Wirkung)
    */
   protected function store($action, $key, &$value, $expires, $ns) {
      if ($action == 'add' && $this->isCached($key, $ns))
         return false;

      if ($action == 'replace' && !$this->isCached($key, $ns))
         return false;

      // es wird ein Array[creation_timestamp, value] gespeichert
      $this->pool["$ns:$key"] = array(time(), $value);
      return true;
   }
}
?>
