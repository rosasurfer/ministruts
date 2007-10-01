<?
/**
 * ApcCache
 *
 * Cacht Objekte im APC-Opcode-Cache.
 */
final class ApcCache extends AbstractCachePeer {


   public function get($key) {
      $data = apc_fetch($key);
      if ($data === null)
         return null;
      return unserialize($data[1]);
   }


   public function delete($key) {
      return apc_delete($key);
   }


   public function isCached($key) {
      return (apc_fetch($key) !== false);
   }


   protected function store($action, $key, &$value, $expires = 0) {
      if ($action == 'add' && $this->isCached($key)) {
         return false;
      }
      elseif ($action == 'replace' && !$this->isCached($key)) {
         return false;
      }

      // gespeichert wird ein Array(creation-time, object)
      return apc_store($key, array(time(), serialize($value)), $expires);
   }
}
?>
