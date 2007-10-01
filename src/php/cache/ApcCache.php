<?
/**
 * ApcCache
 *
 * Cacht Objekte im APC-Opcode-Cache.
 */
final class ApcCache extends AbstractCachePeer {


   // Referenz-Pool
   private $pool = array();


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
    *
    * @return boolean
    */
   public function isCached($key) {
      // Hier wird die eigentliche Arbeit gemacht. isCached() schaut nicht nur nach, ob ein Wert im Cache existiert,
      // sondern speichert ihn auch im lokalen Referenz-Pool. Folgende Abfragen müssen so nicht ein weiteres Mal auf
      // den Cache zugreifen, sondern können aus dem lokalen Pool bedient werden. Existiert ein Wert im Cache nicht,
      // wird auch das vermerkt, sodaß intern für jeden Schlüssel nur ein einziger Zugriff je Request benötigt wird.

      if (isSet($this->pool[$key]))
         return ($this->pool[$key] !== false);

      $data = apc_fetch($key);            // Cache miss, im Referenz-Pool FALSE-Marker setzen
      if ($data === false)
         return $this->pool[$key] = false;

      $data[1] = unserialize($data[1]);   // Cache hit, im Referenz-Pool zwischenspeichern
      $this->pool[$key] =& $data;

      return true;
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 Wird im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    *
    * @see ApcCache::isCached()
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
    * @return boolean - TRUE bei Erfolg,
    *                   FALSE, falls kein solcher Schlüssel existiert
    */
   public function delete($key) {
      $this->pool[$key] = false;    // Marker für nächste Abfrage setzen
      return apc_delete($key);
   }


   /**
    * Implementierung von set/add/replace (protected)
    */
   protected function store($action, $key, &$value, $expires = 0) {
      if ($action == 'add' && $this->isCached($key))
         return false;

      if ($action == 'replace' && !$this->isCached($key))
         return false;

      // im Cache wird ein Array[creation_timestamp, value] gespeichert
      $data = array(time(), $value);

      if (! apc_store($key, array($data[0], serialize($data[1])), $expires))
         throw new RuntimeException('Unexpected APC error, apc_store() returned FALSE');

      $this->pool[$key] =& $data;
      return true;
   }
}
?>
