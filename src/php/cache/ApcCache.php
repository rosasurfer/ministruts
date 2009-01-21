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
         if ($dependency && !$dependency->isValid()) {
            $this->drop($key);
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
   public function drop($key) {
      $this->getReferencePool()->drop($key);

      return apc_delete($this->namespace.'::'.$key);
   }


   /**
    * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schlüssel wird
    * überschrieben.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param string      $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed       $value      - der zu speichernde Wert
    * @param int         $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param IDependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   public function set($key, &$value, $expires = Cache ::EXPIRES_NEVER, IDependency $dependency = null) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));

      // im Cache wird ein array(timestamp, array(value, dependency)) gespeichert
      $time = time();
      $data = array($value, $dependency);

      if (!apc_store($this->namespace.'::'.$key, array($time, serialize($data)), $expires))
         throw new RuntimeException('Unexpected APC error, apc_store() returned FALSE');

      $this->getReferencePool()->set($key, $value, $expires, $dependency);

      return true;
   }
}
?>
