<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use Throwable;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\net\http\HttpResponse;


/**
 * RequestProcessor
 *
 * The default {@link RequestProcessor} implementation used by the framework if no custom RequestProcessor was configured.
 *
 * A custom implementation can be configured for a single {@link Module} by defining the module's Struts config attribute
 * <tt>/struts-config/controller[@request-processor="%ClassName"]</tt>.
 *
 * A custom implementation can be configured for all {@link Module}s by re-defining the DI service named "requestProcessor".
 */
class RequestProcessor extends CObject {


    /** @var Module - the Module the instance belongs to */
    protected $module;

    /** @var scalar[] - additional runtime options */
    protected $options;


    /**
     * Constructor
     *
     * @param  Module   $module  - the Module the instance belongs to
     * @param  scalar[] $options - additional runtime options
     */
    public function __construct(Module $module, array $options) {
        $this->module  = $module;
        $this->options = $options;
    }


    /**
     * Process the passed {@link Request} and generate a {@link Response}. The resulting output may be dynamic content
     * generated by an {@link Action}, static content generated by including a file, or an HTTP redirect to another resource.
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return void
     */
    public function process(Request $request, Response $response) {
        // Start or continue a session (if applicable).
        $this->processSession($request);

        // Resolve the ActionMapping matching the request. Generates an HTTP 404 error if none is found.
        $mapping = $this->processMapping($request, $response);
        if (!$mapping) return;

        // Enforce the mapping's configured HTTP method restrictions. Generates an HTTP 405 error if the used method is not allowed.
        if (!$this->processMethod($request, $response, $mapping)) {
            return;
        }

        // Enforce the mapping's configured role restrictions.
        if (!$this->processRoles($request, $response, $mapping)) {
            return;
        }

        // Instantiate an ActionForm (a parameter wrapper for the request's user input).
        $form = $this->processActionFormCreate($request, $mapping);

        // if configured validate the ActionForm
        if (!$this->processActionFormValidate($request, $response, $mapping, $form)) {
            return;
        }

        // If the mapping defines an ActionForward instead of an Action, process it and return if processing has finished.
        if (!$this->processMappingForward($request, $response, $mapping)) {
            return;
        }

        // Instantiate the configured Action.
        $action = $this->processActionCreate($mapping, $form);

        // Execute the configured Action and return if request processing has finished.
        $forward = $this->processActionExecute($request, $response, $action);
        if (!$forward) return;

        // Process the returned ActionForward to generate a response.
        $this->processActionForward($request, $response, $forward);
    }


    /**
     * Handle the HTTP session according to the configuration. If a valid session id was transmitted
     * the session is restarted.
     *
     * @param  Request $request
     *
     * @return void
     */
    protected function processSession(Request $request) {
        $this->restoreActionForm($request);
        $this->restoreActionMessages($request);
    }


    /**
     * Resolve the {@link ActionMapping} matching the passed request and return it. If no exact match is found an attempt is
     * made to find and return a configured default ActionMapping. If no such mapping is found the method generates an HTTP
     * 404 error and returns NULL.
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return ?ActionMapping - ActionMapping or NULL if no mapping responsible for processing the request was found
     */
    protected function processMapping(Request $request, Response $response) {
        // resolve the full request path
        $requestPath = '/'.trim(preg_replace('|/{2,}|', '/', $request->getPath()), '/').'/';
        if ($requestPath=='//') $requestPath = '/';
        // /                                            // path may point to: application root
        // /action/                                     // path may point to: an action
        // /module/                                     // path may point to: an application module
        // /module/action/                              // path may point to: an action of an application module

        // resolve the module selector component of the path
        $moduleUri = $request->getApplicationBaseUri().$this->module->getPrefix();
        // /
        // /app/
        // /module/
        // /app/module/

        // resolve the mapping selector component of the path
        $mappingPath = '/'.substr($requestPath, strlen($moduleUri));
        // /
        // /action/

        // look-up a configured mapping, store it in the request, and return it
        if (($mapping=$this->module->findMapping($mappingPath)) || ($mapping=$this->module->getDefaultMapping())) {
            $request->setAttribute(ACTION_MAPPING_KEY, $mapping);
            return $mapping;
        }

        // no responsible mapping was found: generate HTTP 404
        $response->setStatus(HttpResponse::SC_NOT_FOUND);

        // TODO: remove SCX specific behavior (optional stuff must go into custom implementation)
        if (isset($this->options['status-404']) && $this->options['status-404']=='pass-through') {
            return null;
        }
        header('HTTP/1.1 404 Not Found', true, HttpResponse::SC_NOT_FOUND);

        // check for a pre-configured 404 response and use it
        if ($forward = $this->module->findForward((string) HttpResponse::SC_NOT_FOUND)) {
            $this->processActionForward($request, $response, $forward);
        }
        else {
            // otherwise generate one
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
     * Ensure that the {@link Request} fulfils the method restrictions defined for the {@link ActionMapping}.
     * Returns TRUE if access is granted and processing is to be continued, or FALSE if access is not granted
     * and the request has been terminated.
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

        // method not allowed
        $response->setStatus(HttpResponse::SC_METHOD_NOT_ALLOWED);

        if (isset($this->options['status-405']) && $this->options['status-405']=='pass-through')
            return false;

        header('HTTP/1.1 405 Method Not Allowed', true, HttpResponse::SC_METHOD_NOT_ALLOWED);

        // check for a pre-configured 405 response and use it
        if ($forward = $this->module->findForward((string) HttpResponse::SC_METHOD_NOT_ALLOWED)) {
            $this->processActionForward($request, $response, $forward);
        }
        else {
            // otherwise generate one
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
     * Ensure that the user fulfils the access restrictions defined for the {@link Action}. Returns TRUE if access is
     * granted and processing is to be continued, or FALSE if access is not granted and the request has been terminated.
     *
     * @param  Request       $request
     * @param  Response      $response
     * @param  ActionMapping $mapping
     *
     * @return bool
     */
    protected function processRoles(Request $request, Response $response, ActionMapping $mapping) {
        if (empty($mapping->getRoles())) return true;

        /** @var ActionForward|string|null $forward */
        $forward = $this->module->getRoleProcessor()->processRoles($request, $mapping);
        if (!isset($forward)) return true;

        // if a string identifier was returned resolve the named instance
        if (is_string($forward)) {
            $forward = $mapping->getForward($forward);
        }

        $this->processActionForward($request, $response, $forward);
        return false;
    }


    /**
     * Return the {@link ActionForm} instance assigned to the passed {@link ActionMapping}.
     * If no ActionForm was configured an {@link EmptyActionForm} is instantiated and assigned.
     *
     * @param  Request       $request
     * @param  ActionMapping $mapping
     *
     * @return ActionForm
     */
    protected function processActionFormCreate(Request $request, ActionMapping $mapping) {
        $formClass = $mapping->getFormClassName();

        /** @var ActionForm $form */
        $form = $formClass ? new $formClass($request) : new EmptyActionForm($request);

        // store the form in the request
        $request->setAttribute(ACTION_FORM_KEY, $form);

        return $form;
    }


    /**
     * Validates the {@link ActionForm}, if configured. Forwards to an explicit forward (if configured for the {@link ActionMapping})
     * or to the configured "success" or "error" resource (if no explicit forward is configured). Returns TRUE if processing is to be
     * continued, or FALSE if the {@link Request} was forwarded to another resource and has already been completed.
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
     * Process an {@link ActionForward} defined in the {@link ActionMapping}. Returns TRUE if processing
     * is to be continued, or FALSE if the {@link Request} has already been completed.
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
     * Create and return the {@link Action} configured for the specified {@link ActionMapping}.
     *
     * @param  ActionMapping $mapping
     * @param  ActionForm    $form - ActionForm instance assigned to the mapping
     *
     * @return Action
     */
    protected function processActionCreate(ActionMapping $mapping, ActionForm $form) {
        $className = $mapping->getActionClassName();

        return new $className($mapping, $form);
    }


    /**
     * Passes the {@link Request} to the specified {@link Action} for execution and returns the resulting {@link ActionForward}.
     * The method calls in this order:   <br>
     * - {@link Action::executeBefore()} <br>
     * - {@link Action::execute()}       <br>
     * - {@link Action::executeAfter()}  <br>
     *
     * @param  Request  $request
     * @param  Response $response
     * @param  Action   $action
     *
     * @return ?ActionForward - ActionForward or NULL if the request has already been completed
     */
    protected function processActionExecute(Request $request, Response $response, Action $action) {
        $forward = $ex = null;

        // wrap everything in try-catch, so Action::executeAfter() can be called after any errors
        try {
            // call pre-processing hook
            $forward = $action->executeBefore($request, $response);

            // call Action::execute() only if pre-processing hook doesn't signal request completion
            if ($forward === null) {
                // TODO: implement dispatch() for DispatchActions; as of now it must be done manually in execute()
                $forward = $action->execute($request, $response);
            }
        }
        catch (Throwable $ex) {}               // keep exception for later use

        // convert a returned string identifier to an actual ActionForward
        if (is_string($forward)) {
            $forward = $action->getMapping()->getForward($forward);
        }

        // call post-processing hook
        $forward = $action->executeAfter($request, $response, $forward);

        // now re-throw any catched exceptions
        if ($ex) throw $ex;

        return $forward;
    }


    /**
     * Process the {@link ActionForward} returned from the {@link Action}. Forwards to
     * the resource identified by the ActionForward.
     *
     * @param  Request       $request
     * @param  Response      $response
     * @param  ActionForward $forward
     *
     * @return void
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
            elseif ($path[0] == '/') {                              // an application-relative URI
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
                // $path is a file name , create a generic Tile
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
     * Copy the {@link ActionForm} configured for the current {@link ActionMapping} from the {@link Request} to the {@link HttpSession}.
     * On the next request the form will be available via {@link \rosasurfer\ministruts\core\di\facade\Form::old()}, giving access to
     * the input of the previous request. An {@link EmptyActionForm} can't be configured for a mapping, and will not be copied.
     *
     * @param  Request $request
     *
     * @return void
     */
    protected function storeActionForm(Request $request) {
        $form = $request->getAttribute(ACTION_FORM_KEY);
        if (!$form || $form instanceof EmptyActionForm)
            return;
        $request->getSession()->setAttribute(ACTION_FORM_KEY.'.old', $form);
    }


    /**
     * Move an old {@link ActionForm} stored in the session to the current {@link Request}. The form will be available via
     * {@link \rosasurfer\ministruts\core\di\facade\Forms} and {@link \rosasurfer\ministruts\core\di\facade\Form::old()}, giving access
     * to the input of the previous request.
     *
     * @param  Request $request
     *
     * @return void
     */
    protected function restoreActionForm(Request $request) {
        if ($request->hasSessionId()) {
            $request->getSession();                     // initialize session
            $oldFormKey = ACTION_FORM_KEY.'.old';

            if (isset($_SESSION[$oldFormKey])) {
                $form = $_SESSION[$oldFormKey];
                unset($_SESSION[$oldFormKey]);
                $request->setAttribute($oldFormKey, $form);
            }
        }
    }


    /**
     * Copy all action messages (including action errors) from the {@link Request} to the {@link HttpSession}.
     * On the next HTML request messages are restored and moved back to the new request.
     *
     * @param  Request $request
     *
     * @return void
     */
    protected function storeActionMessages(Request $request) {
        $errors = $request->getActionErrors();
        if ($errors) {
            $request->getSession();                     // initialize session
            if (isset($_SESSION[ACTION_ERRORS_KEY]))
                $errors = \array_merge($_SESSION[ACTION_ERRORS_KEY], $errors);
            $_SESSION[ACTION_ERRORS_KEY] = $errors;
        }

        $messages = $request->getActionMessages();
        if ($messages) {
            $request->getSession();                     // initialize session
            if (isset($_SESSION[ACTION_MESSAGES_KEY]))
                $messages = \array_merge($_SESSION[ACTION_MESSAGES_KEY], $messages);
            $_SESSION[ACTION_MESSAGES_KEY] = $messages;
        }
    }


    /**
     * Move all action messages (including action errors) stored in the session to the current {@link Request}.
     * Found action errors from the previous request are converted to action messages of the current request.
     *
     * @param  Request $request
     *
     * @return void
     */
    protected function restoreActionMessages(Request $request) {
        if ($request->hasSessionId()) {
            $request->getSession();                     // initialize session
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
