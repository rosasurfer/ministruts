<?
/**
 * IDependency
 *
 * Interface für Klassen, die eine Abhängigkeit von einem bestimmten Zustand oder einer bestimmten
 * Bedingung darstellen. Wird benutzt, um abhängig von einer Änderung Aktionen auszulösen.  Soll ein
 * Zustand prozeßübergreifend verfolgt werden, muß die Instanz in einem entprechenden Persistenz-Container
 * gespeichert werden (Session, Datenbank, Clustercache, Dateisystem, etc.).  Implementierende Klassen
 * müssen serialisierbar sein.
 */
interface IDependency {


   /**
    * Gibt die Mindestgültigkeit der Abhängigkeit zurück.
    *
    * @return int - Mindestgültigkeit in Sekunden
    */
   public function getMinValidity();


   /**
    * Ob das zu überwachende Ereignis oder ein Zustandswechsel eingetreten sind oder nicht.
    *
    * @return boolean - TRUE, wenn die Abhängigkeit weiterhin erfüllt ist.
    *                   FALSE, wenn der Zustandswechsel eingetreten ist und die Abhängigkeit nicht mehr erfüllt ist.
    */
   public function isValid();
}
?>
