<?
/**
 * ReferencePool
 *
 * Die einfachste CachePeer-Implementierung.  Dieser Cache  speichert die Objekte nur im Prozeß-Speicher
 * des Requests, die Werte bleiben also nach Ende des Requests nicht erhalten.  Er stellt jedoch sicher,
 * daß mehrere Zugriffe auf ein gespeichertes Object immer ein und dieselbe Instanz zurückliefern und
 * wird deshalb intern von jedem Cache als Reference-Pool verwendet.
 */
final class ReferencePool extends CachePeer {


   // Pool
   private $pool = array();


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key       - Schlüssel
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return boolean
    */
   public function isCached($key, $namespace) {
      if (!isSet($this->pool["$namespace::$key"]))
         return false;

      /*IDependency*/ $dependency = $this->pool["$namespace::$key"][2];

      if ($dependency && $dependency->isStatusChanged()) {
         unSet($this->pool["$namespace::$key"]);
         return false;
      }

      return true;
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert ist
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 War im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    */
   public function get($key, $namespace) {
      if ($this->isCached($key, $namespace))
         return $this->pool["$namespace::$key"][1];

      return null;
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key       - Schlüssel, unter dem der Wert gespeichert ist
    * @param string $namespace - Namensraum innerhalb des Caches
    *
    * @return boolean - TRUE bei Erfolg, FALSE, falls kein solcher Schlüssel existiert
    */
   public function delete($key, $namespace) {
      if (isSet($this->pool["$namespace::$key"])) {
         unSet($this->pool["$namespace::$key"]);
         return true;
      }
      return false;
   }


   /**
    * Implementierung von set(), add() und replace(). Der Parameter $expires wird bei diesem Cache
    * vernachlässigt, da alle Instanzen nur für die Dauer des aktuellen Requests existieren.
    */
   protected function store($action, $key, &$value, $expires, IDependency $dependency = null, $namespace) {
      if ($action=='add' && $this->isCached($key, $namespace))
         return false;

      if ($action=='replace' && !$this->isCached($key, $namespace))
         return false;

      if ($action=='set') {
         // im Cache wird ein Array[creation_timestamp, value, dependency] gespeichert
         $this->pool["$namespace::$key"] = array(time(), $value, $dependency);
         return true;
      }

      throw new InvalidArgumentException('Invalid argument $action: '.$action);
   }
}
?>
