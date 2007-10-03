<?
/**
 */
interface Instantiatable {

   /**
    * Gibt die Instanz einer Klasse zurück, wird hauptsächlich benutzt zum Zugriff auf Singleton's.
    *
    * @return Instantiatable
    *
    * @see Singleton
    */
   public static function me();
}
?>
