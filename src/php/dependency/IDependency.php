<?
/**
 * IDependency
 *
 * Interface für Klassen, die eine Abhängigkeit darstellen. Wird benutzt, um abhängig von einem
 * bestimmten Ereignis oder Zustandswechsel Aktionen auszulösen.  Soll ein Zustand prozeßübergreifend
 * verfolgt werden, muß die Instanz in einem entprechenden Persistenz-Container gespeichert werden
 * (Session, Datenbank, Clustercache, Dateisystem ...).  Implementierende Klassen müssen serialisierbar
 * sein.
 *
 * @see FileDependency
 */
interface IDependency {


   /**
    * Ob das der Abhängigkeit zugrundeliegende Ereignis oder der Zustandswechsel eingetreten sind.
    *
    * @return boolean
    */
   function isStatusChanged();
}
?>
