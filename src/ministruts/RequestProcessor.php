<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\CObject;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\net\http\HttpResponse;
use rosasurfer\core\facade\Form;


/**
 * RequestProcessor
 */
class RequestProcessor extends CObject {


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
        // Session starten oder fortsetzen
        $this->processSession($request);

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

        // ActionForm validieren (wenn entsprechend konfiguriert)
        if (!$this->processActionFormValidate($request, $response, $mapping, $form))
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
     * Session handling according to the configuration. If a valid session id was transmitted the session is restarted.
     *
     * @param  Request $request
     */
    protected function processSession(Request $request) {
        $this->restoreActionForm($request);
        $this->restoreActionMessages($request);
    }


    /**
     * Waehlt das zu benutzende ActionMapping&#46;  Kann kein Mapping gefunden werden, wird eine Fehlermeldung erzeugt und
     * NULL zurueckgegeben.
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

        $mappingPath = '/'.substr($requestPath, strlen($moduleUri));
        // /
        // /controller/action/

        // Mapping suchen und im Request speichern
        if (($mapping=$this->module->findMapping($mappingPath)) || ($mapping=$this->module->getDefaultMapping())) {
            $request->setAttribute(ACTION_MAPPING_KEY, $mapping);
            return $mapping;
        }

        // kein Mapping gefunden
        $response->setStatus(HttpResponse::SC_NOT_FOUND);

        if (isset($this->options['status-404']) && $this->options['status-404']=='pass-through')
            return null;

        header('HTTP/1.1 404 Not Found', true, HttpResponse::SC_NOT_FOUND);

        // konfiguriertes 404-Layout suchen
        if ($forward = $this->module->findForward((string) HttpResponse::SC_NOT_FOUND)) {
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
     * Wenn fuer das ActionMapping Methodenbeschraenkungen definiert sind, sicherstellen, dass der Request diese
     * Beschraenkungen erfuellt&#46;  Gibt TRUE zurueck, wenn die Verarbeitung fortgesetzt und der Zugriff gewaehrt werden
     * soll werden soll, oder FALSE, wenn der Zugriff nicht gewaehrt wird und der Request beendet wurde.
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
        $response->setStatus(HttpResponse::SC_METHOD_NOT_ALLOWED);

        if (isset($this->options['status-405']) && $this->options['status-405']=='pass-through')
            return false;

        header('HTTP/1.1 405 Method Not Allowed', true, HttpResponse::SC_METHOD_NOT_ALLOWED);

        // konfiguriertes 405-Layout suchen
        if ($forward = $this->module->findForward((string) HttpResponse::SC_METHOD_NOT_ALLOWED)) {
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
     * Wenn die Action Zugriffsbeschraenkungen hat, sicherstellen, dass der User Inhaber der angegebenen Rollen ist&#46;
     * Gibt TRUE zurueck, wenn die Verarbeitung fortgesetzt und der Zugriff gewaehrt werden soll, oder FALSE, wenn der
     * Zugriff nicht gewaehrt wird und der Request beendet wurde.
     *
     * @param  Request       $request
     * @param  Response      $response
     * @param  ActionMapping $mapping
     *
     * @return bool
     */
    protected function processRoles(Request $request, Response $response, ActionMapping $mapping) {
        if (empty($mapping->getRoles()))
            return true;

        /** @var ActionForward|string|null $forward */
        $forward = $this->module->getRoleProcessor()->processRoles($request, $mapping);
        if (!isset($forward))
            return true;

        // if a string identifier was returned resolve the named instance
        if (is_string($forward))
            $forward = $mapping->getForward($forward);

        $this->processActionForward($request, $response, $forward);
        return false;
    }


    /**
     * Return the {@link ActionForm} instance assigned to the passed {@link ActionMapping}. If no ActionForm was configured
     * an {@link EmptyActionForm} is instantiated and assigned.
     *
     * @param  Request       $request
     * @param  ActionMapping $mapping
     *
     * @return ActionForm
     */
    protected function processActionFormCreate(Request $request, ActionMapping $mapping) {
        /** @var ActionForm $form */
        $form = null;

        if ($formClass = $mapping->getFormClassName()) {
            $form = new $formClass($request);

            // if a DispatchAction is used read the action key
            $actionClass = $mapping->getActionClassName();
            if (is_subclass_of($actionClass, DispatchAction::class))
                $form->initActionKey($request);

            // populate the form
            $form->populate($request);
        }
        else {
            // create an empty default instance
            $form = new EmptyActionForm($request);
        }

        // store the ActionForm in the request
        $request->setAttribute(ACTION_FORM_KEY, $form);

        return $form;
    }


    /**
     * Validiert die ActionForm, wenn entprechend konfiguriert&#46;  Ist fuer das ActionMapping ein expliziter Forward
     * konfiguriert, wird nach der Validierung auf diesen Forward weitergeleitet&#46;  Ist kein expliziter Forward definiert,
     * wird auf die konfigurierte "success" oder "error"-Resource weitergeleitet&#46;  Gibt TRUE zurueck, wenn die
     * Verarbeitung fortgesetzt werden soll, oder FALSE, wenn auf eine andere Resource weitergeleitet und der Request bereits
     * beendet wurde.
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

            if (!$forward) throw new RuntimeException(
                '<mapping path="'.$mapping->getPath().'" form-validate-first="true": '
               .'ActionForward "'.$key.'" not found (module validation error, should never happen)'
            );
        }
        $this->processActionForward($request, $response, $forward);
        return false;
    }


    /**
     * Verarbeitet einen direkt im ActionMapping angegebenen ActionForward (wenn angegeben)&#46;  Gibt TRUE zurueck, wenn
     * die Verarbeitung fortgesetzt werden soll, oder FALSE, wenn der Request bereits beendet wurde.
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
     * @param  ActionForm    $form - the ActionForm instance assigned to the mapping
     *
     * @return Action
     */
    protected function processActionCreate(ActionMapping $mapping, ActionForm $form) {
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
        $forward = $ex = null;

        // Alles kapseln, damit Postprocessing-Hook auch nach Auftreten einer Exception aufgerufen
        // werden kann (z.B. Transaction-Rollback o.ae.)
        try {
            // allgemeinen Preprocessing-Hook aufrufen
            $forward = $action->executeBefore($request, $response);

            // Action nur ausfuehren, wenn executeBefore() nicht schon Abbruch signalisiert hat
            if ($forward === null) {
                // TODO: implement dispatching for DispatchActions; as of now it must be done manually in execute()
                $forward = $action->execute($request, $response);
            }
        }
        catch (\Throwable $ex) {}           // auftretende Exceptions zwischenspeichern
        catch (\Exception $ex) {}

        // falls statt eines ActionForwards ein String-Identifier zurueckgegeben wurde, diesen aufloesen
        if (is_string($forward))
            $forward = $action->getMapping()->getForward($forward);

        // allgemeinen Postprocessing-Hook aufrufen
        $forward = $action->executeAfter($request, $response, $forward);

        // jetzt aufgetretene Exception weiterreichen
        if ($ex) throw $ex;

        return $forward;
    }


    /**
     * Verarbeitet den von der Action zurueckgegebenen ActionForward&#46;  Leitet auf die Resource weiter, die der
     * ActionForward bezeichnet.
     *
     * @param  Request       $request
     * @param  Response      $response
     * @param  ActionForward $forward
     */
    protected function processActionForward(Request $request, Response $response, ActionForward $forward) {
        $module = $this->module;

        if ($forward->isRedirect()) {
            $this->storeActionForm($request);                       // copy ActionForm and ActionMessages to the session
            $this->storeActionMessages($request);

            $path = $forward->getPath();

            if (isset(parse_url($path)['host'])) {                  // an external URI
                $url = $path;
            }
            else if ($path[0] == '/') {                             // an application-relative URI
                $appUri = $request->getApplicationBaseUri();
                $url = $appUri.ltrim($path, '/');
            }
            else {                                                  // a module-relative URI
                $moduleUri = $request->getApplicationBaseUri().$module->getPrefix();
                $url = $moduleUri.$path;
            }
            $response->redirect($url, $forward->getRedirectType());
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


    /**
     * Copy the {@link ActionForm} configured for the current {@link ActionMapping} from the {@link Request} to the
     * {@link HttpSession}. On the next HTML request the form will be made available via the facade {@link Form::old()} as
     * form of the previous request. An {@link EmptyActionForm} can't be configured for a mapping, and will not be copied.
     *
     * @param  Request $request
     */
    protected function storeActionForm(Request $request) {
        $form = $request->getAttribute(ACTION_FORM_KEY);
        if (!$form || $form instanceof EmptyActionForm)
            return;
        $request->getSession()->setAttribute(ACTION_FORM_KEY.'.old', $form);
    }


    /**
     * Move an old ActionForm stored in the session to the current {@link Request}. The form will be available via the
     * facade {@link Form::old()} as form of the previous request.
     *
     * @param  Request $request
     */
    protected function restoreActionForm(Request $request) {
        if ($request->hasSessionId() && $request->getSession()) {
            $oldFormKey = ACTION_FORM_KEY.'.old';

            if (isset($_SESSION[$oldFormKey])) {
                $form = $_SESSION[$oldFormKey];
                unset($_SESSION[$oldFormKey]);

                $request->setAttribute($oldFormKey, $form);
            }
        }
    }


    /**
     * Copy all ActionMessages (including ActionErrors) from the {@link Request} to the {@link HttpSession}.
     * On the next HTML request the messages are restored and moved back to the new request.
     *
     * @param  Request $request
     */
    protected function storeActionMessages(Request $request) {
        $errors = $request->getActionErrors();
        if ($errors && $request->getSession()) {
            if (isset($_SESSION[ACTION_ERRORS_KEY]))
                $errors = \array_merge($_SESSION[ACTION_ERRORS_KEY], $errors);
            $_SESSION[ACTION_ERRORS_KEY] = $errors;
        }

        $messages = $request->getActionMessages();
        if ($messages && $request->getSession()) {
            if (isset($_SESSION[ACTION_MESSAGES_KEY]))
                $messages = \array_merge($_SESSION[ACTION_MESSAGES_KEY], $messages);
            $_SESSION[ACTION_MESSAGES_KEY] = $messages;
        }
    }


    /**
     * Move all ActionMessages (including ActionErrors) stored in the session to the current {@link Request}.
     * Found ActionErrors (from the previous request) are converted to ActionMessages of the current request.
     *
     * @param  Request $request
     */
    protected function restoreActionMessages(Request $request) {
        if ($request->hasSessionId() && $request->getSession()) {
            $messages = $errors = [];

            if (isset($_SESSION[ACTION_MESSAGES_KEY])) {
                $messages = $_SESSION[ACTION_MESSAGES_KEY];
                unset($_SESSION[ACTION_MESSAGES_KEY]);
            }
            if (isset($_SESSION[ACTION_ERRORS_KEY])) {
                $errors = $_SESSION[ACTION_ERRORS_KEY];
                unset($_SESSION[ACTION_ERRORS_KEY]);
            }
            $request->setAttribute(ACTION_MESSAGES_KEY, \array_merge($messages, $errors));
        }
    }
}
