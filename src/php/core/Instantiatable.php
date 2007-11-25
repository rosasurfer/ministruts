<?
/**
 * Instantiatable
 *
 * Das Interface wird von jedem Singelton implementiert und zum Zugriff auf die Instanz der Klasse benutzt.
 * Es definiert eine statische Methode, aus diesem Grunde ist es nicht direkt in Singleton integriert.
 * (Singleton müßte eine abstrakte, statische Methode definieren, was unmöglich ist)
 */
interface Instantiatable {


   /**
    * Gibt die Instanz der implementierenden Klasse zurück.
    *
    * @return Instantiatable
    *
    * @see Singleton
    */
   static function me();
}
?>
