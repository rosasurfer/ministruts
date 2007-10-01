<?
/**
 * AbstractCachePeer
 *
 * Abstrakte Basisklasse für Cache-Implementierungen.
 *
 * @see Cache
 */
abstract class AbstractCachePeer extends Object {


   abstract public function get($key);
   abstract public function delete($key);
   abstract public function isCached($key);

   abstract protected function store($action, $key, &$value, $expires = Cache::EXPIRES_NEVER);


   /**
    * Speichert einen Wert im Cache.
    *
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value   - der zu speichernde Wert
    * @param int    $expires - Zeitspanne in Sekunden, die der Wert gespeichert bleiben soll (default: für immer)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function set($key, &$value, $expires = Cache::EXPIRES_NEVER) {
      return $this->store('set', $key, $value, $expires);
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
   final public function add($key, &$value, $expires = Cache::EXPIRES_NEVER) {
      return $this->store('add', $key, $value, $expires);
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
   final public function replace($key, &$value, $expires = Cache::EXPIRES_NEVER) {
      return $this->store('replace', $key, $value, $expires);
   }
}
?>
