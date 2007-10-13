<?
/**
 * RequestProcessor
 */
class RequestProcessor extends Object {


   // Module, zu dem wir gehören
   protected /*Module*/ $module;


   private $logDebug, $logInfo, $logNotice;


   /**
    * Erzeugt einen neuen RequestProcessor.
    *
    * @param Module $module - Module, dem dieser RequestProcessor zugeordnet ist
    */
   public function __construct(Module $module) {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      $this->logDebug  = ($loglevel <= L_DEBUG);
      $this->logInfo   = ($loglevel <= L_INFO);
      $this->logNotice = ($loglevel <= L_NOTICE);

      $this->module = $module;
   }


   /**
    * Verarbeitet den übergebenen Request und gibt entweder den entsprechenden Content aus oder
    * leitet auf eine andere Resource um.
    *
    * @param Request  $request
    * @param Response $response
    */
   final public function process(Request $request, Response $response) {

      // falls notwendig, ein Locale setzen
      $this->processLocale($request, $response);


      // ActionMessages aus der Session löschen
      $this->processCachedMessages($request, $response);


      // passendes Mapping ermitteln
      $mapping = $this->processMapping($request, $response);
      if (!$mapping)
         return;


      // Methodenbeschränkungen des Mappings überprüfen
      if (!$this->processMethod($request, $response, $mapping))
         return;


      // falls im Mapping statt einer Action ein Forward konfiguriert wurde, diesen verarbeiten
      if (!$this->processMappingForward($request, $response, $mapping))
         return;


      // ActionForm des Mappings erzeugen
      $form = $this->processActionForm($request, $response, $mapping);


      // Action des Mappings erzeugen (Form wird der Action schon hier übergeben, damit der User-Code einfacher sein kann)
      $action = $this->processActionCreate($request, $response, $mapping, $form);


      // Action aufrufen
      $forward = $this->processActionExecute($request, $response, $action, $form);
      if (!$forward)
         return;


      // den zurückgegebenen ActionForward verarbeiten
      $this->processActionForward($request, $response, $forward);
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
    *
    * @return ActionMapping
    */
   protected function processMapping(Request $request, Response $response) {
      // Pfad für die Mappingauswahl ermitteln ...
      $appPath    = $request->getAttribute(Struts ::APPLICATION_PATH_KEY);
      $scriptName = $request->getPathInfo();
      $path = subStr($scriptName, strlen($appPath.$this->module->getPrefix()));

      $this->logDebug && Logger ::log('Path used for mapping selection: '.$path, L_DEBUG, __CLASS__);

      // ... und Mapping suchen
      $mapping = $this->module->findMapping($path);
      if (!$mapping) {
         $this->logInfo && Logger ::log('Could not find a mapping for this request', L_INFO, __CLASS__);
         echoPre("Not found: 404\n\nThe requested URL $path was not found on this server");
      }
      return $mapping;
   }


   /**
    * Wenn die Action Methodenbeschränkungen des Requests hat, sicherstellen, daß der Request der angegebenen
    * Methode entspricht. Gibt TRUE zurück, wenn die Verarbeitung fortgesetzt werden soll oder FALSE, wenn der
    * Zugriff nicht gewährt wird.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return boolean
    */
   protected function processMethod(Request $request, Response $response, ActionMapping $mapping) {
      $method = $mapping->getMethod();

      if ($method===null || $method == $request->getMethod())
         return true;

      $this->logDebug && Logger ::log('Request does not have the required method type, denying access', L_DEBUG, __CLASS__);
      echoPre('Access denied: 403');

      return false;
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

      // ActionForm erzeugen und im Request speichern
      $form = new $class($request);
      $request->setAttribute(Struts ::ACTION_FORM_KEY, $form);

      return $form;
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
   protected function processActionExecute(Request $request, Response $response, Action $action) {
      $forward = null;

      // Alles kapseln, damit Postprocessing-Hook auch nach Exceptions aufgerufen wird (für Transaction-Rollback o.ä.)
      $throwable = null;
      try {
         // allgemeiner Preprocessing-Hook
         $forward = $action->executeBefore($request, $response);

         // Action nur ausführen, wenn executeBefore() nicht Abbruch signalisiert hat
         if ($forward === null)
            $forward = $action->execute($request, $response);
      }
      catch (Exception $ex) {
         $throwable = $ex;
      }

      // allgemeiner Postprocessing-Hook
      $forward = $action->executeAfter($request, $response, $forward);

      // jetzt aufgetretene Exceptions weiterreichen
      if ($throwable)
         throw $throwable;

      return $forward;
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
            $appPath = $request->getAttribute(Struts ::APPLICATION_PATH_KEY);
            $url = $appPath.$this->module->getPrefix().$forward->getPath();
            redirect($url);
         }
         else {
            $path = $forward->getPath();
            $tile = $this->module->findTile($path);

            if (!$tile) {
               // create a simple tile on the fly
               $tile = new $this->module->getTilesClass($this);
               $tile->setName('.name')
                    ->setPath($path)
                    ->freeze();
            }

            // render the tile
            $tile->render($request, $response);
         }
      }
   }
}
?>
