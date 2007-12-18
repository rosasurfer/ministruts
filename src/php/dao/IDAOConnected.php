<?
/**
 * Hilfs-Interface zum Finden des DAO eines PersistableObject.
 */
interface IDAOConnected {


   /**
    * Gibt das DAO eines PersistableObject zurück.
    *
    * @return CommonDAO
    */
   static function dao();
}
?>
