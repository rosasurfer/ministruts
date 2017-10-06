<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;
use rosasurfer\exception\RuntimeException;
use rosasurfer\log\Logger;
use rosasurfer\net\http\HttpResponse;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;


/**
 * RequestProcessor
 */
class RequestProcessor extends Object {


    /** @var bool */
    private static $logDebug;

    /** @var bool */
    private static $logInfo;

    /** @var Module - the Module the instance belongs to */
    protected $module;

    /** @var array - processing runtime options */
    protected $options;


    /**
     * Create a new RequestProcessor.
     *
     * @param  Module $module  - the Module the instance belongs to
     * @param  array  $options - processing runtime options
     */
    public function __construct(Module $module, array $options) {
        $loglevel       = Logger::getLogLevel(__CLASS__);
        self::$logDebug = ($loglevel <= L_DEBUG );
        self::$logInfo  = ($loglevel <= L_INFO  );

        $this->module  = $module;
        $this->options = $options;
    }


    /**
     * Verarbeitet den uebergebenen Request und gibt entweder den entsprechenden Content aus oder
     * leitet auf eine andere Resource um.
     *
     * @param  Request  $request
     * @param  Response $response
     */
    public function process(Request $request, Response $response) {
        // ggf. Session starten oder fortsetzen
        $this->processSession($request);


        // ActionMessages aus der Session loeschen
        $this->processCachedActionMessages($request);


        // Mapping fuer den Request ermitteln: wird kein Mapping gefunden, generiert die Methode einen 404-Fehler
        $mapping = $this->processMapping($request, $response);
        if (!$mapping) return;


        // Methodenbeschraenkungen des Mappings pruefen: wird der Zugriff verweigert, generiert die Methode einen 405-Fehler
        if (!$this->processMethod($request, $response, $mapping))
            return;


        // benoetigte Rollen ueberpruefen
        if (!$this->processRoles($request, $response, $mapping))
            return;


        // ActionForm vorbereiten
        $form = $this->processActionFormCreate($request, $mapping);


        // ActionForm validieren
        if ($form && !$this->processActionFormValidate($request, $response, $mapping, $form))
            return;


        // falls statt einer Action ein direkter Forward konfiguriert wurde, diesen verarbeiten
        if (!$this->processMappingForward($request, $response, $mapping))
            return;


        // Action erzeugen (Form und Mapping werden schon hier uebergeben, damit User-Code einfacher wird)
        $action = $this->processActionCreate($mapping, $form);


        // Action aufrufen
        $forward = $this->processActionExecute($request, $response, $action);
        if (!$forward) return;


        // den zurueckgegebenen ActionForward verarbeiten
        $this->processActionForward($request, $response, $forward);
    }


    /**
     * Session handling sccording to the configuration.
     *
     * @param  Request $request
     */
    protected function processSession(Request $request) {
        /*
        // former behaviour: If a session id was transmitted the session was started automatically.
        if (!$request->isSession() && $request->hasSessionId()) {
            $request->getSession();
        }
        */
    }


    /**
     * Verschiebt ActionMessages, die in der HttpSession gespeichert sind, zurueck in den aktuellen
     * Request. Wird verwendet, um ActionMessages ueber einen Redirect hinweg uebertragen zu koennen.
     *
     * @param  Request $request
     *
     * @see    Request::setActionMessage()
     * @see    RequestProcessor::cacheActionMessages()
     */
    protected function processCachedActionMessages(Request $request) {
        if ($request->hasSessionId() && $request->getSession()) {
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
     * Kopiert alle vorhandenen ActionMessages in die HttpSession. Beim naechsten Request werden diese
     * Messages automatisch zurueck in den Request verschoben und stehen wieder zur Verfuegung.
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

        $request->getSession();

        foreach ($errors as $key => $value) {
            $_SESSION[ACTION_ERRORS_KEY][$key] = $value;
        }

        // TODO: ActionMessages verarbeiten
    }


    /**
     * Waehlt das zu benutzende ActionMapping. Kann kein Mapping gefunden werden, wird eine Fehlermeldung
     * erzeugt und NULL zurueckgegeben.
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return ActionMapping|null - ActionMapping oder NULL
     */
    protected function processMapping(Request $request, Response $response) {
        // Pfad fuer die Mappingauswahl ermitteln ...
        $requestPath = '/'.trim(preg_replace('|/{2,}|', '/', $request->getPath()), '/').'/';   // normalize request path
        if ($requestPath=='//') $requestPath = '/';
        // /
        // /controller/action/
        // /module/
        // /module/controller/action/

        $moduleUri = $request->getApplicationBaseUri().$this->module->getPrefix();
        // /
        // /app/
        // /module/
        // /app/module/

        $mappingPath = '/'.subStr($requestPath, strLen($moduleUri));
        // /
        // /controller/action/

        self::$logDebug && Logger::log('Path used for mapping selection: "'.$mappingPath.'"', L_DEBUG);

        // Mapping suchen und im Request speichern
        if (($mapping=$this->module->findMapping($mappingPath)) || ($mapping=$this->module->getDefaultMapping())) {
            $request->setAttribute(ACTION_MAPPING_KEY, $mapping);
            return $mapping;
        }

        // kein Mapping gefunden
        self::$logInfo && Logger::log('Could not find a mapping for path: '.$mappingPath, L_INFO);
        $response->setStatus(HttpResponse::SC_NOT_FOUND);

        if (isSet($this->options['status-404']) && $this->options['status-404']=='pass-through')
            return null;

        header('HTTP/1.1 404 Not Found', true, HttpResponse::SC_NOT_FOUND);
            
        // konfiguriertes 404-Layout suchen
        if ($forward=$this->module->findForward((string) HttpResponse::SC_NOT_FOUND)) {
            // falls vorhanden, einbinden...
            $this->processActionForward($request, $response, $forward);
        }
        else {
            // ...andererseits einfache Fehlermeldung ausgeben
            echo <<<PROCESS_MAPPING_ERROR_SC_404
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
<hr>
<address>...lamented the MiniStruts.</address>
</body></html>
PROCESS_MAPPING_ERROR_SC_404;
        }
        return null;
    }


    /**
     * Wenn fuer das ActionMapping Methodenbeschraenkungen definiert sind, sicherstellen, dass der Request
     * diese Beschraenkungen erfuellt. Gibt TRUE zurueck, wenn die Verarbeitung fortgesetzt und der Zugriff
     * gewaehrt werden soll werden soll, oder FALSE, wenn der Zugriff nicht gewaehrt wird und der Request
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

        // Beschraenkung nicht erfuellt
        self::$logDebug && Logger::log('HTTP method "'.$request->getMethod().'" is not supported by ActionMapping, denying access', L_DEBUG);
        $response->setStatus(HttpResponse::SC_METHOD_NOT_ALLOWED);

        if (isSet($this->options['status-405']) && $this->options['status-405']=='pass-through')
            return false;
        
        header('HTTP/1.1 405 Method Not Allowed', true, HttpResponse::SC_METHOD_NOT_ALLOWED);
            
        // konfiguriertes 405-Layout suchen
        if ($forward=$this->module->findForward((string) HttpResponse::SC_METHOD_NOT_ALLOWED)) {
            // falls vorhanden, einbinden...
            $this->processActionForward($request, $response, $forward);
        }
        else {
            // ...andererseits einfache Fehlermeldung ausgeben
            echo <<<PROCESS_METHOD_ERROR_SC_405
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>405 Method Not Allowed</title>
</head><body>
<h1>Method Not Allowed</h1>
<p>The used HTTP method is not allowed for the requested URL.</p>
<hr>
<address>...lamented the MiniStruts.</address>
</body></html>
PROCESS_METHOD_ERROR_SC_405;
        }
        return false;
    }


    /**
     * Wenn die Action Zugriffsbeschraenkungen hat, sicherstellen, dass der User Inhaber der angegebenen
     * Rollen ist.  Gibt TRUE zurueck, wenn die Verarbeitung fortgesetzt und der Zugriff gewaehrt werden
     * soll, oder FALSE, wenn der Zugriff nicht gewaehrt wird und der Request beendet wurde.
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
     * Erzeugt die ActionForm des angegebenen Mappings bzw. gibt sie zurueck. Ist keine ActionForm
     * konfiguriert, wird NULL zurueckgegeben.
     *
     * @param  Request       $request
     * @param  ActionMapping $mapping
     *
     * @return ActionForm|null
     */
    protected function processActionFormCreate(Request $request, ActionMapping $mapping) {
        $className = $mapping->getFormClassName();
        if (!$className)
            return null;

        $form = null;

        // bei gesetztem Session-Scope ActionForm zuerst in der Session suchen ...
        if ($mapping->isSessionScope())
            $form = $request->getSession()->getAttribute($className);       // implicitely start a session

        // ... ansonsten neue Instanz erzeugen
        if (!$form)
            $form = new $className($request);


        // Instanz im Request ...
        $request->setAttribute(ACTION_FORM_KEY, $form);

        // ... und ggf. auch in der Session speichern
        if ($mapping->isSessionScope())
            $request->getSession()->setAttribute($className, $form);        // use started session

        return $form;
    }


    /**
     * Validiert die ActionForm, wenn entprechend konfiguriert.  Ist fuer das ActionMapping ein expliziter
     * Forward konfiguriert, wird nach der Validierung auf diesen Forward weitergeleitet. Ist kein
     * expliziter Forward definiert, wird auf die konfigurierte "success" oder "error"-Resource
     * weitergeleitet.  Gibt TRUE zurueck, wenn die Verarbeitung fortgesetzt werden soll, oder FALSE,
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
        if (!$mapping->isFormValidateFirst())
            return true;

        $success = $form->validate();

        if ($mapping->getActionClassName())
            return true;

        $forward = $mapping->getForward();
        if (!$forward) {
            $key     = $success ? ActionForward::VALIDATION_SUCCESS_KEY : ActionForward::VALIDATION_ERROR_KEY;
            $forward = $mapping->findForward($key);
        }
        if (!$forward) throw new RuntimeException('<mapping path="'.$mapping->getPath().'" form-validate-first="true": ActionForward not found (module validation error?)');

        $this->processActionForward($request, $response, $forward);
        return false;
    }


    /**
     * Verarbeitet einen direkt im ActionMapping angegebenen ActionForward (wenn angegeben). Gibt TRUE
     * zurueck, wenn die Verarbeitung fortgesetzt werden soll, oder FALSE, wenn der Request bereits
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
     * Erzeugt und gibt die Action zurueck, die fuer das angegebene Mapping konfiguriert wurde.
     *
     * @param  ActionMapping $mapping
     * @param  ActionForm    $form [optional] - ActionForm, die konfiguriert wurde oder NULL
     *
     * @return Action
     */
    protected function processActionCreate(ActionMapping $mapping, ActionForm $form = null) {
        $className = $mapping->getActionClassName();

        return new $className($mapping, $form);
    }


    /**
     * Uebergibt den Request zur Bearbeitung an die konfigurierte Action und gibt den von ihr
     * zurueckgegebenen ActionForward zurueck.
     *
     * @param  Request  $request
     * @param  Response $response
     * @param  Action   $action
     *
     * @return ActionForward|null
     */
    protected function processActionExecute(Request $request, Response $response, Action $action) {
        $forward   = null;
        $throwable = null;

        // Alles kapseln, damit Postprocessing-Hook auch nach Auftreten einer Exception aufgerufen
        // werden kann (z.B. Transaction-Rollback o.ae.)
        try {
            // allgemeinen Preprocessing-Hook aufrufen
            $forward = $action->executeBefore($request, $response);

            // Action nur ausfuehren, wenn executeBefore() nicht schon Abbruch signalisiert hat
            if ($forward === null)
                $forward = $action->execute($request, $response);
        }
        catch (\Exception $ex) {
            $throwable = $ex;    // evt. aufgetretene Exception zwischenspeichern
        }

        // falls statt eines ActionForwards ein String-Identifier zurueckgegeben wurde, diesen aufloesen
        if (is_string($forward)) {
            if ($forwardInstance = $action->getMapping()->findForward($forward)) {
                $forward = $forwardInstance;
            }
            else {
                $throwable = new RuntimeException('No ActionForward found for name "'.$forward.'"');
                $forward = null;
            }
        }

        // allgemeinen Postprocessing-Hook aufrufen
        $forward = $action->executeAfter($request, $response, $forward);

        // jetzt evt. aufgetretene Exception weiterreichen
        if ($throwable)
            throw $throwable;

        return $forward;
    }


    /**
     * Verarbeitet den von der Action zurueckgegebenen ActionForward.  Leitet auf die Resource weiter,
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

            if (isSet(parse_url($path)['host'])) {               // check for external URI
                $url = $path;
            }
            else {
                $moduleUri = $request->getApplicationBaseUri().$module->getPrefix();
                $url = $moduleUri.lTrim($path, '/');
            }
            $response->redirect($url);
        }
        else {
            $path = $forward->getPath();
            $tile = $module->findTile($path);

            if (!$tile) {
                // $path ist ein Dateiname, generische Tile erzeugen
                $tilesClass = $module->getTilesClass();
                /** @var Tile $tile */
                $tile = new $tilesClass($this->module);
                $tile->setName(Tile::GENERIC_NAME)
                     ->setFileName($path)
                     ->freeze();
            }
            $tile->render();
        }
    }
}
