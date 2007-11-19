<?
/**
 * Instantiatable
 */
interface Instantiatable {

   /**
    * Gibt die Instanz einer Klasse zurück, wird hauptsächlich beim Zugriff auf Singletons benutzt.
    *
    * @return Instantiatable
    *
    * @see Singleton
    */
   static function me();
}
?>
