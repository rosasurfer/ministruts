<?
/**
 * PersistableObject
 *
 * Abstrakte Superklasse für Objekte, die dauerhaft gespeichert werden können.
 */
abstract class PersistableObject extends Object {


   // Mapping-Constanten
   const T_BOOL     = 1;         // boolean
   const T_INT      = 2;         // int
   const T_FLOAT    = 3;         // float
   const T_STRING   = 4;         // string
   const T_SET      = 5;         // set
   const T_NULL     = true;      // null
   const T_NOT_NULL = false;     // not null


   // Flag für den aktuellen Änderungsstatus einer Instanz (boolean)
   protected $isModified;


   // Standard-Properties jeder Instanz
   protected $id;                // Primary Key:         int
   protected $version;           // Versionsnummer:      timestamp (string)
   protected $created;           // Erzeugungszeitpunkt: datetime  (string)


   /**
    * Gibt die ID dieser Instanz zurück.
    *
    * @return int - ID (primary key)
    */
   public function getId() {
      return $this->id;
   }


   /**
    * Gibt die Versionsnummer dieser Instanz zurück.
    *
    * @return string - Versionsnummer (Zeitpunkt der letzten Änderung)
    */
   protected function getVersion() {
      return $this->version;
   }


   /**
    * Gibt den Erstellungszeitpunkt dieser Instanz zurück.
    *
    * @param string $format - Zeitformat
    *
    * @return string - Zeitpunkt
    */
   public function getCreated($format = 'Y-m-d H:i:s')  {
      if ($format == 'Y-m-d H:i:s')
         return $this->created;

      return formatDate($format, $this->created);
   }


   /**
    * Zeigt an, ob die aktuelle Instanz bereits gespeichert ist oder nicht.
    * Muß überschrieben werden, wenn die Primary Key-Spalte der Klasse nicht 'id' heißt.
    *
    * @return boolean
    */
   public function isPersistent() {
      return ($this->id !== null);
   }


   /**
    * Speichert diese Instanz in der Datenbank.
    */
   public function save() {
      if (!$this->isPersistent()) {
         $this->insert();
      }
      elseif ($this->isModified) {
         $this->update();
      }
      else {
         //Logger ::log('Nothing to save, '.get_class($this).' instance is in sync with the database.', L_NOTICE);
      }
   }


   /**
    * Fügt diese Instanz in die Datenbank ein.  Diese Methode muß von der konkreten Klasse implementiert werden.
    */
   protected function insert() {
      throw new RuntimeException('Method not implemented: '.get_class($this).'::insert()');
   }


   /**
    * Aktualisiert diese Instanz in der Datenbank.  Diese Methode muß von der konkreten Klasse implementiert werden.
    */
   protected function update() {
      throw new RuntimeException('Method not implemented: '.get_class($this).'::update()');
   }


   /**
    * Löscht diese Instanz aus der Datenbank.  Diese Methode muß von der konkreten Klasse implementiert werden.
    */
   public function delete() {
      throw new RuntimeException('Method not implemented: '.get_class($this).'::delete()');
   }


   /**
    * Erzeugt aus den übergebenen Daten eine neue Instanz.
    *
    * @param array $data - Array mit Instanzdaten (aus der Datenbank)
    *
    * @return instance
    */
   public static function createInstance(array &$data) {
      throw new RuntimeException('Implement YourClass::createInstance() to instantiate your class, see example at '.__CLASS__.'::createInstance()');
      /*
      // Example:
      // --------
      public static function createInstance(array &$data) {
         $instance = new YourClass();
         PersistableObject::populate($instance, YourClass::$mapping, $data);
         return $instance;
      }
      */
   }


   /**
    * Bevölkert eine PersistableObject-Instanz mit den übergebenen Daten.
    *
    * Achtung: Das gleichzeitige Erzeugen sehr vieler Instanzen (z.B. Batchprocessing; mehrere tausend Stück) ist
    *          ca. 3 x mal schneller, wenn diese Methode überschrieben und ohne Schleifen implementiert wird.
    *
    * @param PersistableObject $object - Instanz
    * @param array $mappings           - Datenbankmapping
    * @param array $dataRow            - Datenreihe
    *
    * @return PersistableObject instance - die modifizierte Instanz
    */
   protected static function populate(PersistableObject $object, array &$mappings, array &$dataRow) {
      foreach ($mappings['fields'] as $property => &$mapping) {
         $column =& $mapping[0];

         if ($dataRow[$column] !== null) {
            $type =& $mapping[1];

            if ($type === PersistableObject ::T_STRING) {
               $object->$property =& $dataRow[$column];
            }
            elseif ($type === PersistableObject ::T_INT) {
               $object->$property = (int) $dataRow[$column];
            }
            elseif ($type === PersistableObject ::T_FLOAT) {
               $object->$property = (float) $dataRow[$column];
            }
            elseif ($type === PersistableObject ::T_BOOL) {
               $object->$property = (bool) $dataRow[$column];
            }
            elseif ($type === PersistableObject ::T_SET) {
               $object->$property = (strLen($dataRow[$column]) == 0) ? array() : explode(',', $dataRow[$column]);
            }
            else {
               throw new RuntimeException('Unknown data type \''.$type.'\' in database mapping of '.get_class($object).'::'.$property);
            }
         }
      }
      return $object;
   }
}
?>
