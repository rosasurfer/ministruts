<?
/**
 * RequestProcessor
 */
class RequestProcessor extends Object {


   // ModuleConfig, zu der wir gehören
   protected /* ModuleConfig */ $moduleConfig;

   private $logDebug;
   private $logInfo;
   private $logNotice;


   /**
    * Erzeugt einen neuen RequestProcessor.
    *
    * @param ModuleConfig $config - Modulkonfiguration, der dieser RequestProcessor zugeordnet ist
    */
   public function __construct(ModuleConfig $config) {
      $loglevel = Logger ::getLogLevel(__CLASS__);

      $this->logDebug  = ($loglevel <= L_DEBUG);
      $this->logInfo   = ($loglevel <= L_INFO);
      $this->logNotice = ($loglevel <= L_NOTICE);

      $this->moduleConfig = $config;
   }


   /**
    * Verarbeitet den übergebenen Request und gibt entweder den entsprechenden Content aus oder
    * leitet auf eine andere Resource um.
    *
    * @param Request  $request
    * @param Response $response
    */
   final public function process(Request $request, Response $response) {

      // Pfad für die Mappingauswahl ermitteln
      $path = $this->processPath($request, $response);


      // falls notwendig, ein Locale setzen
      $this->processLocale($request, $response);


      // allgemeinen Preprocessing-Hook aufrufen
      if (!$this->processPreprocess($request, $response)) {
         $this->logDebug && Logger ::log('Preprocessor hook returned false', L_DEBUG, __CLASS__);
         return;
      }


      // ActionMessages aus der Session löschen
      $this->processCachedMessages($request, $response);


      // das zum angegebenen Pfad passende ActionMapping ermitteln
      $mapping = $this->processMapping($request, $response, $path);
      if (!$mapping) {
         $this->logDebug && Logger ::log('Could not find a mapping for this request', L_DEBUG, __CLASS__);
         return;
      }


      // Rollenbeschränkungen des Mappings überprüfen
      if (!$this->processRoles($request, $response, $mapping)) {
         $this->logDebug && Logger ::log('User does not have any required role, denying access', L_DEBUG, __CLASS__);
         return;
      }


      // falls im Mapping statt einer Action ein Forward konfiguriert wurde, diesen verarbeiten
      if (!$this->processMappingForward($request, $response, $mapping))
         return;


      // ActionForm des Mappings erzeugen
      $form = $this->processActionForm($request, $response, $mapping);


      // Action des Mappings erzeugen (Form wird schon hier übergeben, damit im User-Code NPEs vermieden werden)
      $action = $this->processActionCreate($request, $response, $mapping, $form);


      // Action aufrufen
      $forward = $this->processActionExceute($request, $response, $action, $form);


      // den zurückgegebenen ActionForward verarbeiten
      $this->processActionForward($request, $response, $forward);
   }


   /**
    * Gibt die module-relative Pfadkomponente des Requests, die für die ActionMapping-Auswahl benutzt wird, zurück.
    *
    * @param Request  $request
    * @param Response $response
    *
    * @return string
    */
   protected function processPath(Request $request, Response $response) {
      $path = $request->getPathInfo();
      $path = subStr($path, strlen(APPLICATION_ROOT_URL.$this->moduleConfig->getPrefix()));

      $this->logDebug && Logger ::log('Path used for mapping selection: '.$path, L_DEBUG, __CLASS__);
      return $path;
   }


   /**
    * Wählt bei Bedarf ein Locale für den aktuellen User aus.
    *
    * Note: Die Auswahl eines Locale löst automatisch die Erzeugung einer HttpSession aus.
    *
    * @param Request  $request
    * @param Response $response
    */
   protected function processLocale(Request $request, Response $response) {
   }


   /**
    * Allgemeiner Preprocessing-Hook, der bei Bedarf überschrieben werden kann. Muß TRUE
    * zurückgeben, wenn die Verarbeitung nach dem Aufruf normal fortgesetzt werden soll,
    * oder FALSE, wenn bereits eine Anwort generiert wurde.
    * Die Default-Implementierung macht nichts.
    *
    * @param Request  $request
    * @param Response $response
    *
    * @return boolean
    */
   protected function processPreprocess(Request $request, Response $response) {
      return true;
   }


   /**
    * Löscht ActionMessages, die in der HttpSession gespeichert sind und auf die schon zugegriffen wurde.
    * Dies erlaubt das Speichern von Nachrichten in der Session, die nur einmal angezeigt werden können und
    * danach automatisch verschwinden. Dadurch können z.B. trotz eines Redirects Fehlermeldungen angezeigt werden.
    *
    * @param Request  $request
    * @param Response $response
    */
   protected function processCachedMessages(Request $request, Response $response) {
      if ($request->isSession() && isSet($_SESSION[Struts ::ACTION_MESSAGE_KEY])) {
         $errors =& $_SESSION[Struts ::ACTION_MESSAGE_KEY];

         foreach ($errors as $key => $error)
            if ($error['accessed'])
               unset($_SESSION[Struts ::ACTION_MESSAGE_KEY][$key]);

         if(sizeOf($errors) == 0)
            unset($_SESSION[Struts ::ACTION_MESSAGE_KEY]);
      }
   }

   /**
    * Ermittelt das zu benutzende ActionMapping.
    *
    * @param Request  $request
    * @param Response $response
    * @param string   $path     - Pfadkomponente zur ActionMapping-Auswahl
    *
    * @return ActionMapping
    */
   protected function processMapping(Request $request, Response $response, $path) {
      $mapping = $this->moduleConfig->findMapping($path);

      if (!$mapping) {      // no mapping can be found to process this request
         echoPre("Not found: 404\n\nThe requested URL $path was not found on this server");
      }

      return $mapping;
   }


   /**
    * Wenn die Action Zugriffsbeschränkungen hat, sicherstellen, daß der User mindestens eine der angegebenen
    * Rollen inne hat. Gibt TRUE zurück, wenn die Verarbeitung fortgesetzt und der Zugriff gewährt werden soll,
    * oder FALSE, wenn der Zugriff nicht gewährt wird.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return boolean
    */
   protected function processRoles(Request $request, Response $response, ActionMapping $mapping) {
      return true;
   }


   /**
    * Verarbeitet einen direkt im ActionMapping angegebenen ActionForward (wenn angegeben). Gibt TRUE zurück, wenn
    * die Verarbeitung fortgesetzt werden soll, oder FALSE, wenn der Request bereits beendet wurde.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return boolean
    */
   protected function processMappingForward(Request $request, Response $response, ActionMapping $mapping) {
      $forward = $mapping->getForward();
      if (!$forward)
         return true;

      $this->processActionForward($request, $response, $forward);
      return false;
   }


   /**
    * Erzeugt und gibt die ActionForm des angegebenen Mappings zurück (wenn konfiguriert). Ist keine ActionForm konfiguriert,
    * wird NULL zurückgegeben.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return ActionForm
    */
   protected function processActionForm(Request $request, Response $response, ActionMapping $mapping) {
      $class = $mapping->getForm();
      if (!$class)
         return null;

      return new $class($request);
   }


   /**
    * Erzeugt und gibt die Action zurück, die für das angegebene Mapping konfiguriert wurde.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    * @param ActionForm    $form     - ActionForm, die konfiguriert wurde oder NULL
    *
    * @return Action
    */
   protected function processActionCreate(Request $request, Response $response, ActionMapping $mapping, ActionForm $form=null) {
      $class = $mapping->getAction();

      return new $class($mapping, $form);
   }


   /**
    * Übergibt den Request der angegebenen Action zur Bearbeitung und gibt den von der Action zurückgegebenen ActionForward zurück.
    *
    * @param Request    $request
    * @param Response   $response
    * @param Action     $action
    *
    * @return ActionForward
    */
   protected function processActionExceute(Request $request, Response $response, Action $action) {
      return $action->execute($request, $response);
   }


   /**
    * Verarbeitet den von der Action zurückgegebenen ActionForward.  Leitet auf die Resource weiter, die der ActionForward bezeichnet.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionForward $forward
    */
   protected function processActionForward(Request $request, Response $response, ActionForward $forward=null) {
      if ($forward) {
         if ($forward->isRedirect()) {
            echoPre('redirect');
         }
         else {
            echoPre('include');
         }
         echoPre($forward);
      }
   }
}
?>
