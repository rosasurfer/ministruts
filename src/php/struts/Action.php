<?
/**
 * Action
 */
abstract class Action extends Object {


   protected /*ActionMapping*/ $mapping;


   /**
    * Constructor
    *
    * Erzeugt eine neue Action.
    *
    * @param ActionMapping $mapping - das Mapping, zu dem die Action gehört
    */
   public function __construct(ActionMapping $mapping) {
      $this->mapping = $mapping;
   }


   /**
    * Führt die Action aus.
    *
    * @param ActionForm $form     - The optional ActionForm for this request (if any).
    * @param Request    $request  - The HTTP request we are processing.
    * @param Response   $response - The HTTP response we are creating.
    *
    * @return ActionForward
    */
   abstract public function execute(ActionForm $form, Request $request, Response $response);
}
?>
