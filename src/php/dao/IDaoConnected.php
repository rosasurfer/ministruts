<?php
/**
 * Interface zum Finden des DAO einer Instanz.
 *
 * @see PersistableObject
 */
interface IDaoConnected {


   /**
    * Gibt den DAO für die Instanz zurück.
    *
    * @return CommonDAO
    */
   public static function dao();
}
?>
