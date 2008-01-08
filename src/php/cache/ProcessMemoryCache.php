<?
/**
 * ProcessMemoryCache
 *
 * Prozess-Cache. Cacht Objekte im Speicher (RAM) des aktuellen Prozesses, nicht Request-übergreifend.
 * Nützlich als Fallback, wenn ein Cache benötigt wird, jedoch keiner installiert ist.
 */
final class ProcessMemoryCache extends AbstractCachePeer {


   // Referenz-Pool
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
      if (isSet($this->pool["$namespace::$key"])) {
         $dependency = $this->pool["$namespace::$key"][2];

         if ($dependency && $dependency->isStatusChanged()) {
            $this->delete($key, $namespace);
            return false;
         }
         return true;
      }
      return false;
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert ist
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 War im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    *
    * @see ProcessMemoryCache::isCached()
    */
   public function get($key, $namespace) {
      if ($this->isCached($key, $namespace))
         return $this->pool["$namespace::$key"][1];

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
      if (isSet($this->pool["$namespace::$key"])) {
         unSet($this->pool["$namespace::$key"]);
         return true;
      }
      return false;
   }


   /**
    * Implementierung von set/add/replace (protected), $expires wird ignoriert (hat hier keine Wirkung)
    */
   protected function store($action, $key, &$value, $expires, IDependency $dependency = null, $namespace) {
      if ($action == 'add' && $this->isCached($key, $namespace))
         return false;

      if ($action == 'replace' && !$this->isCached($key, $namespace))
         return false;

      // im Cache wird ein Array[creation_timestamp, value, dependency] gespeichert
      $this->pool["$namespace::$key"] = array(time(), $value, $dependency);
      return true;
   }
}
?>
