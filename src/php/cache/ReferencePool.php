<?
/**
 * ReferencePool
 *
 * Die einfachste CachePeer-Implementierung.  Dieser Cache  speichert die Objekte nur im Speicher des
 * aktuellen Prozesses, die Werte bleiben also nach Ende des Requests nicht erhalten.  Er stellt jedoch
 * sicher, daß mehrere Zugriffe auf ein gespeichertes Object immer ein und dieselbe Instanz zurückliefern
 * und wird deshalb intern von jedem Cache als Reference-Pool verwendet.
 *
 * TODO: ReferencePool muß ein Singleton sein
 */
final class ReferencePool extends CachePeer {


   /**
    * Array mit den gespeicherten Referenzen
    */
   private /*array*/ $pool;


   /**
    * Constructor.
    *
    * Erzeugt eine neue Instanz.  Ein evt. angegebenes Label (Namespace) wird in der Folge ignoriert,
    * da für einen ReferencePool im Speicher eines Prozesses nur ein Namespace sinnvoll ist.
    *
    * @param string $label   - Cache-Bezeichner
    * @param array  $options - zusätzliche Optionen
    */
   public function __construct($label = null, array $options = null) {
      $this->label     = $label;
      $this->namespace = null;
      $this->options   = $options;
   }


   /**
    * ReferencePool-Instanzen benötigen im Gegensatz zu anderen CachePeer-Implementierungen intern keinen
    * weiteren ReferencePool.  Diese Methode gibt daher einen Zeiger auf die Instanz selbst zurück und
    * überschreibt damit die Default-Implementierung in CachePeer.
    *
    * @return ReferencePool
    *
    * @see CachePeer::getReferencePool()
    */
   protected function getReferencePool() {
      return $this;
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
    *
    * @return boolean
    */
   public function isCached($key) {
      if (!isSet($this->pool[$key]))
         return false;

      /*IDependency*/ $dependency = $this->pool[$key][2];

      if ($dependency && !$dependency->isValid()) {
         unSet($this->pool[$key]);
         return false;
      }

      return true;
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 Achtung: Ist im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    */
   public function get($key) {
      if ($this->isCached($key))
         return $this->pool[$key][1];

      return null;
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return boolean - TRUE bei Erfolg, FALSE, falls kein solcher Schlüssel existiert
    */
   public function delete($key) {
      if (isSet($this->pool[$key])) {
         unSet($this->pool[$key]);
         return true;
      }
      return false;
   }


   /**
    * Implementierung von set(), add() und replace(). Der Parameter $expires wird bei diesem Cache
    * vernachlässigt, da alle Instanzen nur für die Dauer des aktuellen Requests existieren.
    */
   protected function store($action, $key, &$value, $expires, IDependency $dependency = null) {
      if ($action=='add' && $this->isCached($key))
         return false;

      if ($action=='replace' && !$this->isCached($key))
         return false;

      if ($action=='set') {
         // im Cache wird ein Array[creation_timestamp, value, dependency] gespeichert
         $this->pool[$key] = array(time(), $value, $dependency);
         return true;
      }

      throw new InvalidArgumentException('Invalid argument $action: '.$action);
   }
}
?>
