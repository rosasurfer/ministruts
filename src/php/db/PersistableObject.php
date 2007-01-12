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


   /**
    * Mappt Datenbankspalten auf Instanzvariablen.
    *
    * @param property - Name der zu mappenden Tabellenspalte
    * @param value    - Wert, auf den die entsprechende Variable der Klasseninstanz gesetzt werden soll
    */
   abstract protected function __set($property, $value);
}
?>
