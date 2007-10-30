<?
/**
 * Allgemeines Interface für HttpResponse-Implementierungen.
 */
interface HttpResponse {


   /**
    * Gibt den HTTP-Status zurück.
    *
    * @return int - Statuscode
    */
   function getStatus();


   /**
    * Gibt die erhaltenen Header zurück.
    *
    * @return array - Array mit Headern
    */
   function getHeaders();


   /**
    * Ob ein Header mit dem angegebenen Namen existiert.
    *
    * @param $name - Name des Headers
    *
    * @return boolean
    */
   function isHeader($name);


   /**
    * Gibt den Header mit dem angegebenen Namen zurück.
    *
    * @param $name - Name des Headers
    *
    * @return string
    */
   function getHeader($name);                   // !!! to-do: getHeader() muß ein Array zurückgeben


   /**
    * Gibt den Content des HttpResponse zurück.
    *
    * @return string - Content
    */
   function getContent();
}
?>
