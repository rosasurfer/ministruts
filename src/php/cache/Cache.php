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
class Cache extends StaticClass {


   const EXPIRES_NEVER = 0;


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
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value     - der zu speichernde Wert
    * @param int    $expires   - Zeitspanne in Sekunden, die der Wert gespeichert bleiben soll (default: immer)
    * @param string $namespace - Namensraum innerhalb des Caches (default: APPLICATION_NAME)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   public static function set($key, &$value, $expires = self:: EXPIRES_NEVER, $namespace = APPLICATION_NAME) {
      return self:: getPeer()->set($key, $value, $expires, $namespace);
   }


   /**
    * Speichert einen Wert im Cache, wenn noch kein Wert unter dem angegebenen Schlüssel existiert.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value     - der zu speichernde Wert
    * @param int    $expires   - Zeitspanne in Sekunden, die der Wert gespeichert bleiben soll (default: immer)
    * @param string $namespace - Namensraum innerhalb des Caches (default: APPLICATION_NAME)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   public static function add($key, &$value, $expires = self:: EXPIRES_NEVER, $namespace = APPLICATION_NAME) {
      return self:: getPeer()->add($key, $value, $expires, $namespace);
   }


   /**
    * Speichert einen Wert im Cache, wenn bereits ein Wert unter dem angegebenen Schlüssel existiert.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value     - der zu speichernde Wert
    * @param int    $expires   - Zeitspanne in Sekunden, die der Wert gespeichert bleiben soll (default: immer)
    * @param string $namespace - Namensraum innerhalb des Caches (default: APPLICATION_NAME)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   public static function replace($key, &$value, $expires = self:: EXPIRES_NEVER, $namespace = APPLICATION_NAME) {
      return self:: getPeer()->replace($key, $value, $expires, $namespace);
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert ist
    * @param string $namespace - Namensraum innerhalb des Caches (default: APPLICATION_NAME)
    *
    * @return mixed - der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert
    */
   public static function get($key, $namespace = APPLICATION_NAME) {
      return self:: getPeer()->get($key, $namespace);
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert ist
    * @param string $namespace - Namensraum innerhalb des Caches (default: APPLICATION_NAME)
    *
    * @return boolean - TRUE bei Erfolg,
    *                   FALSE, falls kein solcher Schlüssel existiert
    */
   public static function delete($key, $namespace = APPLICATION_NAME) {
      return self:: getPeer()->delete($key, $namespace);
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key       - Schlüssel
    * @param string $namespace - Namensraum innerhalb des Caches (default: APPLICATION_NAME)
    *
    * @return boolean
    */
   public static function isCached($key, $namespace = APPLICATION_NAME) {
      return self:: getPeer()->isCached($key, $namespace);
   }
}
