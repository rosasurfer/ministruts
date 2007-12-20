<?
/**
 * Interface zum Finden des DAO eines PersistableObject.
 */
interface IDaoConnected {


   /**
    * Gibt den DAO für ein PersistableObject zurück.
    *
    * @return BaseDAO
    */
   static function dao();
}
?>
