<?
/**
 * Cache
 */
class Cache extends Object {


   /**
    */
   public static function get($key) {
      $data = apc_fetch($key);
      if ($data === null)
         return null;
      return unserialize($data);
   }


   /**
    */
   public static function set($key, $value, $ttl = 0) {
      $fH = fOpen(CONFIG_PROJECT_TMP_DIRECTORY.'/apc_lock_'.$key.'.txt', 'w');
      fLock($fH, LOCK_EX);

      $result = apc_store($key, serialize($value), $ttl);

      fClose($fH);
      return $result;
   }


   /**
    */
   public static function delete($key) {
      return apc_delete($key);
   }


   /**
    */
   public static function isCached($key) {
      return (apc_fetch($key) !== null);
   }
}
