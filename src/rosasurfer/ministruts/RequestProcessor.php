<?php
use rosasurfer\core\Object;
use rosasurfer\ministruts\Request;

use function rosasurfer\strRightFrom;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;

use const rosasurfer\ministruts\ACTION_ERRORS_KEY;
use const rosasurfer\ministruts\ACTION_FORM_KEY;
use const rosasurfer\ministruts\ACTION_MAPPING_KEY;
use const rosasurfer\ministruts\ACTION_MESSAGES_KEY;


/**
 * RequestProcessor
 */
class RequestProcessor extends Object {


   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice;


   // Modul, zu dem wir gehören
   protected /*Module*/ $module;


   /**
    * Erzeugt einen neuen RequestProcessor.
    *
    * @param  Module $module - Modul, dem dieser RequestProcessor zugeordnet ist
    */
   public function __construct(Module $module) {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      $this->module = $module;
   }


   /**
    * Verarbeitet den übergebenen Request und gibt entweder den entsprechenden Content aus oder
    * leitet auf eine andere Resource um.
    *
    * @param  Request  $request
    * @param  Response $response
    */
   final public function process(Request $request, Response $response) {
      // ggf. Session starten oder fortsetzen
      $this->processSession($request, $response);


      // ggf. Locale setzen
      $this->processLocale($request, $response);


      // ActionMessages aus der Session löschen
      $this->processCachedActionMessages($request, $response);


      // Mapping für den Request ermitteln: wird kein Mapping gefunden, generiert die Methode einen 404-Fehler
      $mapping = $this->processMapping($request, $response);
      if (!$mapping)
         return;


      // Methodenbeschränkungen des Mappings prüfen: wird der Zugriff verweigert, generiert die Methode einen 405-Fehler
      if (!$this->processMethod($request, $response, $mapping))
         return;


      // benötigte Rollen überprüfen
      if (!$this->processRoles($request, $response, $mapping))
         return;


      // ActionForm vorbereiten
      $form = $this->processActionFormCreate($request, $response, $mapping);


      // ActionForm validieren
      if ($form && !$this->processActionFormValidate($request, $response, $mapping, $form))
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
    * Wurde mit dem Request eine Session-ID übertragen, wird die entsprechende HttpSession fortgesetzt.
    *
    * @param  Request  $request
    * @param  Response $response
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
    * @param  Request  $request
    * @param  Response $response
    */
   protected function processLocale(Request $request, Response $response) {
   }


   /**
    * Verschiebt ActionMessages, die in der HttpSession gespeichert sind, zurück in den aktuellen
    * Request. Wird verwendet, um ActionMessages über einen Redirect hinweg übertragen zu können.
    *
    * @param  Request  $request
    * @param  Response $response
    *
    * @see    Request::setActionMessage()
    * @see    RequestProcessor::cacheActionMessages()
    */
   protected function processCachedActionMessages(Request $request, Response $response) {
      if ($request->isSession()) {
         if (isSet($_SESSION[ACTION_MESSAGES_KEY])) {
            $messages = $_SESSION[ACTION_MESSAGES_KEY];
            $request->setAttribute(ACTION_MESSAGES_KEY, $messages);
            unset($_SESSION[ACTION_MESSAGES_KEY]);
         }

         if (isSet($_SESSION[ACTION_ERRORS_KEY])) {
            $errors = $_SESSION[ACTION_ERRORS_KEY];
            $request->setAttribute(ACTION_ERRORS_KEY, $errors);
            unset($_SESSION[ACTION_ERRORS_KEY]);
         }
         // TODO: ActionError -> ActionMessage
      }
   }


   /**
    * Kopiert alle vorhandenen ActionMessages in die HttpSession. Beim nächsten Request werden diese
    * Messages automatisch zurück in den Request verschoben und stehen wieder zur Verfügung.
    *
    * @param  Request $request
    *
    * @see    Request::setActionMessage()
    * @see    RequestProcessor::processCachedActionMessages()
    */
   protected function cacheActionMessages(Request $request) {
      $errors = $request->getActionErrors();
      if (sizeOf($errors) == 0)
         return;

      $session = $request->getSession();

      foreach ($errors as $key => $value) {
         $_SESSION[ACTION_ERRORS_KEY][$key] = $value;
      }

      // TODO: ActionMessages verarbeiten
   }


   /**
    * Wählt das zu benutzende ActionMapping. Kann kein Mapping gefunden werden, wird eine Fehlermeldung
    * erzeugt und NULL zurückgegeben.
    *
    * @param  Request  $request
    * @param  Response $response
    *
    * @return ActionMapping - ActionMapping oder NULL
    */
   protected function processMapping(Request $request, Response $response) {
      // Pfad für die Mappingauswahl ermitteln ...
      $requestPath  = $request->getPath();
      $baseUri      = $request->getApplicationBaseUri();
      $modulePrefix = $this->module->getPrefix();

      $path = strRightFrom($requestPath, $baseUri.$modulePrefix);
      !strStartsWith($path, '/') && $path='/'.$path;

      self::$logDebug && Logger::log('Path used for mapping selection: '.$path, null, L_DEBUG, __CLASS__);

      // Mapping suchen und im Request speichern
      if (($mapping=$this->module->findMapping($path)) || ($mapping=$this->module->getDefaultMapping())) {
         $request->setAttribute(ACTION_MAPPING_KEY, $mapping);
         return $mapping;
      }

      // kein Mapping gefunden
      self::$logInfo && Logger::log('Could not find a mapping for path: '.$path, null, L_INFO, __CLASS__);


      // TODO: Status-Code 404 im HttpResponse setzen

      // falls vorhanden, ein 404-Layout einbinden
      if ($forward = $this->module->findForward((string) HttpResponse ::SC_NOT_FOUND)) {
         $this->processActionForward($request, $response, $forward);
         return null;
      }

      // einfache Fehlermeldung ausgeben
      echo <<<EOT_404
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
<title>404 Not Found</title>
</head>
<body>
<h1>Not Found</h1>
<p>The requested URL $requestPath was not found on this server.</p>
<hr>
</body>
</html>
EOT_404;
      return null;
   }


   /**
    * Wenn für das ActionMapping Methodenbeschränkungen definiert sind, sicherstellen, daß der Request
    * diese Beschränkungen erfüllt. Gibt TRUE zurück, wenn die Verarbeitung fortgesetzt und der Zugriff
    * gewährt werden soll werden soll, oder FALSE, wenn der Zugriff nicht gewährt wird und der Request
    * beendet wurde.
    *
    * @param  Request       $request
    * @param  Response      $response
    * @param  ActionMapping $mapping
    *
    * @return bool
    */
   protected function processMethod(Request $request, Response $response, ActionMapping $mapping) {
      if ($mapping->isSupportedMethod($request->getMethod()))
         return true;

      // Beschränkung nicht erfüllt
      self::$logDebug && Logger::log('HTTP method "'.$request->getMethod().'" is not supported by ActionMapping, denying access', null, L_DEBUG, __CLASS__);

      // TODO: Status-Code 405 im HttpResponse setzen

      // falls vorhanden, ein 405-Layout einbinden
      if ($forward = $this->module->findForward((string) HttpResponse ::SC_METHOD_NOT_ALLOWED)) {
         $this->processActionForward($request, $response, $forward);
         return false;
      }

      // einfache Fehlermeldung ausgeben
      $requestPath = $request->getPath();
      echo <<<EOT_405
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
<title>405 Method Not Allowed</title>
</head>
<body>
<h1>Method Not Allowed</h1>
<p>The used HTTP method is not allowed for the URL $requestPath</p>
<hr>
</body>
</html>
EOT_405;
      return false;
   }


   /**
    * Wenn die Action Zugriffsbeschränkungen hat, sicherstellen, daß der User Inhaber der angegebenen
    * Rollen ist.  Gibt TRUE zurück, wenn die Verarbeitung fortgesetzt und der Zugriff gewährt werden
    * soll, oder FALSE, wenn der Zugriff nicht gewährt wird und der Request beendet wurde.
    *
    * @param  Request       $request
    * @param  Response      $response
    * @param  ActionMapping $mapping
    *
    * @return bool
    */
   protected function processRoles(Request $request, Response $response, ActionMapping $mapping) {
      if ($mapping->getRoles() === null)
         return true;

      $forward = $this->module->getRoleProcessor()->processRoles($request, $mapping);
      if (!$forward)
         return true;

      $this->processActionForward($request, $response, $forward);
      return false;
   }


   /**
    * Erzeugt die ActionForm des angegebenen Mappings bzw. gibt sie zurück. Ist keine ActionForm
    * konfiguriert, wird NULL zurückgegeben.
    *
    * @param  Request       $request
    * @param  Response      $response
    * @param  ActionMapping $mapping
    *
    * @return ActionForm
    */
   protected function processActionFormCreate(Request $request, Response $response, ActionMapping $mapping) {
      $className = $mapping->getFormClassName();
      if (!$className)
         return null;

      $form = null;

      // bei gesetztem Session-Scope ActionForm zuerst in der Session suchen ...
      if ($mapping->isSessionScope())
         $form = $request->getSession()->getAttribute($className);

      // ... ansonsten neue Instanz erzeugen
      if (!$form)
         $form = new $className($request);


      // Instanz im Request ...
      $request->setAttribute(ACTION_FORM_KEY, $form);

      // ... und ggf. auch in der Session speichern
      if ($mapping->isSessionScope())
         $request->getSession()->setAttribute($className, $form);

      return $form;
   }


   /**
    * Validiert die ActionForm, wenn entprechend konfiguriert.  Ist für das ActionMapping ein direkter
    * Forward konfiguriert, wird nach der Validierung auf diesen Forward weitergeleitet. Ist kein
    * allgemeiner Forward definiert, wird auf die konfigurierte "success" oder "error"-Resource
    * weitergeleitet.  Gibt TRUE zurück, wenn die Verarbeitung fortgesetzt werden soll, oder FALSE,
    * wenn auf eine andere Resource weitergeleitet und der Request bereits beendet wurde.
    *
    * @param  Request       $request
    * @param  Response      $response
    * @param  ActionMapping $mapping
    * @param  ActionForm    $form
    *
    * @return bool
    */
   protected function processActionFormValidate(Request $request, Response $response, ActionMapping $mapping, ActionForm $form) {
      if (!$mapping->isValidate())
         return true;

      $success = $form->validate();

      if ($mapping->getActionClassName())
         return true;

      $forward = $mapping->getForward();
      if (!$forward) {
         $key = $success ? ActionForward ::VALIDATION_SUCCESS_KEY : ActionForward ::VALIDATION_ERROR_KEY;
         $forward = $mapping->findForward($key);
      }

      $this->processActionForward($request, $response, $forward);
      return false;
   }


   /**
    * Verarbeitet einen direkt im ActionMapping angegebenen ActionForward (wenn angegeben). Gibt TRUE
    * zurück, wenn die Verarbeitung fortgesetzt werden soll, oder FALSE, wenn der Request bereits
    * beendet wurde.
    *
    * @param  Request       $request
    * @param  Response      $response
    * @param  ActionMapping $mapping
    *
    * @return bool
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
    * @param  Request       $request
    * @param  Response      $response
    * @param  ActionMapping $mapping
    * @param  ActionForm    $form     - ActionForm, die konfiguriert wurde oder NULL
    *
    * @return Action
    */
   protected function processActionCreate(Request $request, Response $response, ActionMapping $mapping, ActionForm $form=null) {
      $className = $mapping->getActionClassName();

      return new $className($mapping, $form);
   }


   /**
    * Übergibt den Request zur Bearbeitung an die konfigurierte Action und gibt den von ihr
    * zurückgegebenen ActionForward zurück.
    *
    * @param  Request  $request
    * @param  Response $response
    * @param  Action   $action
    *
    * @return ActionForward
    */
   protected function processActionExecute(Request $request, Response $response, Action $action) {
      $forward   = null;
      $throwable = null;

      // Alles kapseln, damit Postprocessing-Hook auch nach Auftreten einer Exception aufgerufen
      // werden kann (z.B. Transaction-Rollback o.ä.)
      try {
         // allgemeinen Preprocessing-Hook aufrufen
         $forward = $action->executeBefore($request, $response);

         // Action nur ausführen, wenn executeBefore() nicht schon Abbruch signalisiert hat
         if ($forward === null)
            $forward = $action->execute($request, $response);

         if ($forward === null)
            self::$logInfo && Logger::log('ActionForward of NULL returned from Action::execute()', null, L_INFO, __CLASS__);
      }
      catch (\Exception $ex) {
         $throwable = $ex;    // evt. aufgetretene Exception zwischenspeichern
      }

      // falls statt eines ActionForwards ein String-Identifier zurückgegeben wurde, diesen auflösen
      if (is_string($forward))
         $forward = $action->getMapping()->findForward($forward);


      // allgemeinen Postprocessing-Hook aufrufen
      $forward = $action->executeAfter($request, $response, $forward);


      // jetzt evt. aufgetretene Exception weiterreichen
      if ($throwable)
         throw $throwable;

      return $forward;
   }


   /**
    * Verarbeitet den von der Action zurückgegebenen ActionForward.  Leitet auf die Resource weiter,
    * die der ActionForward bezeichnet.
    *
    * @param  Request       $request
    * @param  Response      $response
    * @param  ActionForward $forward
    */
   protected function processActionForward(Request $request, Response $response, ActionForward $forward) {
      $module = $this->module;

      if ($forward->isRedirect()) {
         $this->cacheActionMessages($request);

         $path = $forward->getPath();

         // prüfen, ob der Forward eine URL "http://www..." oder einen Path "/target?..." enthält
         if (strStartsWith($path, 'http')) {
            $url = $path;
         }
         else {
            $baseUri = $request->getApplicationBaseUri();
            $url     = $baseUri.$module->getPrefix();
            !strEndsWith($url, '/') && $url.='/';
            $url    .= lTrim($path, '/');
         }

         // TODO: QueryString kodieren
         HeaderUtils::redirect($url);
      }
      else {
         $path = $forward->getPath();
         $tile = $module->findTile($path);

         if (!$tile) {
            // $path ist ein Dateiname, einfache Tile erzeugen, damit render() existiert
            $class = $module->getTilesClass();
            $tile = new $class($this->module);
            $tile->setName('generic')
                 ->setPath($path)
                 ->setLabel($forward->getLabel())
                 ->freeze();
         }

         $tile->render();
      }
   }
}


!defined('rosasurfer\ministruts\MODULE_KEY') && include(__DIR__.'/definitions.php');