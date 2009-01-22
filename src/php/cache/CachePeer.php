<?
/**
 * CachePeer
 *
 * Abstrakte Basisklasse für Cache-Implementierungen.
 *
 * Anwendung:
 * ----------
 *
 *    Wert speichern:
 *
 *       CachePeer::set($key, $value, $expires);
 *
 *
 *    Wert hinzufügen (nur speichern, wenn er noch nicht im Cache existiert):
 *
 *       CachePeer::add($key, $value, $expires)
 *
 *
 *    Wert ersetzen (nur speichern, wenn er bereits im Cache existiert):
 *
 *       CachePeer::replace($key, $value, $expires)
 *
 *
 *    Wert aus dem Cache holen:
 *
 *       $value = CachePeer::get($key);
 *
 *
 *    Wert löschen:
 *
 *       CachePeer::drop($key);
 *
 * @see ApcCache
 * @see FileSystemCache
 * @see ReferencePool
 */
abstract class CachePeer extends Object {


   protected /*string*/        $label;
   protected /*string*/        $namespace;
   protected /*array*/         $options;
   private   /*ReferencePool*/ $referencePool;


   /**
    * Gibt den lokalen ReferencePool zurück.
    *
    * @return ReferencePool
    */
   protected function getReferencePool() {
      if (!$this->referencePool)
         $this->referencePool = new ReferencePool($this->label);
      return $this->referencePool;
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    */
   abstract public function get($key);


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return boolean - TRUE bei Erfolg, FALSE, falls kein solcher Schlüssel existiert
    */
   abstract public function drop($key);


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
    *
    * @return boolean
    */
   abstract public function isCached($key);


   /**
    * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schlüssel wird
    * überschrieben.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param string     $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed      $value      - der zu speichernde Wert
    * @param int        $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param Dependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   abstract public function set($key, &$value, $expires = Cache ::EXPIRES_NEVER, Dependency $dependency = null);


   /**
    * Alias für $self::drop()
    *
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return boolean - TRUE bei Erfolg, FALSE, falls kein solcher Schlüssel existiert
    */
   final public function delete($key) {
      return $this->drop($key);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn noch kein Wert unter dem angegebenen Schlüssel
    * existiert.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param string     $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed      $value      - der zu speichernde Wert
    * @param int        $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param Dependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function add($key, &$value, $expires = Cache ::EXPIRES_NEVER, Dependency $dependency = null) {
      if ($this->isCached($key))
         return false;

      return $this->set($key, $value, $expires, $dependency);
   }


   /**
    * Speichert einen Wert im Cache nur dann, wenn unter dem angegebenen Schlüssel bereits ein Wert
    * existiert.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param string     $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed      $value      - der zu speichernde Wert
    * @param int        $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param Dependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   final public function replace($key, &$value, $expires = Cache ::EXPIRES_NEVER, Dependency $dependency = null) {
      if (!$this->isCached($key))
         return false;

      return $this->set($key, $value, $expires, $dependency);
   }
}
?>
