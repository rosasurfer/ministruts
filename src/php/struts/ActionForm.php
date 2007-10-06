<?
/**
 * ActionForm
 */
abstract class ActionForm extends Object {


   /**
    * Constructor
    *
    * Erzeugt eine neue ActionForm für den aktuellen Request.
    *
    * @param Request $request - der aktuelle Request
    */
   public function __construct(Request $request) {
      // ActionForm global zugänglich machen (für Zugriff aus der HTML-Seite)
      $GLOBALS['form'] = $this;

      // Request-Parameter einlesen
      $this->populate($request);
   }


   /**
    * Liest die übergebenen Parameter aus dem Request ein.  Kann anwendungsabhängig überschrieben werden.
    *
    * @param Request $request
    */
   protected function populate(Request $request) {
   }


   /**
    * Validiert die eingelesenen Parameter. Kann anwendungsabhängig überschrieben werden.
    *
    * @return boolean - TRUE, wenn die übergebenen Parameter gültig sind,
    *                   FALSE andererseits
    */
   public function validate() {
      return true;
   }
}
?>
