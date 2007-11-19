<?
/**
 * ActionForm
 */
abstract class ActionForm extends Object {


   protected $actionKey;      // DispatchAction-Key


   /**
    * Constructor
    *
    * Erzeugt eine neue ActionForm für den aktuellen Request.
    *
    * @param Request $request - der aktuelle Request
    */
   public function __construct(Request $request) {
      // ggf. definierten Dispatch-Parameter auslesen
      if (isSet($_REQUEST['action']))
         $this->actionKey = $_REQUEST['action'];

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


   /**
    * Gibt den DispatchAction-Key zurück, sofern er angegeben wurde (siehe java.struts.DispatchAction).
    *
    * @return string - Action-Key oder NULL, wen kein Wert übertragen wurde
    */
   public function getActionKey() {
      return $this->actionKey;
   }
}
?>
