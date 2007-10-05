<?
/**
 * BaseActionForm
 */
abstract class BaseActionForm extends Object {


   protected $actionKey;      // DispatchAction-Key


   /**
    * Constructor
    */
   public function __construct() {
      // Objekt global zugänglich machen (für Zugriff aus der HTML-Seite)
      $GLOBALS['form'] = $this;

      // Menge der Input-Variablen neu definieren ($_COOKIE und $_FILES sind kein User-Input)
      $_REQUEST = array_merge($_GET, $_POST);

      // evt. definierten Dispatch-Parameter auslesen
      if (isSet($_REQUEST['action']))
         $this->actionKey = $_REQUEST['action'];

      // Request-Parameter einlesen
      $this->populate();
   }


   /**
    * Liest die übergebenen Request-Parameter in das Formular-Objekt ein (muß implementiert werden).
    */
   abstract protected function populate();


   /**
    * Validiert die übergebenen Parameter. Kann anwendungsabhängig überschrieben werden.
    *
    * @return boolean - true, wenn die übergebenen Parameter gültig sind,
    *                   false andererseits
    */
   public function validate() {
      return true;
   }


   /**
    * Gibt den DispatchAction-Key zurück, sofern einer übertragen wurde (siehe java.struts.DispatchAction).
    *
    * @return string - Action-Key oder NULL, wen kein Schlüssel übertragen wurde
    */
   public function getActionKey() {
      return $this->actionKey;
   }


   /**
    * Alias für getActionKey()
    *
    * @return string
    */
   public function getDispatchKey() {
      return $this->getActionKey();
   }
}
?>
