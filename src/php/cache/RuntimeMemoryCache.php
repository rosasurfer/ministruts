<?
/**
 * RuntimeMemoryCache
 *
 * Default Prozess-RAM Cache. Cacht Objekte nur im Speicher des aktuellen Prozesses, nicht Request-übergreifend.
 * Nützlich als Cache-Fallback, wenn ein Cache benötigt, jedoch keiner installiert ist.
 */
final class RuntimeMemoryCache extends AbstractCachePeer {


   // Referenz-Pool
   private $pool = array();


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
    *
    * @return boolean
    */
   public function isCached($key) {
      return isSet($this->pool[$key]);
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 Wird im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    *
    * @see RuntimeMemoryCache::isCached()
    */
   public function get($key) {
      if ($this->isCached($key))
         return $this->pool[$key][1];
      return null;
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return boolean - TRUE bei Erfolg,
    *                   FALSE, falls kein solcher Schlüssel existiert
    */
   public function delete($key) {
      if ($this->isCached($key)) {
         unSet($this->pool[$key]);
         return true;
      }
      return false;
   }


   /**
    * Implementierung von set/add/replace (protected)
    */
   protected function store($action, $key, &$value, $expires = 0) {
      if ($action == 'add' && $this->isCached($key))
         return false;

      if ($action == 'replace' && !$this->isCached($key))
         return false;

      // es wird ein Array[creation_timestamp, value] gespeichert
      $this->pool[$key] = array(time(), $value);
      return true;
   }
}
?>
