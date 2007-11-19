<?
/**
 * RoleProcessor
 */
abstract class RoleProcessor {


   /**
    * Prüft, ob der User, der den aktuellen Request ausgelöst hat, Inhaber der angegebenen Rollen ist.
    * Gibt NULL zurück, wenn die Verarbeitung fortgesetzt und der Zugriff gewährt werden soll, oder
    * eine ActionForward-Instanz, wenn der Zugriff nicht gewährt und statt dessen zu dem vom Forward
    * beschriebenen Ziel verzweigt werden soll.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return ActionForward oder NULL
    */
   abstract public function processRoles(Request $request, Response $response, ActionMapping $mapping);
}
?>
