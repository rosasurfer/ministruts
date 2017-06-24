<?php
namespace rosasurfer\ministruts;

use rosasurfer\config\Config;
use rosasurfer\core\Singleton;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\util\PHP;

use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\DAY;
use const rosasurfer\NL;


/**
 * An object representing the current HTTP request. It provides an additional variables container (a context) with the
 * life-time of the HTTP request.
 */
class Request extends Singleton {


    /** @var string */
    private $method;

    /** @var string */
    private $hostUrl;

    /** @var string */
    private $path;

    /** @var array - additional variables context */
    private $attributes = [];


    /**
     * Return the <tt>Singleton</tt> instance.
     *
     * @return static
     *
     * @throws RuntimeException if not called from the web interface
     */
    public static function me() {
        if (CLI) throw new RuntimeException('Cannot create a '.static::class.' instance in a non-web context.');
        return Singleton::getInstance(static::class);
    }


    /**
     * Constructor
     */
    protected function __construct() {
        parent::__construct();

        $this->method = $_SERVER['REQUEST_METHOD'];

        // If $_SERVER['QUERY_STRING'] is empty (e.g. at times in nginx) PHP will not parse URL parameters
        // and it needs to be done manually.
        $query = $this->getQueryString();
        if (strLen($query) && !$_GET)
            $this->parseQueryString($query);
    }


    /**
     * Parse the specified query string and store parameters in $GET and $_REQUEST.
     *
     * @param  string $data - raw query string
     */
    protected function parseQueryString($data) {
        $params = explode('&', $data);

        foreach ($params as $param) {
            $parts = explode('=', $param, 2);
            $name  = trim(urlDecode($parts[0])); if (!strLen($name)) continue;
       //$name  = str_replace(['.', ' '], '_', $name);                           // replace as the PHP implementation does
            $value = sizeOf($parts)==1 ? '' : urlDecode($parts[1]);

            // TODO: process multi-dimensional arrays

            if (($open=strPos($name, '[')) && ($close=strPos($name, ']')) && strLen($name)==$close+1) {
                // name is an array index
                $name = trim(subStr($name, 0, $open));
                $key  = trim(subStr($name, $open+1, $close-$open-1));

                if (!strLen($key)) {
                    $_GET[$name][] = $_REQUEST[$name][] = $value;
                }
                else {
                    $_GET[$name][$key]                                    = $value;
                    !isSet($_POST[$name][$key]) && $_REQUEST[$name][$key] = $value;   // GET must not over-write POST
                }
            }
            else {
                // name is not an array index
                $_GET[$name]                              = $value;
                !isSet($_POST[$name]) && $_REQUEST[$name] = $value;                  // GET must not over-write POST
            }
        }
    }


    /**
     * Return the HTTP methode of the current request.
     *
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }


    /**
     * Whether or not the current request is a GET request.
     *
     * @return bool
     */
    public function isGet() {
        return ($this->method == 'GET');
    }


    /**
     * Whether or not the current request is a POST request.
     *
     * @return bool
     */
    public function isPost() {
        return ($this->method == 'POST');
    }


    /**
     * Return the single request parameter with the specified name. If multiple parameters with that name have been
     * transmitted, the last value is returned. If an array of parameters with that name have been transmitted, it is
     * ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string|null - value or NULL if no such single parameter has been transmitted
     */
    public function getParameter($name) {
        if (isSet($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
            if (!is_array($value))
                return $value;
        }
        return null;
    }


    /**
     * Return the array parameters with the specified name. If a single parameter with that name was transmitted, it is
     * ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string[] - values or an empty array if no such array parameters have been transmitted
     */
    public function getParameters($name) {
        if (isSet($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
            if (is_array($value))
                return $value;
        }
        return [];
    }


    /**
     * Return the host name the request was made to.
     *
     * @return string - host name
     *
     * @example
     * <pre>
     * "www.domain.tld"
     * </pre>
     */
    public function getHostname() {
        return $_SERVER['SERVER_NAME'];
    }


    /**
     * Return the root url of the server the request was made to. This value always ends with a slash "/".
     *
     * @return string - root url: protocol + host_name + port
     *
     * @example
     * <pre>
     * "https://www.domain.tld:433/"
     * </pre>
     */
    public function getHostUrl() {
        if (!$this->hostUrl) {
            $protocol = isSet($_SERVER['HTTPS']) ? 'https' : 'http';
            $host     = $this->getHostname();
            $port     = $_SERVER['SERVER_PORT']=='80' ? '' : ':'.$_SERVER['SERVER_PORT'];

            $this->hostUrl = $protocol.'://'.$host.$port.'/';
        }
        return $this->hostUrl;
    }


    /**
     * Return the full url of the current request.
     *
     * @return string - full url: protocol + host_name + port + path + query_string
     *                  All urls in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * "https://www.domain.tld:433/myapplication/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getUrl() {
        return strLeft($this->getHostUrl(), -1).$this->getUri();
    }


    /**
     * Return the URI of the current request (the value in the first line of the HTTP protocol). This value always starts
     * with a slash "/".
     *
     * @return string - URI: path + query_string
     *                  All URLs in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * "/application/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getUri() {
        return $_SERVER['REQUEST_URI'];
    }


    /**
     * Return the path fragment of the current request's URI. This value always starts with a slash "/".
     *
     * @return string - path without query string
     *                  All URLs in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * "/application/module/foo/bar.html"
     * </pre>
     */
    public function getPath() {
        if (!$this->path) {
            $value = $this->getUri();
            $value = strLeftTo($value, '?');
            $value = strLeftTo($value, '#');

            $this->path = $value;
        }
        return $this->path;
    }


    /**
     * Return the root url of the current application. This value always ends with a slash "/".
     *
     * @return string - url: protocol + host_name + port + application_base_uri
     *
     * @example
     * <pre>
     * "https://www.domain.tld:433/myapplication/"
     * </pre>
     */
    public function getApplicationUrl() {
        // TODO: Move to application as this url is not a property of the request.
        return strLeft($this->getHostUrl(), -1).$this->getApplicationBaseUri();
    }


    /**
     * Return the current request's uri relative to the application's base url. This value always starts with a slash "/".
     *
     * @return string - uri: slash + module_prefix + path + query_string
     *                  All urls in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * $request->getUrl():                    "http://a.domain.tld/path/myapplication/module/foo/bar.html?key=value"
     * $request->getApplicationRelativeUri(): "/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getApplicationRelativeUri() {
        return strRightFrom($this->getUri(), $this->getApplicationBaseUri()).'/';
    }


    /**
     * Return the current request's path fragment relative to the application's base url. This value always starts with
     * a slash "/".
     *
     * @return string - path fragment: slash + module_prefix + path (without query string)
     *                  All urls in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * "/module/foo/bar.html"
     * </pre>
     */
    public function getApplicationRelativePath() {
        return '/'.strRightFrom($this->getPath(), $this->getApplicationBaseUri());
    }


    /**
     * Return the application's base uri. The value always starts and ends with a slash "/".
     *
     * @return string - a partial URI (the path without a query string)
     *
     * @example
     * <pre>
     * "/application/"
     * </pre>
     */
    public function getApplicationBaseUri() {

        // TODO: Move to application as this uri is not a property of the request.

        static $baseUri;
        if (!$baseUri) {
            if (isSet($_SERVER['APP_BASE_URI'])) {
                $baseUri = $_SERVER['APP_BASE_URI'];
            }
            else {
                $baseUri = Config::getDefault()->get('app.base-uri', false);
                if ($baseUri === false)
                    throw new RuntimeException('Missing application base URI configuration: either $_SERVER["APP_BASE_URI"] or config("app.base-uri") needs to be configured.');
            }
            !strStartsWith($baseUri, '/') && $baseUri  = '/'.$baseUri;
            !strEndsWith  ($baseUri, '/') && $baseUri .= '/';
        }
        return $baseUri;
    }


    /**
     * Return the query string of the current url.
     *
     * @return string
     *
     * @example
     * <pre>
     * "key1=value1&key2=value2"
     * </pre>
     */
    public function getQueryString() {
        // The variable $_SERVER['QUERY_STRING'] is set by the server and can differ, e.g. it might hold additional
        // parameters or it might be empty (nginx).

        if (isSet($_SERVER['QUERY_STRING']) && strLen($_SERVER['QUERY_STRING'])) {
            $query = $_SERVER['QUERY_STRING'];
        }
        else {
            $query = strRightFrom($_SERVER['REQUEST_URI'], '?');
        }
        return $query;
    }


    /**
     * Return the remote IP address the current request is made from.
     *
     * @return string - IP address
     */
    public function getRemoteAddress() {
        return $_SERVER['REMOTE_ADDR'];
    }


    /**
     * Return the remote host name the current request is made from.
     *
     * @return string - host name
     */
    public function getRemoteHostname() {
        static $hostname = null;
        if (!$hostname) {
            $hostname = getHostByAddr($this->getRemoteAddress());
        }
        return $hostname;
    }


    /**
     * Gibt den Wert des 'X-Forwarded-For'-Headers des aktuellen Requests zurueck.
     *
     * @return string|null - Wert (ein oder mehrere IP-Adressen oder Hostnamen) oder NULL, wenn der Header nicht gesetzt ist
     */
    public function getForwardedRemoteAddress() {
        return $this->getHeaderValue(array('X-Forwarded-For', 'X-UP-Forwarded-For'));
    }


    /**
     * Return the content of the current request (the request body).
     *
     * @return string|null - Request body for POST requests or NULL otherwise. If the "Content-Type" header of a POST request
     *                       is "multipart/form-data" (a file upload) a string with the posted file's information is returned.
     */
    public function getContent() {
        static $content = null;
        static $read    = false;

        if (!$read) {
            if ($this->isPost()) {
                if ($this->getContentType() != 'multipart/form-data') {
                    $content = file_get_contents('php://input');
                }
                else {
                    // php://input is not available with enctype="multipart/form-data"
                    if ($_POST)
                        $content = '$_POST => '.print_r($_POST, true)."\n";
                    $content .= '$_FILES => '.print_r($_FILES, true);
                }
            }
            $read = true;
        }
        return $content;
    }


    /**
     * Return the "Content-Type" header of the request. If multiple "Content-Type" headers have been transmitted the first
     * one is returned.
     *
     * @return string|null - "Content-Type" header or NULL if no "Content-Type" header was transmitted.
     */
    public function getContentType() {
        $contentType = $this->getHeaderValue('Content-Type');

        if ($contentType) {
            $headers     = explode(',', $contentType, 2);
            $contentType = array_shift($headers);

            $values      = explode(';', $contentType, 2);
            $contentType = trim(array_shift($values));
        }
        return $contentType;
    }


    /**
     * Whether or not an HTTP session was started during the current request. Not whether the session is still open (active).
     *
     * @return bool
     */
    public function isSession() {
        return (session_id() !== '');
    }


    /**
     * Gibt die aktuelle HttpSession zurueck. Existiert noch keine Session, wird eine erzeugt.
     *
     * @return HttpSession
     */
    public function getSession() {
        return Singleton::getInstance(HttpSession::class, $this);
    }


    /**
     * Return the session id transmitted by the client with the request (not the id sent to the client with the reponse).
     *
     * @return string
     */
    public function getSessionId() {
        $name = session_name();

        if (PHP::ini_get_bool('session.use_cookies')) {
            if (isSet($_COOKIE[$name]))
                return $_COOKIE[$name];
        }
        if (PHP::ini_get_bool('session.use_trans_sid') && !PHP::ini_get_bool('session.use_only_cookies')) {
            if (isSet($_REQUEST[$name]))
                return $_REQUEST[$name];
        }
        return '';
    }


    /**
     * Whether or not a session id was transmitted with the request in a legal way. An illegal way would be for example a
     * URL based session id when the php.ini setting 'session.use_trans_sid' is not enabled.
     *
     * @return bool
     */
    public function hasSessionId() {
        $name = session_name();

        if (PHP::ini_get_bool('session.use_cookies')) {
            if (isSet($_COOKIE[$name]))
                return true;
        }
        if (PHP::ini_get_bool('session.use_trans_sid') && !PHP::ini_get_bool('session.use_only_cookies')) {
            if (isSet($_REQUEST[$name]))
                return true;
        }
        return false;
    }


    /**
     * Destroy the current session and it's data.
     */
    public function destroySession() {
        if (session_status() == PHP_SESSION_ACTIVE) {
            // unset all session variables
            $_SESSION = [];

            // delete the session cookie
            if (PHP::ini_get_bool('session.use_cookies')) {
                $params = session_get_cookie_params();
                setCookie($name=session_name(), $value='', $expire=time()-1*DAY, $params['path'    ],
                                                                                 $params['domain'  ],
                                                                                 $params['secure'  ],
                                                                                 $params['httponly']);
            }
            session_destroy();                      // TODO: check if SID is reset
        }
    }


    /**
     * Gibt den Wert des angegebenen Headers zurueck.  Wurden mehrere Header dieses Namens uebertragen,
     * wird der Wert des ersten uebertragenen Headers zurueckgegeben.
     *
     * @param  string $name - Name des Headers
     *
     * @return string|null - Wert oder NULL, wenn kein Header dieses Namens uebertragen wurde
     */
    public function getHeader($name) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));

        $headers = $this->getHeaders($name);
        return array_shift($headers);
    }


    /**
     * Return the specified headers as an associative array of header values (in transmitted order).
     *
     * @param  string|string[] $names [optional] - one or more header names; without a name all headers are returned
     *
     * @return array - associative array of header values
     */
    public function getHeaders($names = []) {
        if (is_string($names)) $names = [$names];
        elseif (is_array($names)) {
            foreach ($names as $name) {
                if (!is_string($name)) throw new IllegalTypeException('Illegal argument type in argument $names: '.getType($name));
            }
        }
        else throw new IllegalTypeException('Illegal type of parameter $names: '.getType($names));

        // read all headers once
        static $headers = null; if (!$headers) {
            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
            }
            else {
                // TODO: check $_FILES array

                $headers = [];
                foreach ($_SERVER as $key => $value) {
                    if (subStr($key, 0, 5) == 'HTTP_') {
                        $key = subStr($key, 5);
                        if ($key != 'DNT') {
                            $key = str_replace(' ', '-', ucWords(str_replace('_', ' ', strToLower($key))));
                        }
                        $headers[$key] = $value;
                    }
                }
                if (isSet($_SERVER['CONTENT_TYPE'  ])) $headers['Content-Type'  ] = $_SERVER['CONTENT_TYPE'  ];
                if (isSet($_SERVER['CONTENT_LENGTH'])) $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
                if (isSet($_SERVER['CONTENT_MD5'   ])) $headers['Content-MD5'   ] = $_SERVER['CONTENT_MD5'   ];
            }

            if (!isSet($headers['Authorization'])) {
                if (isSet($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                    $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
                }
                elseif (isSet($_SERVER['PHP_AUTH_USER'])) {
                    $passwd = isSet($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                    $headers['Authorization'] = 'Basic '. base64_encode($_SERVER['PHP_AUTH_USER'].':'.$passwd);
                }
                elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                    $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
                }
            }
        }

        // return all or just the specified headers
        if (!$names)
            return $headers;
        return array_intersect_ukey($headers, array_flip($names), 'strCaseCmp');
    }


    /**
     * Return the value of the specified header. If multiple headers are specified or multiple headers have been
     * transmitted, return all values as a comma-separated list (in transmission order).
     *
     * @param  string|string[] $names - one or more header names
     *
     * @return string|null - value or NULL if no such headers have been transmitted.
     */
    public function getHeaderValue($names) {
        if (is_string($names))
            $names = array($names);
        elseif (is_array($names)) {
            foreach ($names as $name)
                if (!is_string($name)) throw new IllegalTypeException('Illegal argument type in argument $names: '.getType($name));
        }
        else                         throw new IllegalTypeException('Illegal type of parameter $names: '.getType($names));

        $headers = $this->getHeaders($names);
        if ($headers)
            return join(',', $headers);

        return null;
    }


    /**
     * Gibt die einzelnen Werte aller angegebenen Header als Array zurueck (in der uebertragenen Reihenfolge).
     *
     * @param  string|string[] $names - ein oder mehrere Headernamen
     *
     * @return array - Werte
     */
    public function getHeaderValues($names) {
        if (is_string($names))
            $names = array($names);
        elseif (is_array($names)) {
            foreach ($names as $name)
                if (!is_string($name)) throw new IllegalTypeException('Illegal argument type in argument $names: '.getType($name));
        }
        else                         throw new IllegalTypeException('Illegal type of parameter $names: '.getType($names));

        $headers = $this->getHeaders($names);
        if ($headers)
            return array_map('trim', explode(',', join(',', $headers)));

        return $headers; // empty array;
    }


    /**
     * Gibt den im Request-Context unter dem angegebenen Schluessel gespeicherten Wert zurueck oder NULL,
     * wenn unter diesem Schluessel kein Wert existiert.
     *
     * @param  string $key - Schluessel, unter dem der Wert im Context gespeichert ist
     *
     * @return mixed - der gespeicherte Wert oder NULL
     */
    public function &getAttribute($key) {
        if (isSet($this->attributes[$key]))
            return $this->attributes[$key];

        $value = null;
        return $value;    // Referenz auf NULL
    }


    /**
     * Gibt alle im Request-Context gespeicherten Werte zurueck.
     *
     * @return array - Werte-Array
     */
    public function getAttributes() {
        return $this->attributes;
    }


    /**
     * Store a value in the <tt>Request</tt> context. Can be used to transfer data from controllers or <tt>Action</tt>s to views.
     *
     * @param  string $key   - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed  $value - der zu speichernde Wert
     */
    public function setAttribute($key, &$value) {
        $this->attributes[$key] = $value;
    }


    /**
     * Loescht die Werte mit den angegebenen Schluesseln aus dem Request-Context. Es koennen mehrere Schluessel
     * angegeben werden.
     *
     * @param  string $key - Schluessel des zu loeschenden Wertes
     */
    public function removeAttributes($key /*, $key2, $key3 ...*/) {
        foreach (func_get_args() as $key) {
            unset($this->attributes[$key]);
        }
    }


    /**
     * Setzt einen Cookie mit den angegebenen Daten.
     *
     * @param  string $name            - Name des Cookies
     * @param  mixed  $value           - der zu speichernde Wert (wird zu String gecastet)
     * @param  int    $expires         - Lebenszeit des Cookies (0: bis zum Schliessen des Browsers)
     * @param  string $path [optional] - Pfad, fuer den der Cookie gueltig sein soll (default: whole domain)
     */
    public function setCookie($name, $value, $expires = 0, $path = null) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));
        if ($expires < 0)      throw new InvalidArgumentException('Invalid argument $expires: '.$expires);

        $value = (string)$value;

        if ($path === null)
            $path = $this->getApplicationBaseUri();

        if (!is_string($path)) throw new IllegalTypeException('Illegal type of parameter $path: '.getType($path));

        setCookie($name, $value, $expires, $path);
    }


    /**
     * Ob der User, der den Request ausgeloest hat, Inhaber der angegebenen Rolle(n) ist.
     *
     * @param  string $roles - Rollenbezeichner
     *
     * @return bool
     */
    public function isUserInRole($roles) {
        if (!is_string($roles)) throw new IllegalTypeException('Illegal type of parameter $roles: '.getType($roles));

        // Module holen
        $module = $this->getAttribute(MODULE_KEY);
        if (!$module) throw new RuntimeException('You can not call '.__METHOD__.'() in this context');

        // RoleProcessor holen ...
        $processor = $module->getRoleProcessor();
        if (!$processor) throw new RuntimeException('You can not call '.__METHOD__.'() without configuring a RoleProcessor');

        // ... und Aufruf weiterreichen
        return $processor->isUserInRole($this, $roles);
    }


    /**
     * Gibt die Error-Message fuer den angegebenen Schluessel zurueck.  Ohne Schluessel wird die erste
     * vorhandene Error-Message zurueckgegeben.
     *
     * @param  string $key [optional] - Schluessel der Error-Message
     *
     * @return string|null - Error-Message
     */
    public function getActionError($key = null) {
        $errors = &$this->getAttribute(ACTION_ERRORS_KEY);

        if ($key === null) {       // die erste zurueckgeben
            if ($errors) {
                foreach ($errors as $error)
                    return $error;
            }
        }                          // eine bestimmte zurueckgeben
        elseif (isSet($errors[$key])) {
            return $errors[$key];
        }
        return null;
    }


    /**
     * Gibt alle vorhandenen Error-Messages zurueck.
     *
     * @return array - Error-Messages
     */
    public function getActionErrors() {
        $errors = &$this->getAttribute(ACTION_ERRORS_KEY);

        if ($errors === null)
            $errors = [];

        return $errors;
    }


    /**
     * Ob unter dem angegebenen Schluessel eine Error-Message existiert.  Ohne Angabe eines Schluessel
     * wird geprueft, ob eine beliebige Error-Message existiert.
     *
     * @param  string $key [optional] - Schluessel
     *
     * @return bool
     */
    public function isActionError($key = null) {
        if ($key !== null) {
            return ($this->getActionError($key) !== null);
        }
        return (sizeOf($this->getAttribute(ACTION_ERRORS_KEY)) > 0);
    }


    /**
     * Setzt fuer den angegebenen Schluessel eine Error-Message. Ist Message NULL, wird die Message mit
     * dem angegebenen Schluessel aus dem Request geloescht.
     *
     * @param  string $key     - Schluessel der Error-Message
     * @param  string $message - Error-Message
     */
    public function setActionError($key, $message) {
        if (is_null($message)) {
            if (isSet($this->attributes[ACTION_ERRORS_KEY][$key]))
                unset($this->attributes[ACTION_ERRORS_KEY][$key]);
        }
        elseif (is_string($message)) {
            $this->attributes[ACTION_ERRORS_KEY][$key] = $message;
        }
        else {
            throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));
        }
    }


    /**
     * Loescht Error-Messages aus dem Request.
     *
     * @param  string $key - die Schluessel der zu loeschenden Werte (ohne Angabe werden alle Error-Messages geloescht)
     *
     * @return array - die geloeschten Error-Messages
     *
     * @todo   Error-Messages auch aus der Session loeschen
     */
    public function removeActionErrors(/*$key1, $key2, $key3 ...*/) {
        $dropped = [];
        $args = func_get_args();

        if ($args) {
            foreach ($args as $key => $value) {
                if ($error = $this->getActionError($value)) {
                    $dropped[$value] = $error;
                    $this->setActionError($value, null);
                }
            }
            return $dropped;
        }

        $dropped = $this->getActionErrors();
        unset($this->attributes[ACTION_ERRORS_KEY]);
        return $dropped;
    }


    /**
     * Gibt das diesem Request zugeordnete ActionMapping zurueck.
     *
     * @return ActionMapping|null - Mapping oder NULL, wenn die Request-Instance ausserhalb des Struts-Frameworks benutzt wird.
     */
    final public function getMapping() {
        return $this->getAttribute(ACTION_MAPPING_KEY);
    }


    /**
     * Gibt das diesem Request zugeordnete Struts-{@link Module} zurueck.
     *
     * @return Module - Module oder NULL, wenn die Request-Instance ausserhalb des Struts-Frameworks benutzt wird.
     */
    final public function getModule() {
        return $this->getAttribute(MODULE_KEY);
    }


    /**
     * Reject serialization of Request instances.
     */
    final public function __sleep() {
        throw new IllegalStateException('You must not serialize a '.get_class($this));
    }


    /**
     * Reject de-serialization of Request instances.
     */
    final public function __wakeUp() {
        throw new IllegalStateException('You must not deserialize a '.get_class($this));
    }


    /**
     * Return a human-readable version of this request.
     *
     * @return string
     */
    public function __toString() {
        // request
        $string = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL'].NL;

        // headers
        $headers = $this->getHeaders() ?: [];

        $maxLen  = 0;
        foreach ($headers as $key => $value) {
            $maxLen = max(strLen($key), $maxLen);
        }

        $maxLen++; // add a char for ':'
        foreach ($headers as $key => $value) {
            $string .= str_pad($key.':', $maxLen).' '.$value.NL;
        }

        // content (body)
        if ($this->isPost()) {
            $content = $this->getContent();
            if (strLen($content))
                $string .= NL.$content.NL;
        }

        return $string;
    }
}
