<?
/**
 * RequestProcessor
 */
class RequestProcessor extends Object {


   private /* ModuleConfig */ $config;


   /**
    * Erzeugt einen neuen RequestProcessor.
    *
    * @param ModuleConfig $config - Modulkonfiguration, der dieser RequestProcessor zugeordnet ist
    */
   public function __construct(ModuleConfig $config) {
      $this->config = $config;
   }


   /**
    * Verarbeitet den übergebenen Request.
    *
    * @param Request $request
    */
   public function process(Request $request) {

      echoPre($request);

      echoPre($this->config);
   }
}
?>
