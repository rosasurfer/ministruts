<?php
/**
 * Interface zum Finden des DAO einer Instanz.
 *
 * @see PersistableObject
 */
interface IDaoConnected {


   /**
    * Return the DAO of this class.
    *
    * @return CommonDAO
    */
   public static function dao();
}
