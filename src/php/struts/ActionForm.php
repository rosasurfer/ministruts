<?
/**
 * ActionForm
 */
abstract class ActionForm extends Object {


   protected /*Request*/ $request;     // der Request, mit dem wir verbunden sind
   protected             $actionKey;   // string: DispatchAction-Key


   /**
    * Constructor
    *
    * Erzeugt eine neue ActionForm für den aktuellen Request.
    *
    * @param Request $request - der aktuelle Request
    */
   public function __construct(Request $request) {
      $this->request = $request;

      // ggf. definierten Dispatch-Parameter auslesen
      if (isSet($_REQUEST['action']))
         $this->actionKey = $_REQUEST['action'];

      // Request-Parameter auslesen
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
    * @return boolean - TRUE, wenn die übergebenen Parameter gültig sind, FALSE andererseits
    */
   public function validate() {
      return true;
   }


   /**
    * Gibt den DispatchAction-Key zurück, sofern er angegeben wurde (siehe java.struts.DispatchAction).
    *
    * @return string - Action-Key oder NULL, wenn kein Wert angegeben wurde
    */
   public function getActionKey() {
      return $this->actionKey;
   }
}
?>
