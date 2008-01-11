<?
/**
 * HelloWorldAction
 */
class HelloWorldAction extends Action {


   /**
    * Execute the action.
    *
    * @return ActionForward
    */
   public function execute(Request $request, Response $response) {

      // ... do something useful

      if (true) {
         return 'success';    // shortkey for $this->findForward('success');
      }

      return 'error';         // shortkey for $this->findForward('error');
   }
}
?>
