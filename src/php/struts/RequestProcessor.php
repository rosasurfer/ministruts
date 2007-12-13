<?
/**
 * RequestProcessor
 */
class RequestProcessor extends Object {


   // Module, zu dem wir gehören
   protected /*Module*/ $module;


   private $logDebug, $logInfo, $logNotice;  // boolean


   /**
    * Erzeugt einen neuen RequestProcessor.
    *
    * @param Module $module - Module, dem dieser RequestProcessor zugeordnet ist
    */
   public function __construct(Module $module) {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      $this->logDebug  = ($loglevel <= L_DEBUG );
      $this->logInfo   = ($loglevel <= L_INFO  );
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

      // falls angefordert, Session starten
      $this->processSession($request, $response);


      // falls notwendig, ein Locale setzen
      $this->processLocale($request, $response);


      // ActionMessages aus der Session löschen
      $this->processCachedActionMessages($request, $response);


      // das passende Mapping ermitteln
      $mapping = $this->processMapping($request, $response);
      if (!$mapping)
         return;


      // Methodenbeschränkungen des Mappings überprüfen
      if (!$this->processMethod($request, $response, $mapping))
         return;


      // benötigte Rollen überprüfen
      if (!$this->processRoles($request, $response, $mapping))
         return;


      // ActionForm vorbereiten
      $form = $this->processActionForm($request, $response, $mapping);


      // falls konfiguriert, ActionForm validieren
      if ($form && !$this->processFormValidate($request, $response, $mapping, $form))
         return;


      // falls statt einer Action ein direkter Forward konfiguriert wurde, diesen verarbeiten
      if (!$this->processMappingForward($request, $response, $mapping))
         return;


      // Action erzeugen (Form und Mapping werden schon hier übergeben, damit User-Code einfacher wird)
      $action = $this->processActionCreate($request, $response, $mapping, $form);


      // Action aufrufen
      $forward = $this->processActionExecute($request, $response, $action, $form);
      if (!$forward)
         return;


      // den zurückgegebenen ActionForward verarbeiten
      $this->processActionForward($request, $response, $forward);
   }


   /**
    * Startet eine HttpSession bzw. setzt eine vorhergehende fort, wenn eine Session-ID übertragen
    * wurde, die Session aber noch nicht läuft.
    *
    * @param Request  $request
    * @param Response $response
    */
   protected function processSession(Request $request, Response $response) {
      if (!$request->isSession() && $request->isSessionId()) {
         $session = $request->getSession();
      }
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
    * Speichert alle vorhandenen ActionMessages in der HttpSession zwischen. Beim nächsten Request werden
    * diese Messages automatisch zurück in den Request verschoben und stehen wieder zur Verfügung.
    *
    * @param Request  $request
    *
    * @see Request::setActionMessage()
    * @see RequestProcessor::processCachedActionMessages()
    */
   protected function cacheActionMessages(Request $request) {
      $errors = $request->getActionErrors();
      if (sizeOf($errors) == 0)
         return;

      $session = $request->getSession();

      foreach ($errors as $key => $value) {
         $_SESSION[Struts ::ACTION_ERRORS_KEY][$key] = $value;
      }

      // TODO: ActionMessages verarbeiten
   }


   /**
    * Verschiebt ActionMessages, die in der HttpSession zwischengespeichert sind, zurück in den aktuellen
    * Request. Wird verwendet, um ActionMessages über einen Redirect hinweg übertragen zu können.
    *
    * @param Request  $request
    * @param Response $response
    *
    * @see Request::setActionMessage()
    * @see RequestProcessor::cacheActionMessages()
    */
   protected function processCachedActionMessages(Request $request, Response $response) {
      if ($request->isSession()) {
         if (isSet($_SESSION[Struts ::ACTION_MESSAGES_KEY])) {
            $messages = $_SESSION[Struts ::ACTION_MESSAGES_KEY];
            $request->setAttribute(Struts ::ACTION_MESSAGES_KEY, $messages);
            unset($_SESSION[Struts ::ACTION_MESSAGES_KEY]);
         }

         if (isSet($_SESSION[Struts ::ACTION_ERRORS_KEY])) {
            $errors = $_SESSION[Struts ::ACTION_ERRORS_KEY];
            $request->setAttribute(Struts ::ACTION_ERRORS_KEY, $errors);
            unset($_SESSION[Struts ::ACTION_ERRORS_KEY]);
         }
         // TODO: ActionError -> ActionMessage
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
      $contextPath = $request->getAttribute(Struts ::APPLICATION_PATH_KEY);
      $requestPath = $request->getPath();
      $path = subStr($requestPath, strLen($contextPath.$this->module->getPrefix()));

      $this->logDebug && Logger ::log('Path used for mapping selection: '.$path, L_DEBUG, __CLASS__);

      // Mapping suchen und im Request speichern
      $mapping = $this->module->findMapping($path);
      if ($mapping) {
         $request->setAttribute(Struts ::ACTION_MAPPING_KEY, $mapping);
      }
      else {
         $this->logInfo && Logger ::log('Could not find a mapping for this request', L_INFO, __CLASS__);
         echoPre("Not found: 404\n\nThe requested URL ".$requestPath." was not found on this server");
         // TODO: HttpResponse modifizieren und status code setzen
      }

      return $mapping;
   }


   /**
    * Wenn die Action Request-Methodenbeschränkungen hat, sicherstellen, daß der Request der
    * angegebenen Methode entspricht. Gibt TRUE zurück, wenn die Verarbeitung fortgesetzt werden soll
    * oder FALSE, wenn der Zugriff nicht gewährt wird.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return boolean
    */
   protected function processMethod(Request $request, Response $response, ActionMapping $mapping) {
      $method = $mapping->getMethod();

      if (!$method || $method==$request->getMethod())
         return true;

      $this->logDebug && Logger ::log('Request does not have the required method type, denying access', L_DEBUG, __CLASS__);
      echoPre('Access denied: 403');

      // TODO: auf Default-Mapping umleiten
      return false;
   }


   /**
    * Wenn die Action Zugriffsbeschränkungen hat, sicherstellen, daß der User Inhaber der angegebenen
    * Rollen ist.  Gibt TRUE zurück, wenn die Verarbeitung fortgesetzt und der Zugriff gewährt werden
    * soll, oder FALSE, wenn der Zugriff nicht gewährt und der Request bereits beendet wurde.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return boolean
    */
   protected function processRoles(Request $request, Response $response, ActionMapping $mapping) {
      if ($mapping->getRoles() === null)
         return true;

      $forward = $this->module->getRoleProcessor()->processRoles($request, $response, $mapping);
      if (!$forward)
         return true;

      $this->processActionForward($request, $response, $forward);
      return false;
   }


   /**
    * Erzeugt und gibt die ActionForm des angegebenen Mappings zurück (wenn konfiguriert). Ist keine
    * ActionForm konfiguriert, wird NULL zurückgegeben.
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
    * Wenn Form-Validierung für das Mapping aktiviert ist, wird die ActionForm des Mappings validiert.
    * Treten dabei Validation-Fehler auf, wird auf die konfigurierte Fehler-Resource weitergeleitet.
    * Gibt TRUE zurück, wenn die Verarbeitung fortgesetzt werden soll, oder FALSE, wenn Fehler
    * aufgetreten sind und der Request bereits beendet wurde.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    * @param ActionForm    $form
    *
    * @return boolean
    */
   protected function processFormValidate(Request $request, Response $response, ActionMapping $mapping, ActionForm $form) {
      // TODO: Form muß immer validiert werden, solange invalidate nicht "false" ist
      $forward = $mapping->getFormErrorForward();
      if (!$forward)
         return true;

      if ($form->validate())
         return true;

      $this->processActionForward($request, $response, $forward);
      return false;
   }


   /**
    * Verarbeitet einen direkt im ActionMapping angegebenen ActionForward (wenn angegeben). Gibt TRUE
    * zurück, wenn die Verarbeitung fortgesetzt werden soll, oder FALSE, wenn der Request bereits
    * beendet wurde.
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
      $actionClass = $mapping->getAction();

      return new $actionClass($mapping, $form);
   }


   /**
    * Übergibt den Request zur Bearbeitung an die konfigurierte Action und gibt den von ihr
    * zurückgegebenen ActionForward zurück.
    *
    * @param Request    $request
    * @param Response   $response
    * @param Action     $action
    *
    * @return ActionForward
    */
   protected function processActionExecute(Request $request, Response $response, Action $action) {
      $forward = null;
      $throwable = null;

      // Alles kapseln, damit Postprocessing-Hook auch nach Exceptions aufgerufen werden kann (z.B. Transaction-Rollback o.ä.)
      try {
         // allgemeinen Preprocessing-Hook aufrufen
         $forward = $action->executeBefore($request, $response);

         // Action nur ausführen, wenn executeBefore() nicht schon Abbruch signalisiert hat
         if ($forward === null)
            $forward = $action->execute($request, $response);
      }
      catch (Exception $ex) {
         $throwable = $ex;    // evt. aufgetretene Exception zwischenspeichern
      }

      // falls statt eines ActionForwards ein Forward-Identifier zurückgegeben wurde, diesen auflösen
      if (is_string($forward))
         $forward = $action->getMapping()->findForward($forward);


      // allgemeinen Postprocessing-Hook aufrufen
      $forward = $action->executeAfter($request, $response, $forward);


      // jetzt aufgetretene Exceptions weiterreichen
      if ($throwable)
         throw $throwable;

      return $forward;
   }


   /**
    * Verarbeitet den von der Action zurückgegebenen ActionForward.  Leitet auf die Resource weiter,
    * die der ActionForward bezeichnet.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionForward $forward
    */
   protected function processActionForward(Request $request, Response $response, ActionForward $forward) {
      if ($forward->isRedirect()) {
         $this->cacheActionMessages($request);

         $context = $request->getAttribute(Struts ::APPLICATION_PATH_KEY);
         $url = $context.$this->module->getPrefix().$forward->getPath();
         // TODO: QueryString kodieren
         HeaderUtils ::redirect($url);
      }
      else {
         $path = $forward->getPath();
         $tile = $this->module->findTile($path);

         if (!$tile) {     // it's a page, create a simple one on the fly
            $class = $this->module->getTilesClass();
            $tile = new $class($this->module);
            $tile->setName('generic')
                 ->setPath($path)
                 ->freeze();
         }

         // render the tile
         $tile->render();
      }
   }
}
?>
