<?
/**
 * ApcCache
 *
 * Cacht Objekte im APC-Cache.
 *
 * TODO: Cache-Values in Wrapperobjekt speichern und CREATED, EXPIRES etc. verarbeiten
 */
final class ApcCache extends CachePeer {


   /**
    * Constructor.
    *
    * @param string $label   - Cache-Bezeichner
    * @param array  $options - zusätzliche Optionen
    */
   public function __construct($label = null, array $options = null) {
      $this->label     = $label;
      $this->namespace = ($label===null) ? APPLICATION_NAME : $label;
      $this->options   = $options;
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
    *
    * @return boolean
    */
   public function isCached($key) {
      // Hier wird die eigentliche Arbeit gemacht. Die Methode prüft nicht nur, ob der Wert im Cache
      // existiert, sondern speichert ihn auch im lokalen ReferencePool. Folgende Abfragen müssen so
      // nicht ein weiteres Mal auf den Cache zugreifen, sondern können aus dem lokalen Pool bedient
      // werden.

      // ReferencePool abfragen
      if ($this->getReferencePool()->isCached($key)) {
         return true;
      }
      else {
         // APC abfragen
         $data = apc_fetch($this->namespace.'::'.$key);
         if (!$data)          // Cache-Miss
            return false;

         // Cache-Hit, $data Format: array(timestamp, array($value, $dependency))
         $timestamp  = $data[0];
         $data[1]    = unserialize($data[1]);
         $value      = $data[1][0];
         $dependency = $data[1][1];

         // Dependency prüfen und Wert ggf. löschen
         if ($dependency && $dependency->isStatusChanged()) {
            $this->delete($key);
            return false;
         }

         // ok, Wert im ReferencePool speichern
         $this->getReferencePool()->set($key, $value, Cache ::EXPIRES_NEVER, $dependency);
         return true;
      }
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
         return $this->getReferencePool()->get($key);

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
      $this->getReferencePool()->delete($key);

      return apc_delete($this->namespace.'::'.$key);
   }


   /**
    * Implementierung von set(), add() und replace().
    */
   protected function store($action, $key, &$value, $expires, IDependency $dependency = null) {
      if ($action=='add' && $this->isCached($key))
         return false;

      if ($action=='replace' && !$this->isCached($key))
         return false;

      if ($action=='set') {
         // im Cache wird ein array(timestamp, array(value, dependency)) gespeichert
         $time = time();
         $data = array($value, $dependency);

         if (!apc_store($this->namespace.'::'.$key, array($time, serialize($data)), $expires))
            throw new RuntimeException('Unexpected APC error, apc_store() returned FALSE');

         $this->getReferencePool()->store($action, $key, $value, $expires, $dependency);
         return true;
      }

      throw new InvalidArgumentException('Invalid argument $action: '.$action);
   }
}
?>
