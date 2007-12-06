<?
/**
 * ISingle
 *
 * Muß von jedem Singelton implementiert werden. Dient zum Zugriff auf die Instanz der Klasse und
 * ist ein Shortcut für Class::getInstance(). Gehört logisch gesehen in die Klasse Singleton, PHP
 * erlaubt jedoch keine statischen, abstrakten Methoden.
 */
interface ISingle {


   /**
    * Gibt die einzige Instanz des Singletons zurück.
    *
    * @return ISingle
    *
    * @see Singleton
    */
   static function me();
}
?>
