<?
/**
 * AbstractCachePeer
 *
 * Abstrakte Basisklasse für Cache-Implementierungen.
 *
 * @see Cache
 */
abstract class AbstractCachePeer extends Object {


   abstract public    function get($key, $namespace);
   abstract public    function delete($key, $namespace);
   abstract public    function isCached($key, $namespace);

   abstract protected function store($action, $key, &$value, $expires, $namespace);


   /**
    * Speichert einen Wert im Cache.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value     - der zu speichernde Wert
    * @param int    $expires   - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfallen soll
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function set($key, &$value, $expires, $namespace) {
      if (!is_string($key))       throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires))      throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));
      if (!is_string($namespace)) throw new IllegalTypeException('Illegal type of parameter $namespace: '.getType($namespace));

      return $this->store('set', $key, $value, $expires, $namespace);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn noch kein Wert unter diesem Schlüssel existiert.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value     - der zu speichernde Wert
    * @param int    $expires   - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfallen soll
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function add($key, &$value, $expires, $namespace) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));
      if (!is_string($namespace)) throw new IllegalTypeException('Illegal type of parameter $namespace: '.getType($namespace));

      return $this->store('add', $key, $value, $expires, $namespace);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn bereits ein Wert unter diesem Schlüssel existiert.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value     - der zu speichernde Wert
    * @param int    $expires   - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfallen soll
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function replace($key, &$value, $expires, $namespace) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));
      if (!is_string($namespace)) throw new IllegalTypeException('Illegal type of parameter $namespace: '.getType($namespace));

      return $this->store('replace', $key, $value, $expires, $namespace);
   }
}
?>
