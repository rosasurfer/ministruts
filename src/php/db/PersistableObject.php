<?
/**
 * PersistableObject
 *
 * Abstrakte Superklasse für Objekte, die dauerhaft gespeichert werden können.
 */
abstract class PersistableObject extends Object {


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
}
?>
