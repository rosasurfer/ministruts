<?
/**
 * RuntimeMemoryCache
 *
 * Default Prozess-RAM Cache. Cacht Objekte nur im Speicher des aktuellen Prozesses, nicht Request-übergreifend.
 * Nützlich als Cache-Fallback, wenn ein Cache benötigt, jedoch kein anderer installiert ist.
 */
final class RuntimeMemoryCache extends AbstractCachePeer {


   private $cache = array();


   public function get($key) {
      if ($this->isCached($key))
         return $this->cache[$key][1];
      return null;
   }


   public function delete($key) {
      if ($this->isCached($key)) {
         unSet($this->cache[$key]);
         return true;
      }
      return false;
   }


   public function isCached($key) {
      return isSet($this->cache[$key]);
   }


   protected function store($action, $key, &$value, $expires = 0) {
      if ($action == 'add' && $this->isCached($key)) {
         return false;
      }
      elseif ($action == 'replace' && !$this->isCached($key)) {
         return false;
      }
      $this->cache[$key] = array(time(), $value);     // gespeichert wird ein Array(creation-time, object)
      return true;
   }
}
?>
