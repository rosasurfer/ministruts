<?
/**
 * PersistableObject
 *
 * Abstrakte Superklasse für Objekte, die dauerhaft gespeichert werden können.
 */
abstract class PersistableObject extends Object {


   const C_NUMERIC  = 1;
   const C_STRING   = 2;
   const C_NULL     = 3;
   const C_NOT_NULL = 4;


   /* leeres Datenbankmapping (array) */
   protected static $mapping;

   /* Flag für den aktuellen Änderungsstatus einer Instanz (boolean) */
   protected $isChanged;


   /**
    * Ob die aktuelle Instanz bereits gespeichert ist oder nicht.
    *
    * @return boolean
    */
   public function isPersistent() {
      return ($this->id !== null);
   }


   /**
    * Speichert die aktuelle Instanz.
    */
   abstract public function save();


   /**
    * Mappt Datenbankspalten auf Instanzvariablen. Damit dies funktioniert,
    * muß das statische Array $mapping das Datenbankmapping der Klasse enthalten.
    *
    * Beispiel:
    * ---------
    * class MyObject extends PersistableObject {
    *
    *    protected $id;
    *    protected $created;
    *    protected $key;
    *    protected $name;                   Spaltenname    =>  Object-Eigenschaft
    *                                       -------------------------------------
    *    protected static $mapping = array('a_id'          => 'id',
    *                                      'creation_date' => 'created',
    *                                      'object_key'    => 'key',
    *                                      'fullname'      => 'fullName',
    *    );
    * }
    *
    * @param property - Name der zu mappenden Tabellenspalte
    * @param value    - Wert der zu mappenden Tabellenspalte
    */
   protected function __set($property, $value) {
      static $mapping = null;
      if ($mapping === null) {
         eval('$mapping =& '.get_class($this).'::$mapping;');           // runtime evaluation of static property  (erst ab PHP6 fest eingebaut)
      }

      // Mapping suchen
      if (isSet($mapping[$property])) {
         $this->$mapping[$property][0] = $value;                        // gefunden
      }
      else {
         $trace = debug_backTrace();                                    // nicht gefunden; prüfen, ob Aufruf von mysql_fetch_object() kommt
         $i = 0;
         do {
            $frame =& $trace[++$i];
         } while (strToLower($frame['function'])=='__set');

         if (strToLower($frame['function']) == 'mysql_fetch_object')
            throw new RuntimeException("Database mapping for field '$property' not found (class ".get_class($this).")");

         parent:: __set($property, $value);                             // nein, Aufruf weiterreichen (Programmierfehler)
      }
   }


   /**
    * Castet alle numerischen Properties nach int, da mysql_fetch_object() diese als String speichert.
    */
   protected static function castNumericProperties(PersistableObject $instance, array $mapping) {
      foreach ($mapping as $column) {
         if ($column[1]==self::C_NUMERIC && $instance->$column[0]!==null) {
            $instance->$column[0] = (int) $instance->$column[0];
         }
      }
   }
}
?>
