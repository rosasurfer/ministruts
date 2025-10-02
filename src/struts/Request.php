<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\RuntimeException;

use function rosasurfer\ministruts\first;
use function rosasurfer\ministruts\getHostByAddress;
use function rosasurfer\ministruts\ini_get_bool;
use function rosasurfer\ministruts\strCompareI;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strLeft;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strRightFrom;
use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\DAY;
use const rosasurfer\ministruts\NL;

/**
 * Request
 *
 * An object representing the current HTTP request. Provides helper methods and an
 * additional variables context with the life-time of the request.
 */
class Request extends CObject {

    /** @var string */
    protected string $method;

    /** @var ?string */
    protected ?string $path = null;

    /** @var ?string */
    protected ?string $hostUrl = null;

    /** @var array<string, mixed> */
    protected array $_GET;

    /** @var array<string, mixed> */
    protected array $_POST;

    /** @var array<string, mixed> */
    protected array $_REQUEST;

    /** @var ?ActionInput - all input */
    protected ?ActionInput $allInput = null;

    /** @var ?ActionInput - GET input*/
    protected ?ActionInput $getInput = null;

    /** @var ?ActionInput - POST input */
    protected ?ActionInput $postInput = null;

    /** @var ?scalar[][] - normalized array of files uploaded with the request */
    protected ?array $files = null;

    /** @var mixed[] - additional variables context */
    protected array $attributes = [];


    /**
     * Constructor
     */
    public function __construct() {
        $this->method   = $_SERVER['REQUEST_METHOD'];
        $this->_GET     = $_GET;
        $this->_POST    = $_POST;
        $this->_REQUEST = $_REQUEST;
    }


    /**
     * Return the HTTP method of the request.
     *
     * @return string
     */
    public function getMethod(): string {
        return $this->method;
    }


    /**
     * Whether the request is a GET request.
     *
     * @return bool
     */
    public function isGet(): bool {
        return $this->method == 'GET';
    }


    /**
     * Whether the request is a POST request.
     *
     * @return bool
     */
    public function isPost(): bool {
        return $this->method == 'POST';
    }


    /**
     * Whether the request is a result of an AJAX call.
     *
     * @return bool
     */
    public function isAjax(): bool {
        static $isAjax;
        $isAjax ??= strCompareI($this->getHeader('X-Requested-With'), 'XMLHttpRequest');
        return $isAjax;
    }


    /**
     * Whether the request was made over a secure connection (HTTPS).
     *
     * @return bool
     */
    public function isSecure(): bool {
        return !empty($_SERVER['HTTPS']) && !strCompareI($_SERVER['HTTPS'], 'off');
    }


    /**
     * Return an object wrapper for all raw input parameters of the request. It includes GET and POST parameters.
     *
     * @return ActionInput
     */
    public function input(): ActionInput {
        $this->allInput ??= new ActionInput($this->_REQUEST);
        return $this->allInput;
    }


    /**
     * Return an object wrapper for all raw GET parameters of the request.
     *
     * @return ActionInput
     */
    public function get(): ActionInput {
        $this->getInput ??= new ActionInput($this->_GET);
        return $this->getInput;
    }


    /**
     * Return an object wrapper for all raw POST parameters of the request.
     *
     * @return ActionInput
     */
    public function post(): ActionInput {
        $this->postInput ??= new ActionInput($this->_POST);
        return $this->postInput;
    }


    /**
     * Return an object-oriented representation of the files uploaded with the request. The PHP array structure of $_FILES
     * is converted to normalized arrays.
     *
     * @return scalar[][] - associative array of files
     *
     * @todo   convert the returned arrays to instances of UploadedFile
     */
    public function getFiles(): array {
        if (!isset($this->files)) {
            $normalizeLevel = null;
            $normalizeLevel = static function(array $file) use (&$normalizeLevel) {
                if (isset($file['name']) && is_array($file['name'])) {
                    $properties = \array_keys($file);
                    $normalized = [];
                    foreach ($file['name'] as $name => $_) {
                        foreach ($properties as $property) {
                            $normalized[$name][$property] = $file[$property][$name];
                        }
                        $normalized[$name] = $normalizeLevel($normalized[$name]);
                    }
                    $file = $normalized;
                }
                return $file;
            };

            $this->files = [];
            foreach ($_FILES as $key => $file) {
                $this->files[$key] = $normalizeLevel($file);
            }
        }
        return $this->files;
    }


    /**
     * Return an object-oriented representation of a single file uploaded with the request.
     *
     * @param  string $name - parameter name of the file upload
     *
     * @return scalar[]|null - array or NULL if no such file was uploaded
     *
     * @todo   convert the returned array to instance of UploadedFile
     */
    public function getFile(string $name): ?array {
        $files = $this->getFiles();
        return $files[$name] ?? null;
    }


    /**
     * Return the host name the request was made to.
     *
     * @return string - host name
     *
     * @example
     * <pre>
     *  $request->getUrl();             // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getHostname();        // "a.domain.tld"
     * </pre>
     */
    public function getHostname(): string {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $httpHost = strtolower(trim($_SERVER['HTTP_HOST']));    // nginx doesn't set $_SERVER[SERVER_NAME]
            if (strlen($httpHost))                                  // automatically to $_SERVER[HTTP_HOST]
                return $httpHost;
        }
        return $_SERVER['SERVER_NAME'] ?? '';
    }


    /**
     * Return the root URL of the server the request was made to. This value always ends with a slash "/".
     *
     * @return string - root URL: protocol + host name + port
     *
     * @example
     * <pre>
     *  $request->getUrl();             // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getHostUrl();         // "http://a.domain.tld/"
     * </pre>
     */
    public function getHostUrl(): string {
        if (!$this->hostUrl) {
            $protocol = $this->isSecure() ? 'https' : 'http';
            $host = $this->getHostname();
            $port = ':'.$_SERVER['SERVER_PORT'];
            if ($protocol.$port=='http:80' || $protocol.$port=='https:443') {
                $port = '';
            }
            $this->hostUrl = $protocol.'://'.$host.$port.'/';
        }
        return $this->hostUrl;
    }


    /**
     * Return the full URL of the request.
     *
     * @return string - full URL: protocol + host name + port + path + query string
     *
     * @example
     * <pre>
     *  $request->getUrl();         // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getUrl(): string {
        return strLeft($this->getHostUrl(), -1).$this->getUri();
    }


    /**
     * Return the URI of the request (the value in the first line of the HTTP protocol).
     * This value always starts with a slash "/".
     *
     * @return string - URI: path + query string
     *
     * @example
     * <pre>
     *  $request->getUrl();         // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getUri();         // "/path/application/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getUri(): string {
        return $_SERVER['REQUEST_URI'] ?? '';
    }


    /**
     * Return the path fragment of the request's URI. This value always starts with a slash "/".
     *
     * @return string - path without query string
     *
     * @example
     * <pre>
     *  $request->getUrl();         // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getPath():        // "/path/application/module/foo/bar.html"
     * </pre>
     */
    public function getPath(): string {
        if (!$this->path) {
            $value = $this->getUri();
            $value = strLeftTo($value, '?');
            $value = strLeftTo($value, '#');

            $this->path = $value;
        }
        return $this->path;
    }


    /**
     * Return the root URL of the application. This value always ends with a slash "/".
     *
     * @return string - URL: protocol + host name + port + application_base_uri
     *
     * @example
     * <pre>
     *  $request->getUrl();             // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getApplicationUrl();  // "http://a.domain.tld/path/application/"
     * </pre>
     */
    public function getApplicationUrl(): string {
        // TODO: Move to application as this is not a property of the request.
        return strLeft($this->getHostUrl(), -1).$this->getApplicationBaseUri();
    }


    /**
     * Return the request's URI relative to the application's base URL. This value always starts with a slash "/".
     *
     * @return string - URI: slash + module prefix + path + query string
     *
     * @example
     * <pre>
     *  $request->getUrl();                     // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getApplicationRelativeUri();  // "/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getApplicationRelativeUri(): string {
        // TODO: Move to application as this is not a property of the request.
        return '/'.strRightFrom($this->getUri(), $this->getApplicationBaseUri());
    }


    /**
     * Return the request's path fragment relative to the application's base URL.
     * This value always starts with a slash "/".
     *
     * @return string - path fragment: slash + module prefix + path (without query string)
     *
     * @example
     * <pre>
     *  $request->getUrl();                     // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getApplicationRelativePath(); // "/module/foo/bar.html"
     * </pre>
     */
    public function getApplicationRelativePath(): string {
        // TODO: Move to application as this is not a property of the request.
        return '/'.strRightFrom($this->getPath(), $this->getApplicationBaseUri());
    }


    /**
     * Return the application's base URI. The value always starts and ends with a slash "/".
     *
     * @return string - a partial URI (application path without module prefix and query string)
     *
     * @example
     * <pre>
     *  $request->getUrl();                     // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getApplicationBaseUri();      // "/path/application/"
     * </pre>
     */
    public function getApplicationBaseUri(): string {
        // TODO: Move to application as this is not a property of the request.
        static $baseUri;
        if (!isset($baseUri)) {
            $baseUri = $this->resolveBaseUriVar();

            if (!isset($baseUri)) {
                /** @var Config $config */
                $config  = $this->di('config');
                /** @var ?string $baseUri */
                $baseUri = $config->get('app.base-uri', null);
                if (!$baseUri) throw new RuntimeException('Unknown application base URI, either $_SERVER["APP_BASE_URI"] or $config["app.base-uri"] needs to be configured.');
            }
            !strStartsWith($baseUri, '/') && $baseUri  = '/'.$baseUri;
            !strEndsWith  ($baseUri, '/') && $baseUri .= '/';
        }
        return $baseUri;
    }


    /**
     * Resolve the value of an existing APP_BASE_URI server variable. Considers existing redirection values.
     *
     * @return ?string - value or NULL if the variable is not defined
     */
    private function resolveBaseUriVar(): ?string {
        $envName = 'APP_BASE_URI';
        $envValue = null;

        if (!isset($_SERVER[$envName]))
            $envName = 'REDIRECT_'.$envName;

        while (isset($_SERVER[$envName])) {
            $envValue = $_SERVER[$envName];
            $envName = 'REDIRECT_'.$envName;
        }
        return $envValue;
    }


    /**
     * Return the query string of the request's URL.
     *
     * @return string
     *
     * @example
     * <pre>
     *  $request->getUrl();                 // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getQueryString();         // "key=value"
     * </pre>
     */
    public function getQueryString(): string {
        // The variable $_SERVER['QUERY_STRING'] is set by the server and can differ from the transmitted query string.
        // The server variable may hold additional parameters, or it may be empty (e.g. on a mis-configured Nginx).

        if (isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING'])) {
            $query = $_SERVER['QUERY_STRING'];
        }
        else {
            $query = strRightFrom($_SERVER['REQUEST_URI'], '?');
        }
        return $query;
    }


    /**
     * Return the IP address the request was made from.
     *
     * @return string - IP address
     */
    public function getRemoteAddress(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }


    /**
     * Return the name of the host the request was made from.
     *
     * @return string - host name
     */
    public function getRemoteHostname(): string {
        static $hostname = null;
        if (!$hostname) {
            /** @var string $hostname */
            $hostname = getHostByAddress($this->getRemoteAddress());
        }
        return $hostname;
    }


    /**
     * Return the value of a transmitted "X-Forwarded-For" header.
     *
     * @return ?string - header value (one or more ip addresses or hostnames) or NULL if the header was not transmitted
     */
    public function getForwardedRemoteAddress(): ?string {
        return $this->getHeaderValue('X-Forwarded-For', 'X-UP-Forwarded-For');
    }


    /**
     * Return the content of the request (the body). For file uploads the method doesn't return the real binary content.
     * Instead it returns available metadata.
     *
     * @return string - request body or metadata
     */
    public function getContent(): string {
        static $content  = '';
        static $isRead = false;

        if (!$isRead) {
            if ($this->getContentType() == 'multipart/form-data') {
                // file upload
                if ($_POST) {                                           // php://input is not available with enctype="multipart/form-data"
                    $content = '$_POST => '.print_r($_POST, true).NL;
                }
                $content .= '$_FILES => '.print_r($this->getFiles(), true);
            }
            else {
                // regular request body
                $content = file_get_contents('php://input');
            }
            $isRead = true;
        }
        return $content;
    }


    /**
     * Return the "Content-Type" header of the request. If multiple "Content-Type" headers have been transmitted the first
     * one is returned.
     *
     * @return ?string - "Content-Type" header or NULL if no "Content-Type" header was transmitted
     */
    public function getContentType(): ?string {
        $contentType = $this->getHeaderValue('Content-Type');

        if ($contentType) {
            $headers     = explode(',', $contentType, 2);
            $contentType = \array_shift($headers);

            $values      = explode(';', $contentType, 2);
            $contentType = trim(\array_shift($values));
        }
        return $contentType;
    }


    /**
     * Return the current HTTP session object. If a session object does not yet exist, one is created.
     *
     * @return HttpSession
     */
    public function getSession(): HttpSession {
        return HttpSession::me();
    }


    /**
     * Whether an HTTP session was started during the request. Not whether the session is still open (active).
     *
     * @return bool
     */
    public function isSession(): bool {
        return session_id() !== '';
    }


    /**
     * Whether a session attribute of the specified name exists. If no session exists none is started.
     *
     * @param  string $key - key
     *
     * @return bool
     */
    public function isSessionAttribute(string $key): bool {
        if ($this->isSession() || $this->hasSessionId())
            return $this->getSession()->isAttribute($key);
        return false;
    }


    /**
     * Return the session id transmitted with the request (not the id sent with the response, which may differ).
     *
     * @return string
     */
    public function getSessionId(): string {
        $name = session_name();

        if (ini_get_bool('session.use_cookies'))
            if (isset($_COOKIE[$name]))
                return $_COOKIE[$name];

        if (!ini_get_bool('session.use_only_cookies'))
            if (isset($_REQUEST[$name]))
                return $_REQUEST[$name];

        return '';
    }


    /**
     * Whether a valid session id was transmitted with the current request. For example, a URL based session id is invalid
     * if the "php.ini" setting "session.use_only_cookies" is enabled.
     *
     * @return bool
     */
    public function hasSessionId(): bool {
        $name = session_name();

        if (ini_get_bool('session.use_cookies'))
            if (isset($_COOKIE[$name]))
                return true;

        if (!ini_get_bool('session.use_only_cookies'))
            if (isset($_REQUEST[$name]))
                return true;

        return false;
    }


    /**
     * Destroy the current session and it's data.
     *
     * @return void
     */
    public function destroySession(): void {
        if (session_status() == PHP_SESSION_ACTIVE) {
            // unset all session variables
            $_SESSION = [];

            // delete the session cookie
            if (ini_get_bool('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time()-1*DAY, $params['path'    ],
                                                            $params['domain'  ],
                                                            $params['secure'  ],
                                                            $params['httponly']);
            }
            session_destroy();          // TODO: check if SID is reset
        }
    }


    /**
     * Return the first transmitted header with the specified name.
     *
     * @param  string $name - header name
     *
     * @return ?string - header value or NULL if no such header was transmitted
     */
    public function getHeader(string $name): ?string {
        $headers = $this->getHeaders($name);
        return \array_shift($headers);
    }


    /**
     * Return all headers with the specified name as an associative array of header values (in transmitted order).
     *
     * @param  string ...$names [optional] - one or more header names (default: all headers)
     *
     * @return string[] - associative array of header values
     */
    public function getHeaders(string ...$names): array {
        static $headers = null;

        // read headers only once
        if ($headers === null) {
            // headers with custom name representation (names are case-insensitive)
            $specialHeaders = [
                'CDN'             => 'CDN',
                'DNT'             => 'DNT',
                'SEC_GPC'         => 'Sec-GPC',
                'X_CDN'           => 'X-CDN',
                'X_MINISTRUTS_UI' => 'X-Ministruts-UI',
                'X_REAL_IP'       => 'X-Real-IP',
            ];
            $headers = [];

            foreach ($_SERVER as $name => $value) {
                while (substr($name, 0, 9) == 'REDIRECT_') {
                    $name = substr($name, 9);
                    if (isset($_SERVER[$name])) {
                        continue 2;
                    }
                }
                if (substr($name, 0, 5) == 'HTTP_') {
                    $name = substr($name, 5);
                    $name = $specialHeaders[$name] ?? str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($name))));
                    $headers[$name] = $value;
                }
            }

            // reconstruct an existing 'Authorization' header
            if (!isset($headers['Authorization']) && isset($_SERVER['AUTH_TYPE'])) {
                $authType = $_SERVER['AUTH_TYPE'];
                if ($authType == 'Basic') {
                    $user = $_SERVER['PHP_AUTH_USER'] ?? '';
                    $pass = $_SERVER['PHP_AUTH_PW'] ?? '';
                    $headers['Authorization'] = 'Basic '.base64_encode("$user:$pass");
                }
                if ($authType == 'Digest') {
                    $digest = $_SERVER['PHP_AUTH_DIGEST'] ?? '';
                    $headers['Authorization'] = "Digest $digest";
                }
            }

            // sort headers
            uksort($headers, 'strnatcasecmp');

            // move "Host" header to the beginning
            $value = $headers[$name='Host'] ?? null;
            if (isset($value)) {
                unset($headers[$name]);
                $headers = [$name => $value] + $headers;
            }
        }

        // return all or just the specified headers
        if (!$names) {
            return $headers;
        }
        return \array_intersect_ukey($headers, \array_flip($names), 'strcasecmp');
    }


    /**
     * Return a single value of the specified header/s. If multiple headers are specified or multiple headers have been
     * transmitted, return all values as one comma-separated value (in transmission order).
     *
     * @param  string ...$names - one or multiple header names
     *
     * @return ?string - value or NULL if no such headers have been transmitted
     */
    public function getHeaderValue(string ...$names): ?string {
        $headers = $this->getHeaders(...$names);
        return $headers ? join(',', $headers) : null;
    }


    /**
     * Return the values of all specified header(s) as an array (in transmission order).
     *
     * @param  string ...$names - one or multiple header names
     *
     * @return string[] - values or an empty array if no such headers have been transmitted
     */
    public function getHeaderValues(string ...$names): array {
        $headers = $this->getHeaders(...$names);
        if (!$headers) return [];

        return \array_map('trim', explode(',', join(',', $headers)));
    }


    /**
     * Return a value stored in the request's variables context under the specified name.
     *
     * @param  string $name - attribute name
     *
     * @return mixed - attribute value or NULL if no value is stored under the specified name
     */
    public function getAttribute(string $name) {
        if (\key_exists($name, $this->attributes))
            return $this->attributes[$name];
        return null;
    }


    /**
     * Return all values stored in the request's variables context.
     *
     * @return mixed[]
     */
    public function getAttributes(): array {
        return $this->attributes;
    }


    /**
     * Store a value in the local variables context. May be used to transfer data from controllers or {@link Action}s
     * to views.
     *
     * @param  string $name  - name under which the value is stored
     * @param  mixed  $value - value to store
     *
     * @return $this
     */
    public function setAttribute(string $name, $value): self {
        $this->attributes[$name] = $value;
        return $this;
    }


    /**
     * Remove the value(s) with the specified name(s) from the request's variables context.
     *
     * @param  string ...$names - names of the values to remove
     *
     * @return void
     */
    public function removeAttributes(string ...$names): void {
        foreach ($names as $name) {
            unset($this->attributes[$name]);
        }
    }


    /**
     * Send a cookie.
     *
     * @param  string  $name               - cookie name
     * @param  string  $value              - cookie value
     * @param  int     $expires [optional] - timestamp when the cookie expires (default: when the browser is closed)
     * @param  ?string $path    [optional] - path the cookie will be available for (default: the application)
     *
     * @return $this
     */
    public function setCookie(string $name, string $value, int $expires = 0, ?string $path = null): self {
        if ($expires < 0) throw new InvalidValueException('Invalid parameter $expires: '.$expires);

        if (!isset($path)) {
            $path = $this->getApplicationBaseUri();
        }
        \setcookie($name, $value, $expires, $path);
        return $this;
    }


    /**
     * Whether the current web user owns the specified role.
     *
     * @param  string $role - a single role identifier (not an expression)
     *
     * @return bool
     */
    public function isUserInRole(string $role): bool {
        /** @var Module|null $module */
        $module = $this->getAttribute(Struts::MODULE_KEY);
        if (!$module) throw new RuntimeException('Current Struts module not found');

        $processor = $module->getRoleProcessor();
        if (!$processor) throw new RuntimeException('No RoleProcessor configured for Struts module with prefix "'.$module->getPrefix().'"');

        return $processor->isUserInRole($this, $role);
    }


    /**
     * Return the stored ActionMessage for the specified key, or the first ActionMessage if no key was given.
     *
     * @param  ?string $key [optional]
     *
     * @return ?string - message
     */
    public function getActionMessage(?string $key = null): ?string {
        /** @var string[] $messages */
        $messages = $this->getAttribute(Struts::ACTION_MESSAGES_KEY) ?? [];

        if (!isset($key)) {                             // return the first one
            if ($messages) {
                return first($messages);
            }
        }
        elseif (\key_exists($key, $messages)) {         // return the specified one
            return $messages[$key];
        }
        return $this->getActionError($key);             // look-up separately stored ActionErrors
    }


    /**
     * Return all stored ActionMessages, including ActionErrors.
     *
     * @return string[]
     */
    public function getActionMessages(): array {
        $messages = $this->getAttribute(Struts::ACTION_MESSAGES_KEY) ?? [];
        $errors = $this->getActionErrors();
        return \array_merge($messages, $errors);
    }


    /**
     * Whether an ActionMessage exists for one of the specified keys, or for any key if no key was given.
     *
     * @param  string ...$keys [optional] - message keys
     *
     * @return bool
     */
    public function isActionMessage(string ...$keys): bool {
        $messages = $this->getActionMessages();

        if (!$keys) {
            return (bool) $messages;
        }
        if ($messages) {
            foreach ($keys as $key) {
                if (\key_exists($key, $messages)) return true;
            }
        }
        return false;
    }


    /**
     * Store an ActionMessage for the specified key.
     *
     * @param  string|int $key     - message key
     * @param  ?string    $message - message; if NULL is passed the message for the specified key is removed
     *
     * @return $this
     */
    public function setActionMessage($key, ?string $message): self {
        if (!isset($message)) {
            unset($this->attributes[Struts::ACTION_MESSAGES_KEY][$key]);
        }
        else {
            $this->attributes[Struts::ACTION_MESSAGES_KEY][$key] = $message;
        }
        return $this;
    }


    /**
     * Remove the ActionMessage(s) with the specified key(s).
     *
     * @param  string ...$keys - zero or more message keys to remove; without a key all ActionMessages are removed
     *
     * @return string[] - the removed ActionMessages
     */
    public function removeActionMessages(string ...$keys): array {
        $messages = $this->getAttribute(Struts::ACTION_MESSAGES_KEY) ?? [];
        $removed = [];

        foreach ($keys as $key) {
            if (isset($messages[$key])) {
                $removed[$key] = $messages[$key];
            }
            unset($this->attributes[Struts::ACTION_MESSAGES_KEY][$key]);
        }
        if ($keys) {
            return $removed;
        }
        unset($this->attributes[Struts::ACTION_MESSAGES_KEY]);
        return $messages;
    }


    /**
     * Return the stored ActionError for the specified key, or the first ActionError if no key was given.
     *
     * @param  ?string $key [optional]
     *
     * @return ?string - message
     */
    public function getActionError(?string $key = null): ?string {
        /** @var string[] $errors */
        $errors = $this->getAttribute(Struts::ACTION_ERRORS_KEY) ?? [];

        if (!isset($key)) {                             // return the first one
            if ($errors) {
                return first($errors);
            }
        }
        elseif (\key_exists($key, $errors)) {           // return the specified one
            return $errors[$key];
        }
        return null;
    }


    /**
     * Return all stored ActionErrors.
     *
     * @return string[]
     */
    public function getActionErrors(): array {
        return $this->getAttribute(Struts::ACTION_ERRORS_KEY) ?? [];
    }


    /**
     * Whether an ActionError exists for one of the specified keys, or for any key if no key was given.
     *
     * @param  string ...$keys [optional] - error keys
     *
     * @return bool
     */
    public function isActionError(string ...$keys): bool {
        $errors = $this->getActionErrors();

        if (!$keys) {
            return (bool) $errors;
        }
        if ($errors) {
            foreach ($keys as $key) {
                if (\key_exists($key, $errors)) return true;
            }
        }
        return false;
    }


    /**
     * Store an ActionError for the specified key.
     *
     * @param  string|int $key     - error key
     * @param  ?string    $message - message; if NULL is passed the error for the specified key is removed
     *
     * @return $this
     */
    public function setActionError($key, ?string $message): self {
        if (!isset($message)) {
            unset($this->attributes[Struts::ACTION_ERRORS_KEY][$key]);
        }
        else {
            $this->attributes[Struts::ACTION_ERRORS_KEY][$key] = $message;
        }
        return $this;
    }


    /**
     * Remove the ActionError(s) with the specified key(s).
     *
     * @param  string ...$keys - zero or more error keys to delete; without a key all ActionErrors are removed
     *
     * @return string[] - the removed ActionErrors
     */
    public function removeActionErrors(string ...$keys): array {
        $errors = $this->getAttribute(Struts::ACTION_ERRORS_KEY) ?? [];
        $removed = [];

        foreach ($keys as $key) {
            if (isset($errors[$key])) {
                $removed[$key] = $errors[$key];
            }
            unset($this->attributes[Struts::ACTION_ERRORS_KEY][$key]);
        }
        if ($keys) {
            return $removed;
        }

        unset($this->attributes[Struts::ACTION_ERRORS_KEY]);
        return $errors;
    }


    /**
     * Return the {@link ActionMapping} responsible for processing the current request.
     *
     * @return ?ActionMapping - instance or NULL if the request doesn't match any of the configured mappings
     */
    final public function getMapping(): ?ActionMapping {
        return $this->getAttribute(Struts::ACTION_MAPPING_KEY);
    }


    /**
     * Return the {@link Module} the current request is assigned to.
     *
     * @return ?Module - instance or NULL if the request doesn't match any of the configured modules
     */
    final public function getModule(): ?Module {
        return $this->getAttribute(Struts::MODULE_KEY);
    }


    /**
     * Reject serialization of request instances.
     *
     * @return string[]
     */
    final public function __sleep(): array {
        throw new IllegalStateException('You cannot serialize a '.static::class);
    }


    /**
     * Reject deserialization of Request instances.
     *
     * @return void
     */
    final public function __wakeUp(): void {
        throw new IllegalStateException('You cannot deserialize a '.static::class);
    }


    /**
     * {@inheritDoc}
     */
    public function __toString(): string {
        // request
        $string = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL'].NL;

        // headers
        $headers = $this->getHeaders();
        $maxLen = 0;
        foreach ($headers as $key => $value) {
            $maxLen = max(strlen($key), $maxLen);
        }
        $maxLen++;                                                          // add one char for ':'
        foreach ($headers as $key => $value) {
            $string .= str_pad($key.':', $maxLen).' '.$value.NL;
        }

        // content (request body)
        $content = $this->getContent();
        if (strlen($content)) {
            $string .= NL.substr($content, 0, 2_048).NL;                    // limit the request body to 2048 bytes
        }
        return $string;
    }
}
