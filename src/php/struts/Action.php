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
    * Führt die Action aus.
    *
    * @param Request  $request  - The HTTP request we are processing.
    * @param Response $response - The HTTP response we are creating.
    *
    * @return ActionForward
    */
   abstract public function execute(Request $request, Response $response);
}
?>
