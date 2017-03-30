<?php
namespace rosasurfer\ministruts;

use \Exception;

use rosasurfer\core\Object;
use rosasurfer\exception\RuntimeException;
use rosasurfer\log\Logger;

use rosasurfer\net\http\HeaderUtils;
use rosasurfer\net\http\HttpResponse;


use function rosasurfer\strEndsWith;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;


/**
 * RequestProcessor
 */
class RequestProcessor extends Object {


    /** @var bool */
    private static $logDebug;

    /** @var bool */
    private static $logInfo;

    /** @var bool */
    private static $logNotice;

    /** @var Module - Modul, zu dem wir gehoeren */
    protected $module;


    /**
     * Erzeugt einen neuen RequestProcessor.
     *
     * @param  Module $module - Module, dem dieser RequestProcessor zugeordnet ist
     */
    public function __construct(Module $module) {
        $loglevel        = Logger::getLogLevel(__CLASS__);
        self::$logDebug  = ($loglevel <= L_DEBUG );
        self::$logInfo   = ($loglevel <= L_INFO  );
        self::$logNotice = ($loglevel <= L_NOTICE);

        $this->module = $module;
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
        $mapping = $this->processMapping($request);
        if (!$mapping)
            return;


        // Methodenbeschraenkungen des Mappings pruefen: wird der Zugriff verweigert, generiert die Methode einen 405-Fehler
        if (!$this->processMethod($request, $mapping))
            return;


        // benoetigte Rollen ueberpruefen
        if (!$this->processRoles($request, $mapping))
            return;


        // ActionForm vorbereiten
        $form = $this->processActionFormCreate($request, $mapping);


        // ActionForm validieren
        if ($form && !$this->processActionFormValidate($request, $mapping, $form))
            return;


        // falls statt einer Action ein direkter Forward konfiguriert wurde, diesen verarbeiten
        if (!$this->processMappingForward($request, $mapping))
            return;


        // Action erzeugen (Form und Mapping werden schon hier uebergeben, damit User-Code einfacher wird)
        $action = $this->processActionCreate($mapping, $form);


        // Action aufrufen
        $forward = $this->processActionExecute($request, $response, $action);
        if (!$forward)
            return;


        // den zurueckgegebenen ActionForward verarbeiten
        $this->processActionForward($request, $forward);
    }


    /**
     * Wurde mit dem Request eine Session-ID uebertragen, wird die entsprechende HttpSession fortgesetzt.
     *
     * @param  Request $request
     */
    protected function processSession(Request $request) {
        if (!$request->isSession() && $request->isSessionId()) {
            $request->getSession();
        }
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
     *
     * @return ActionMapping|null - ActionMapping oder NULL
     */
    protected function processMapping(Request $request) {
        // Pfad fuer die Mappingauswahl ermitteln ...
        $requestPath = '/'.trim(preg_replace('|/{2,}|', '/', $request->getPath()), '/').'/';   // normalize request path
        if ($requestPath=='//') $requestPath = '/';
        // /
        // /controller/action/
        // /module/
        // /module/controller/action/

        $appBaseUri = trim($request->getApplicationBaseUri(), '/');
        // ""
        // app

        $modulePrefix = trim($this->module->getPrefix(), '/');
        // ""
        // module

        $modulePath = '/'.trim($appBaseUri.'/'.$modulePrefix, '/').'/';
        if ($modulePath=='//') $modulePath = '/';
        // /
        // /app/
        // /module/
        // /app/module/

        $path = '/'.strRightFrom($requestPath, $modulePath);
        // /
        // /controller/action/

        self::$logDebug && Logger::log('Path used for mapping selection: "'.$path.'"', L_DEBUG);

        // Mapping suchen und im Request speichern
        if (($mapping=$this->module->findMapping($path)) || ($mapping=$this->module->getDefaultMapping())) {
            $request->setAttribute(ACTION_MAPPING_KEY, $mapping);
            return $mapping;
        }

        // kein Mapping gefunden
        self::$logInfo && Logger::log('Could not find a mapping for path: '.$path, L_INFO);

        // Status-Code 404 setzen, bevor Content ausgegeben wird
        header('HTTP/1.1 404 Not Found', true);

        // konfiguriertes 404-Layout suchen
        if ($forward=$this->module->findForward((string) HttpResponse::SC_NOT_FOUND)) {
            // falls vorhanden, einbinden...
            $this->processActionForward($request, $forward);
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
     * @param  ActionMapping $mapping
     *
     * @return bool
     */
    protected function processMethod(Request $request, ActionMapping $mapping) {
        if ($mapping->isSupportedMethod($request->getMethod()))
            return true;

        // Beschraenkung nicht erfuellt
        self::$logDebug && Logger::log('HTTP method "'.$request->getMethod().'" is not supported by ActionMapping, denying access', L_DEBUG);

        // Status-Code 405 setzen, bevor Content ausgegeben wird
        header('HTTP/1.1 405 Method Not Allowed', true);

        // konfiguriertes 405-Layout suchen
        if ($forward=$this->module->findForward((string) HttpResponse::SC_METHOD_NOT_ALLOWED)) {
            // falls vorhanden, einbinden...
            $this->processActionForward($request, $forward);
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
     * @param  ActionMapping $mapping
     *
     * @return bool
     */
    protected function processRoles(Request $request, ActionMapping $mapping) {
        if ($mapping->getRoles() === null)
            return true;

        $forward = $this->module->getRoleProcessor()->processRoles($request, $mapping);
        if (!$forward)
            return true;

        $this->processActionForward($request, $forward);
        return false;
    }


    /**
     * Erzeugt die ActionForm des angegebenen Mappings bzw. gibt sie zurueck. Ist keine ActionForm
     * konfiguriert, wird NULL zurueckgegeben.
     *
     * @param  Request       $request
     * @param  ActionMapping $mapping
     *
     * @return ActionForm
     */
    protected function processActionFormCreate(Request $request, ActionMapping $mapping) {
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
     * Validiert die ActionForm, wenn entprechend konfiguriert.  Ist fuer das ActionMapping ein direkter
     * Forward konfiguriert, wird nach der Validierung auf diesen Forward weitergeleitet. Ist kein
     * allgemeiner Forward definiert, wird auf die konfigurierte "success" oder "error"-Resource
     * weitergeleitet.  Gibt TRUE zurueck, wenn die Verarbeitung fortgesetzt werden soll, oder FALSE,
     * wenn auf eine andere Resource weitergeleitet und der Request bereits beendet wurde.
     *
     * @param  Request       $request
     * @param  ActionMapping $mapping
     * @param  ActionForm    $form
     *
     * @return bool
     */
    protected function processActionFormValidate(Request $request, ActionMapping $mapping, ActionForm $form) {
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
        if (!$forward) throw new RuntimeException('ActionForward for mapping "'.$mapping->getPath().'" not found (Module validation error?)');

        $this->processActionForward($request, $forward);
        return false;
    }


    /**
     * Verarbeitet einen direkt im ActionMapping angegebenen ActionForward (wenn angegeben). Gibt TRUE
     * zurueck, wenn die Verarbeitung fortgesetzt werden soll, oder FALSE, wenn der Request bereits
     * beendet wurde.
     *
     * @param  Request       $request
     * @param  ActionMapping $mapping
     *
     * @return bool
     */
    protected function processMappingForward(Request $request, ActionMapping $mapping) {
        $forward = $mapping->getForward();
        if (!$forward)
            return true;

        $this->processActionForward($request, $forward);
        return false;
    }


    /**
     * Erzeugt und gibt die Action zurueck, die fuer das angegebene Mapping konfiguriert wurde.
     *
     * @param  ActionMapping   $mapping
     * @param  ActionForm|null $form     - ActionForm, die konfiguriert wurde oder NULL
     *
     * @return Action
     */
    protected function processActionCreate(ActionMapping $mapping, ActionForm $form=null) {
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

            if ($forward === null)
                self::$logInfo && Logger::log('ActionForward of NULL returned from Action::execute()', L_INFO);
        }
        catch (Exception $ex) {
            $throwable = $ex;    // evt. aufgetretene Exception zwischenspeichern
        }

        // falls statt eines ActionForwards ein String-Identifier zurueckgegeben wurde, diesen aufloesen
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
     * Verarbeitet den von der Action zurueckgegebenen ActionForward.  Leitet auf die Resource weiter,
     * die der ActionForward bezeichnet.
     *
     * @param  Request       $request
    * @param  ActionForward $forward
     */
    protected function processActionForward(Request $request, ActionForward $forward) {
        $module = $this->module;

        if ($forward->isRedirect()) {
            $this->cacheActionMessages($request);

            $path = $forward->getPath();

            // pruefen, ob der Forward eine URL "http://www..." oder einen Path "/target?..." enthaelt
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
                // $path ist ein Dateiname, generische Tile erzeugen
                $class = $module->getTilesClass();
                $tile = new $class($this->module);
                $tile->setName(Tile::GENERIC_NAME)
                     ->setFileName($path)
                     ->freeze();
            }
            $tile->render();
        }
    }
}
