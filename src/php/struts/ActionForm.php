<?
/**
 * ActionForm
 */
abstract class ActionForm extends Object {


   /**
    * Request, zu dem wir gehören
    */
   protected /*transient*/ /*Request*/ $request;


   /**
    * DispatchAction-Key
    */
   protected /*transient*/ /*string*/  $actionKey;


   /**
    * Constructor
    *
    * Erzeugt eine neue ActionForm für den aktuellen Request.
    *
    * @param Request $request - der aktuelle Request
    */
   public function __construct(Request $request) {
      $this->request = $request;

      // ggf. definierten DispatchAction-Key auslesen
      if     (isSet($_REQUEST['action'  ])) $this->actionKey = $_REQUEST['action'  ];
      elseif (isSet($_REQUEST['action.x'])) $this->actionKey = $_REQUEST['action.x'];  // submit-Type "image"

      // Parameter einlesen
      $this->populate($request);
   }


   /**
    * Liest die im Request übergebenen Parameter ein.  Muß anwendungsabhängig implementiert werden.
    *
    * @param Request $request - der aktuelle Request
    */
   abstract protected function populate(Request $request);


   /**
    * Ob die eingelesenen Parameter gültig sind. Muß anwendungsabhängig überschrieben werden.
    *
    * @return boolean - TRUE, wenn die übergebenen Parameter gültig sind, FALSE andererseits
    *                   (diese Default-Implementierung gibt immer TRUE zurück)
    */
   public function validate() {
      return true;
   }


   /**
    * Gibt den DispatchAction-Key zurück (Beschreibung: siehe java.struts.DispatchAction).
    *
    * @return string - Action-Key oder NULL, wenn kein Wert angegeben wurde
    */
   public function getActionKey() {
      return $this->actionKey;
   }


   /**
    * Verhindert das Serialisieren transienter Werte.  Wird intern vorm Serialisieren aufgerufen.
    *
    * @return array - Array mit den zu serialisierenden Eigenschaften
    */
   final public function __sleep() {
      $array = (array) $this;

      $null = chr(0);
      unset($array["${null}*${null}request"], $array["${null}*${null}actionKey"]);

      return array_keys($array);
   }


   /**
    * Reinitialisiert die transienten Werte dieser Instanz.  Wird intern nach dem Deserialisieren
    * aufgerufen.
    */
   final public function __wakeUp() {
      $this->__construct(Request ::me());
   }
}
?>
