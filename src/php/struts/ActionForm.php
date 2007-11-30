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
      if     (isSet($_REQUEST['action'  ])) $this->actionKey = $_REQUEST['action'  ];
      elseif (isSet($_REQUEST['action_x'])) $this->actionKey = $_REQUEST['action_x'];     // input-Type 'image'


      // Parameter einlesen
      $this->populate($request);
   }


   /**
    * Liest die im Request übergebenen Parameter ein.  Muß anwendungsabhängig implementiert werden.
    *
    * @param Request $request
    */
   abstract protected function populate(Request $request);


   /**
    * Ob die eingelesenen Parameter gültig sind. Sollte anwendungsabhängig überschrieben werden.
    *
    * @return boolean - TRUE, wenn die übergebenen Parameter gültig sind, FALSE andererseits
    *                   (diese Default-Implementierung gibt immer TRUE zurück)
    */
   public function validate() {
      return true;
   }


   /**
    * Gibt den DispatchAction-Key zurück (siehe java.struts.DispatchAction).
    *
    * @return string - Action-Key oder NULL, wenn kein Wert angegeben wurde
    */
   public function getActionKey() {
      return $this->actionKey;
   }
}
?>
