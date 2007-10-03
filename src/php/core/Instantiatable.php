<?
/**
 * Instantiatable
 */
interface Instantiatable {

   /**
    * Gibt die Instanz einer Klasse zurück, wird hauptsächlich beim Zugriff auf Singleton's benutzt.
    *
    * @return Instantiatable
    *
    * @see Singleton
    */
   public static function me();
}
?>
