<?
/**
 * Cache
 *
 * Fassade für verschiedene Cache-Implementierungen.
 *
 * Anwendung:
 * ----------
 *
 *    Objekt im Cache speichern:
 *
 *       Cache::set($key, $value, $expires);
 *
 *
 *    Objekt im Cache nur dann speichern, wenn es dort noch nicht existiert:
 *
 *       Cache::add($key, $value, $expires)
 *
 *
 *    Objekt im Cache nur dann speichern, wenn es dort bereits existiert:
 *
 *       Cache::replace($key, $value, $expires)
 *
 *
 *    Objekt aus dem Cache holen:
 *
 *       $value = Cache::get($key);
 *
 *
 *    Objekt im Cache löschen:
 *
 *       Cache::delete($key);
 *
 *
 * @see AbstractCachePeer
 */
class Cache extends StaticFactory {


   const EXPIRES_NEVER   = 0;
   const EXPIRES_MAXIMUM = 21600;      // 6 hrs
   const EXPIRES_MEDIUM  = 3600;       // 1 hr
   const EXPIRES_MINIMUM = 300;        // 5 mins


   // aktuelle Cache-Implementierung
   private static $peer = null;


   /**
    * Gibt die Instanz der aktuellen Cache-Implementierung zurück.
    *
    * @return AbstractCachePeer
    */
   private static function getPeer() {
      if (!self::$peer) {
         if (extension_loaded('apc') && ini_get('apc.enabled'))
            self::$peer = new ApcCache();
         else
            self::$peer = new RuntimeMemoryCache();
      }
      return self::$peer;
   }


   /**
    * Speichert einen Wert im Cache.
    *
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value   - der zu speichernde Wert
    * @param int    $expires - Zeitspanne in Sekunden, die der Wert gespeichert bleiben soll (default: für immer)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   public static function set($key, &$value, $expires = self:: EXPIRES_NEVER) {
      return self:: getPeer()->set($key, $value, $expires);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn noch kein Wert unter dem Schlüssel existiert.
    *
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value   - der zu speichernde Wert
    * @param int    $expires - Zeitspanne in Sekunden, die der Wert gespeichert bleiben soll (default: für immer)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   public static function add($key, &$value, $expires = self:: EXPIRES_NEVER) {
      return self:: getPeer()->add($key, $value, $expires);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn bereits ein Wert unter dem Schlüssel existiert.
    *
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value   - der zu speichernde Wert
    * @param int    $expires - Zeitspanne in Sekunden, die der Wert gespeichert bleiben soll (default: für immer)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   public static function replace($key, &$value, $expires = self:: EXPIRES_NEVER) {
      return self:: getPeer()->replace($key, $value, $expires);
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return mixed - der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert
    */
   public static function get($key) {
      return self:: getPeer()->get($key);
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return boolean - TRUE bei Erfolg,
    *                   FALSE, falls kein solcher Schlüssel existiert
    */
   public static function delete($key) {
      return self:: getPeer()->delete($key);
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
    *
    * @return boolean
    */
   public static function isCached($key) {
      return self:: getPeer()->isCached($key);
   }
}
