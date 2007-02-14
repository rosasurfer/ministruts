<?
/**
 * PersistableObject
 *
 * Abstrakte Superklasse für Objekte, die dauerhaft gespeichert werden können.
 */
abstract class PersistableObject extends Object {


   /* Mapping-Constante: numerische Spalte */
   const C_NUMERIC  = 1;

   /* Mapping-Constante: String-Spalte */
   const C_STRING   = 2;

   /* Mapping-Constante: Spalte, die NULL sein kann */
   const C_NULL     = 3;

   /* Mapping-Constante: Spalte, die nicht NULL sein kann */
   const C_NOT_NULL = 4;


   /* leeres Datenbankmapping (array) */
   protected static $mapping;

   /* Flag für den aktuellen Änderungsstatus einer Instanz (boolean) */
   protected $isModified;


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
   }


   /**
    * Fügt diese Instanz in die Datenbank ein.
    */
   protected function insert() {
      throw new RuntimeException('Method not implemented');
   }


   /**
    * Aktualisiert diese Instanz in der Datenbank.
    */
   protected function update() {
      throw new RuntimeException('Method not implemented');
   }
}
?>
