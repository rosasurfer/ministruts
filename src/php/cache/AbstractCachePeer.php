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

   abstract protected function store($action, $key, &$value, $expires, IDependency $dependency = null, $namespace);


   /**
    * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schlüssel wird
    * überschrieben.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param string      $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed       $value      - der zu speichernde Wert
    * @param int         $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param IDependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    * @param string      $namespace  - Namensraum innerhalb des Caches
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function set($key, &$value, $expires, IDependency $dependency = null, $namespace) {
      if (!is_string($key))       throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires))      throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));
      if (!is_string($namespace)) throw new IllegalTypeException('Illegal type of parameter $namespace: '.getType($namespace));

      return $this->store('set', $key, $value, $expires, $dependency, $namespace);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn noch kein Wert unter dem angegebenen Schlüssel
    * existiert.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param string      $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed       $value      - der zu speichernde Wert
    * @param int         $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param IDependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    * @param string      $namespace  - Namensraum innerhalb des Caches
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function add($key, &$value, $expires, IDependency $dependency = null, $namespace) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));
      if (!is_string($namespace)) throw new IllegalTypeException('Illegal type of parameter $namespace: '.getType($namespace));

      return $this->store('add', $key, $value, $expires, $dependency, $namespace);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn unter dem angegebenen Schlüssel bereits ein Wert
    * existiert.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param string      $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed       $value      - der zu speichernde Wert
    * @param int         $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param IDependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    * @param string      $namespace  - Namensraum innerhalb des Caches
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function replace($key, &$value, $expires, IDependency $dependency = null, $namespace) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));
      if (!is_string($namespace)) throw new IllegalTypeException('Illegal type of parameter $namespace: '.getType($namespace));

      return $this->store('replace', $key, $value, $expires, $dependency, $namespace);
   }
}
?>
