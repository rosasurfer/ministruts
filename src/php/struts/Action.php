<?
/**
 * Action
 */
abstract class Action extends Object {


   protected /* ActionMapping */ $mapping;
   protected /* ActionForm    */ $form;


   /**
    * Constructor
    *
    * Erzeugt eine neue Action.
    *
    * @param ActionMapping $mapping - das Mapping, zu dem die Action gehört
    * @param ActionForm    $form    - die ActionForm oder NULL, wenn keine angegeben wurde
    */
   public function __construct(ActionMapping $mapping, ActionForm $form=null) {
      $this->mapping = $mapping;
      $this->form = $form;
   }


   /**
    * Allgemeiner Pre-Processing-Hook, der von Subklassen bei Bedarf überschrieben werden kann.  Gibt
    * NULL zurück, wenn die Verarbeitung fortgesetzt werden soll oder eine ActionForward-Instanz, wenn
    * die Verarbeitung abgebrochen und zu dem vom Forward beschriebenen Ziel verzweigt werden soll.
    * Die Default-Implementierung macht nichts.
    *
    * @param Request       $request
    * @param Response      $response
    *
    * @return ActionForward oder NULL
    */
   public function executeBefore(Request $request, Response $response) {
      return null;
   }


   /**
    * Führt die Action aus und gibt einen ActionForward zurück, der beschreibt, zu welcher Resource verzweigt
    * werden soll.  Muß implementiert werden.
    *
    * @param Request  $request
    * @param Response $response
    *
    * @return ActionForward
    */
   abstract public function execute(Request $request, Response $response);


   /**
    * Allgemeiner Post-Processing-Hook, der von Subklassen bei Bedarf überschrieben werden kann.
    *
    * Besondere Vorsicht ist anzuwenden, da zu der Zeit, da diese Methode aufgerufen wird, der Content schon ausgeliefert
    * und der Response schon fertiggestellt sein kann. Die Methode ist für Aufräumarbeiten nützlich, z.B. das Committen von
    * Transaktionen oder das Schließen von Datenbankverbindungen. Die Default-Implementierung macht nichts.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionForward $forward  - der originale ActionForward, wie ihn die Action zurückgegeben hat
    *
    * @return ActionForward - der originale oder ein modifizierter ActionForward (z.B. zusätzliche Query-Parameter)
    */
   public function executeAfter(Request $request, Response $response, ActionForward $forward=null) {
      return $forward;
   }
}
?>
