<?
/**
 * AbstractCachePeer
 *
 * Abstrakte Basisklasse für Cache-Implementierungen.
 *
 * @see Cache
 */
abstract class AbstractCachePeer extends Object {


   abstract public    function get($key);
   abstract public    function delete($key);
   abstract public    function isCached($key);

   abstract protected function store($action, $key, &$value, $expires = 0);


   /**
    * Speichert einen Wert im Cache.
    *
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value   - der zu speichernde Wert
    * @param int    $expires - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfallen soll (default: gar nicht)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function set($key, &$value, $expires = 0) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));

      return $this->store('set', $key, $value, $expires);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn noch kein Wert unter diesem Schlüssel existiert.
    *
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value   - der zu speichernde Wert
    * @param int    $expires - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfallen soll (default: gar nicht)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function add($key, &$value, $expires = 0) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));

      return $this->store('add', $key, $value, $expires);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn bereits ein Wert unter diesem Schlüssel existiert.
    *
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value   - der zu speichernde Wert
    * @param int    $expires - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfallen soll (default: gar nicht)
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function replace($key, &$value, $expires = 0) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));

      return $this->store('replace', $key, $value, $expires);
   }
}
?>
