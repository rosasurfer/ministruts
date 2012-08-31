<?php
/**
 * PersistableObject
 *
 * Abstrakte Basisklasse für gespeicherte Objekte.
 */
abstract class PersistableObject extends Object implements IDaoConnected {


   // Flag für den aktuellen Änderungsstatus der Instanz
   protected /*bool*/     $modified = false;
   protected /*string[]*/ $modifications;


   // Standard-Properties jeder Instanz
   protected /*int*/    $id;           // Primary Key
   protected /*string*/ $created;      // Erzeugungszeitpunkt: datetime
   protected /*string*/ $version;      // Versionsnummer:      timestamp
   protected /*string*/ $deleted;      // Löschzeitpunkt:      datetime


   /**
    * Default-Constructor.
    *
    * Der Constructor ist final, neue PersistableObject-Instanzen können nur per Helfermethode erzeugt werden.
    *
    *
    * Beispiel:
    * ---------
    *
    * class MyClass extends PersistableObject {
    *
    *    private $property = null;
    *
    *    public static function create($arg) {
    *       // parameter validation...
    *       $instance = new self();
    *       $instance->property = $arg;
    *       return $instance;
    *    }
    * }
    *
    * $object = MyClass::create('foo');
    * $object->save();
    */
   final protected function __construct() {
      $this->created = $this->touch();
   }


   /**
    * Gibt die ID dieser Instanz zurück.
    *
    * @return int - ID (primary key)
    */
   public function getId() {
      return $this->id;
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
    * Gibt die Versionsnummer dieser Instanz zurück.
    *
    * @return string - Versionsnummer (Zeitpunkt der letzten Änderung)
    */
   public function getVersion() {
      return $this->version;
   }


   /**
    * Aktualisiert die Versions-Nr. dieser Instanz und gibt den neuen Wert zurück.  Wird zur externen
    * Generierung der Versions-Informationen verwendet.
    *
    * @return string - Versionsnummer (Zeitpunkt der letzten Änderung)
    */
   final protected function touch() {
      return $this->version = date('Y-m-d H:i:s');
   }


   /**
    * Gibt den Zeitpunkt des "Soft-Delete" dieser Instanz zurück.
    *
    * @param string $format - Zeitformat
    *
    * @return string - Zeitpunkt
    */
   public function getDeleted($format = 'Y-m-d H:i:s')  {
      if ($format == 'Y-m-d H:i:s')
         return $this->deleted;

      return formatDate($format, $this->deleted);
   }


   /**
    * Ob diese Instanz in der Datenbank als "gelöscht" markiert ist (Soft-Delete).
    *
    * @return bool
    */
   public function isDeleted() {
      return ($this->deleted !== null);
   }


   /**
    * Zeigt an, ob die aktuelle Instanz bereits gespeichert ist oder nicht.
    * Muß überschrieben werden, wenn die Primary Key-Spalte der Klasse nicht 'id' heißt.
    *
    * @return bool
    */
   public function isPersistent() {
      return ($this->id !== null);
   }


   /**
    * Speichert diese Instanz in der Datenbank.
    *
    * @return PersistableObject
    */
   final public function save() {
      if (!$this->isPersistent()) {
         $this->insert();
      }
      elseif ($this->modified) {
         $this->update();
      }
      else {
         // Logger ::log('Nothing to save, '.get_class($this).' instance is in sync with the database.', L_NOTICE, __CLASS__);
      }
      $this->updateLinks();
      $this->modified = false;

      return $this;
   }


   /**
    * Fügt diese Instanz in die Datenbank ein.  Diese Methode muß von der konkreten Klasse implementiert werden.
    *
    * @return PersistableObject
    */
   protected function insert() {
      throw new UnimplementedFeatureException('You need to implement '.get_class($this).'->'.__FUNCTION__.'() to insert '.get_class($this).'s.');
   }


   /**
    * Aktualisiert diese Instanz in der Datenbank.  Diese Methode muß von der konkreten Klasse implementiert werden.
    *
    * @return PersistableObject
    */
   protected function update() {
      throw new UnimplementedFeatureException('You need to implement '.get_class($this).'->'.__FUNCTION__.'() to update '.get_class($this).'s.');
   }


   /**
    * Aktualisiert die Kreuzverknüpfungen dieser Instanz in der Datenbank.  Diese Methode muß von der
    * konkreten Klasse implementiert werden.
    *
    * @return PersistableObject
    */
   protected function updateLinks() {
      return $this;
   }


   /**
    * Löscht diese Instanz aus der Datenbank.  Diese Methode muß von der konkreten Klasse implementiert werden.
    *
    * @return NULL
    */
   public function delete() {
      throw new UnimplementedFeatureException('You need to implement '.get_class($this).'->'.__FUNCTION__.'() to delete '.get_class($this).'s.');
   }


   /**
    * Erzeugt eine neue Instanz.
    *
    * @param string $class - Klassenname der zu erzeugenden Instanz
    * @param array  $row   - Array mit Instanzdaten (entspricht einer Datenbankzeile)
    *
    * @return PersistableObject
    */
   public static function createInstance(&$class, array &$row) {
      $object = new $class();
      if (!$object instanceof self) throw new plInvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);

      $mappings = $object->dao()->mapping;

      foreach ($mappings['fields'] as $property => &$mapping) {
         $column =& $mapping[0];

         if ($row[$column] !== null) {
            $type =& $mapping[1];

            switch ($type) {
               case CommonDAO ::T_STRING:
                  $object->$property =&        $row[$column]; break;
               case CommonDAO ::T_INT:
                  $object->$property =   (int) $row[$column]; break;
               case CommonDAO ::T_FLOAT:
                  $object->$property = (float) $row[$column]; break;
               case CommonDAO ::T_BOOL:
                  $object->$property =  (bool) $row[$column]; break;
               case CommonDAO ::T_SET:
                  $object->$property = strLen($row[$column]) ? explode(',', $row[$column]) : array();
                  break;
               default:
                  throw new plInvalidArgumentException('Unknown data type "'.$type.'" in database mapping of '.$class.'::'.$property);
            }
         }
      }
      return $object;
   }


   /**
    * Gibt den DAO für diese Instanz zurück.
    *
    * @param string $class - Klassenname der Instanz
    *
    * @return CommonDAO
    */
   protected static function getDAO($class) {
      return Singleton ::getInstance($class.'DAO');
   }
}
?>
