<?
/**
 * PersistableObject
 *
 * Abstrakte Superklasse für Objekte, die dauerhaft gespeichert werden können.
 */
abstract class PersistableObject extends Object {


   // 'leeres' Datenbankmapping
   protected static $mappings;


   /**
    * Ob die aktuelle Instanz bereits gespeichert ist oder nicht.
    *
    * @return boolean
    */
   abstract public function isPersistent();


   /**
    * Speichert die aktuelle Instanz.
    */
   abstract public function save();


   /**
    * Mappt Datenbankspalten auf Instanzvariablen. Damit dies funktioniert, muß die statische
    * Variable $mappings jeder persistenten Klasse deren Datenbankmapping enthalten.
    *
    * Beispiel:
    * ---------
    * class MyObject extends PersistableObject {
    *
    *    protected $id;
    *    protected $created;
    *    protected $key;
    *    protected $name;
    *                                     // Field names     => Property names
    *                                     // ---------------------------------
    *    protected static $mappings = array('a_id'          => 'id',
    *                                       'creation_date' => 'created',
    *                                       'object_key'    => 'key',
    *                                       'fullname'      => 'name',
    *    );
    * }
    *
    * @param property - Name der zu mappenden Tabellenspalte
    * @param value    - Wert, auf den die entsprechende Variable der Klasseninstanz gesetzt werden soll
    */
   protected function __set($property, $value) {
      static $mappings = null;
      if ($mappings === null) {
         eval('$mappings =& '.get_class($this).'::$mappings;');         // runtime evaluation of static property  (erst ab PHP6 fest eingebaut)
      }

      // Mapping suchen
      if (isSet($mappings[$property])) {
         $this->$mappings[$property] = $value;                          // gefunden, Property setzen
      }
      else {                                                            // Mapping nicht gefunden: prüfen, ob mysql_fetch_object() der Aufrufer ist
         $trace = debug_backTrace();
         $i = 0;
         do {
            $frame =& $trace[++$i];
         } while (strToLower($frame['function'])=='__set');

         if (strToLower($frame['function']) == 'mysql_fetch_object')    // ja
            throw new RuntimeException("Database mapping for field '$property' not found (class ".get_Class($this).")");

         parent:: __set($property, $value);                             // nein, Aufruf weiterreichen (Programmierfehler)
      }
   }
}
?>
