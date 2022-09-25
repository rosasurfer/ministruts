<?php
namespace rosasurfer\ministruts;

use rosasurfer\config\Config;
use rosasurfer\core\Singleton;
use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;
use rosasurfer\net\NetTools;

use function rosasurfer\ini_get_bool;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\DAY;
use const rosasurfer\NL;
use function rosasurfer\strCompareI;


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
            //$name  = str_replace(['.', ' '], '_', $name);                             // replace as the PHP implementation does
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
     * Return the HTTP methode of the request.
     *
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }


    /**
     * Whether or not the request is a GET request.
     *
     * @return bool
     */
    public function isGet() {
        return ($this->method == 'GET');
    }


    /**
     * Whether or not the request is a POST request.
     *
     * @return bool
     */
    public function isPost() {
        return ($this->method == 'POST');
    }


    /**
     * Whether or not the request was made over a secure channel (HTTPS).
     *
     * @return bool
     */
    public function isSecure() {
        return !empty($_SERVER['HTTPS']) && !strCompareI($_SERVER['HTTPS'], 'off');
    }


    /**
     * Return the single $_REQUEST parameter with the specified name. If multiple $_REQUEST parameters with that name have
     * been transmitted, the last one is returned. If an array of $_REQUEST parameters with that name have been transmitted
     * they are ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string|null - value or NULL if no such $_REQUEST parameter has been transmitted
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
     * Return an array of $_REQUEST parameters with the specified name. If a single $_REQUEST parameter with that name was
     * transmitted it is ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string[] - values or an empty array if no such array of $_REQUEST parameters have been transmitted
     */
    public function getParameters($name) {
        if (key_exists($name, $_REQUEST)) {
            $value = $_REQUEST[$name];
            if (is_array($value))
                return $value;
        }
        return [];
    }


    /**
     * Return the single $_GET parameter with the specified name. If multiple $_GET parameters with that name have been
     * transmitted, the last one is returned. If an array of $_GET parameters with that name have been transmitted they are
     * ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string|null - value or NULL if no such $_GET parameter has been transmitted
     */
    public function getGetParameter($name) {
        if (isSet($_GET[$name])) {
            $value = $_GET[$name];
            if (!is_array($value))
                return $value;
        }
        return null;
    }


    /**
     * Return an array of $_GET parameters with the specified name. If a single $_GET parameter with that name was
     * transmitted it is ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string[] - values or an empty array if no such array of $_GET parameters have been transmitted
     */
    public function getGetParameters($name) {
        if (isSet($_GET[$name])) {
            $value = $_GET[$name];
            if (is_array($value))
                return $value;
        }
        return [];
    }


    /**
     * Return the single $_POST parameter with the specified name. If multiple $_POST parameters with that name have been
     * transmitted, the last one is returned. If an array of $_POST parameters with that name have been transmitted they are
     * ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string|null - value or NULL if no such $_POST parameter has been transmitted
     */
    public function getPostParameter($name) {
        if (isSet($_POST[$name])) {
            $value = $_POST[$name];
            if (!is_array($value))
                return $value;
        }
        return null;
    }


    /**
     * Return an array of $_POST parameters with the specified name. If a single $_POST parameter with that name was
     * transmitted it is ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string[] - values or an empty array if no such array of $_POST parameters have been transmitted
     */
    public function getPostParameters($name) {
        if (isSet($_POST[$name])) {
            $value = $_POST[$name];
            if (is_array($value))
                return $value;
        }
        return [];
    }


    /**
     * Return an object-oriented representation of the uploaded files. The broken PHP array structure of uploaded files is
     * converted to regular file arrays.
     *
     * @TODO: convert file data to {@link UploadedFile} instances
     *
     * @return array - associative array of files
     */
    public function getFiles() {
        static $files;
        if (!isSet($files)) {
            $normalizeLevel = null;
            $normalizeLevel = function(array $file) use (&$normalizeLevel) {
                if (isSet($file['name']) && is_array($file['name'])) {
                    $properties = \array_keys($file);
                    $normalized = [];
                    foreach (\array_keys($file['name']) as $name) {
                        foreach ($properties as $property) {
                            $normalized[$name][$property] = $file[$property][$name];
                        }
                        $normalized[$name] = $normalizeLevel($normalized[$name]);
                    }
                    $file = $normalized;
                }
                return $file;
            };
            $files = [];
            if (isSet($_FILES)) {
                foreach ($_FILES as $key => $file) {
                    $files[$key] = $normalizeLevel($file);
                }
            }
        }
        return $files;
    }


    /**
     * Return the host name the request was made to.
     *
     * @return string - host name
     *
     * @example
     * <pre>
     * $request->getUrl():       "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * $request->getHostname():  "a.domain.tld"
     * </pre>
     */
    public function getHostname() {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $httpHost = strToLower(trim($_SERVER['HTTP_HOST']));    // nginx doesn't set $_SERVER[SERVER_NAME]
            if (strLen($httpHost))                                  // automatically to $_SERVER[HTTP_HOST]
                return $httpHost;
        }
        return $_SERVER['SERVER_NAME'];
    }


    /**
     * Return the root URL of the server the request was made to. This value always ends with a slash "/".
     *
     * @return string - root URL: protocol + host name + port
     *
     * @example
     * <pre>
     * $request->getUrl():      "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * $request->getHostUrl():  "http://a.domain.tld/"
     * </pre>
     */
    public function getHostUrl() {
        if (!$this->hostUrl) {
            $protocol = $this->isSecure() ? 'https' : 'http';
            $host     = $this->getHostname();
            $port     = ':'.$_SERVER['SERVER_PORT'];
            if ($protocol.$port=='http:80' || $protocol.$port=='https:443')
                $port = '';
            $this->hostUrl = $protocol.'://'.$host.$port.'/';
        }
        return $this->hostUrl;
    }


    /**
     * Return the full URL of the request.
     *
     * @return string - full URL: protocol + host name + port + path + query string
     *                  All URLs in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getUrl() {
        return strLeft($this->getHostUrl(), -1).$this->getUri();
    }


    /**
     * Return the URI of the request (the value in the first line of the HTTP protocol). This value always starts
     * with a slash "/".
     *
     * @return string - URI: path + query string
     *                  All URLs in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * $request->getUrl():  "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * $request->getUri():  "/path/application/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getUri() {
        return $_SERVER['REQUEST_URI'];
    }


    /**
     * Return the path fragment of the request's URI. This value always starts with a slash "/".
     *
     * @return string - path without query string
     *                  All URLs in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * $request->getUrl():   "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * $request->getPath():  "/path/application/module/foo/bar.html"
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
     * Return the root URL of the application. This value always ends with a slash "/".
     *
     * @return string - URL: protocol + host name + port + application_base_uri
     *
     * @example
     * <pre>
     * $request->getUrl():              "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * $request->getApplicationUrl():   "http://a.domain.tld/path/application/"
     * </pre>
     */
    public function getApplicationUrl() {
        // TODO: Move to application as this is not a property of the request.
        return strLeft($this->getHostUrl(), -1).$this->getApplicationBaseUri();
    }


    /**
     * Return the request's URI relative to the application's base URL. This value always starts with a slash "/".
     *
     * @return string - URI: slash + module prefix + path + query string
     *                  All URLs in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * $request->getUrl():                    "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * $request->getApplicationRelativeUri(): "/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getApplicationRelativeUri() {
        // TODO: Move to application as this is not a property of the request.
        return strRightFrom($this->getUri(), $this->getApplicationBaseUri()).'/';
    }


    /**
     * Return the request's path fragment relative to the application's base URL. This value always starts with
     * a slash "/".
     *
     * @return string - path fragment: slash + module prefix + path (without query string)
     *                  All URLs in this framework are virtual, there is no "path info" as such.
     * @example
     * <pre>
     * $request->getUrl():                      "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * $request->getApplicationRelativePath()   "/module/foo/bar.html"
     * </pre>
     */
    public function getApplicationRelativePath() {
        // TODO: Move to application as this is not a property of the request.
        return '/'.strRightFrom($this->getPath(), $this->getApplicationBaseUri());
    }


    /**
     * Return the application's base URI. The value always starts and ends with a slash "/".
     *
     * @return string - a partial URI (application path without module prefix or query string)
     *
     * @example
     * <pre>
     * $request->getUrl():                  "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * $request->getApplicationBaseUri():   "/path/application/"
     * </pre>
     */
    public function getApplicationBaseUri() {
        // TODO: Move to application as this is not a property of the request.
        static $baseUri;
        if (!$baseUri) {
            if (isSet($_SERVER['APP_BASE_URI'])) {
                $baseUri = $_SERVER['APP_BASE_URI'];
            }
            else {
                $baseUri = Config::getDefault()->get('app.base-uri', false);
                if (!$baseUri) throw new RuntimeException('Unknown application base URI, either $_SERVER["APP_BASE_URI"] or $config["app.base-uri"] needs to be configured.');
            }
            !strStartsWith($baseUri, '/') && $baseUri  = '/'.$baseUri;
            !strEndsWith  ($baseUri, '/') && $baseUri .= '/';
        }
        return $baseUri;
    }


    /**
     * Return the query string of the request's URL.
     *
     * @return string
     *
     * @example
     * <pre>
     * $request->getUrl():          "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     * $request->getQueryString():  "key=value"
     * </pre>
     */
    public function getQueryString() {
        // The variable $_SERVER['QUERY_STRING'] is set by the server and can differ from the transmitted query string.
        // It might hold additional parameters added by the server or it might be empty (e.g. on a mis-configured nginx).

        if (isSet($_SERVER['QUERY_STRING']) && strLen($_SERVER['QUERY_STRING'])) {
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
    public function getRemoteAddress() {
        return $_SERVER['REMOTE_ADDR'];
    }


    /**
     * Return the name of the host the request was made from.
     *
     * @return string - host name
     */
    public function getRemoteHostname() {
        static $hostname = null;
        if (!$hostname) {
            /** @var string $hostname */
            $hostname = NetTools::getHostByAddress($this->getRemoteAddress());
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
     * Return the content of the request (the body). For file uploads the method returns not the real binary content.
     * Instead it returns the available meta infos.
     *
     * @return string - request body or meta infos
     */
    public function getContent() {
        static $content  = '';
        static $isRead = false;

        if (!$isRead) {
            if ($this->getContentType() == 'multipart/form-data') {
                // file upload
                if ($_POST) {                                                           // php://input is not available with
                    $content = '$_POST => '.print_r($_POST, true).NL;                   // enctype="multipart/form-data"
                    // TODO: we should limit excessive variable values to 1KB
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
     * @return string|null - "Content-Type" header or NULL if no "Content-Type" header was transmitted.
     */
    public function getContentType() {
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
     * Gibt die aktuelle HttpSession zurueck. Existiert noch keine Session, wird eine erzeugt.
     *
     * @param  bool $suppressHeadersAlreadySentError [optional] - whether or not to suppress "headers already sent" errors
     *                                                            (default: no)
     * @return HttpSession
     */
    public function getSession($suppressHeadersAlreadySentError = false) {
        return Singleton::getInstance(HttpSession::class, $this, $suppressHeadersAlreadySentError);
    }


    /**
     * Whether or not an HTTP session was started during the request. Not whether the session is still open (active).
     *
     * @return bool
     */
    public function isSession() {
        return (session_id() !== '');
    }


    /**
     * Whether or not a session attribute of the specified name exists. If no session exists none is started.
     *
     * @param  string $key - key
     *
     * @return bool
     */
    public function isSessionAttribute($key) {
        if ($this->isSession() || $this->hasSessionId())
            return $this->getSession()->isAttribute($key);
        return false;
    }


    /**
     * Return the session id transmitted with the request (not the id sent with the response; which may differ).
     *
     * @return string
     */
    public function getSessionId() {
        $name = session_name();

        if (ini_get_bool('session.use_cookies'))
            if (isSet($_COOKIE[$name]))
                return $_COOKIE[$name];

        if (!ini_get_bool('session.use_only_cookies'))
            if (isSet($_REQUEST[$name]))
                return $_REQUEST[$name];

        return '';
    }


    /**
     * Whether or not a valid session id was transmitted with the request. An invalid id is a URL based session id when the
     * php.ini setting 'session.use_only_cookies' is enabled.
     *
     * @return bool
     */
    public function hasSessionId() {
        $name = session_name();

        if (ini_get_bool('session.use_cookies'))
            if (isSet($_COOKIE[$name]))
                return true;

        if (!ini_get_bool('session.use_only_cookies'))
            if (isSet($_REQUEST[$name]))
                return true;

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
            if (ini_get_bool('session.use_cookies')) {
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
        return \array_shift($headers);
    }


    /**
     * Return the specified headers as an associative array of header values (in transmitted order).
     *
     * @param  string|string[] $names [optional] - one or more header names (default: all headers)
     *
     * @return array - associative array of header values
     */
    public function getHeaders($names = []) {
        if (is_string($names)) {
            $names = [$names];
        }
        elseif (is_array($names)) {
            foreach ($names as $name) {
                if (!is_string($name)) throw new IllegalTypeException('Illegal argument type in argument $names: '.getType($name));
            }
        }
        else throw new IllegalTypeException('Illegal type of parameter $names: '.getType($names));

        // read all headers once
        static $headers = null; if ($headers === null) {
            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                // TODO: Apache skips REDIRECT_* headers
            }
            else {
                static $fixHeaderNames = ['CDN'=>1, 'DNT'=>2, 'X-CDN'=>3];
                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    while (subStr($name, 0, 9) == 'REDIRECT_') {
                        $name = subStr($name, 9);
                        if (isSet($_SERVER[$name]))
                            continue 2;
                    }
                    if (subStr($name, 0, 5) == 'HTTP_') {
                        $name = subStr($name, 5);
                        if (!isSet($fixHeaderNames[$name]))
                            $name = str_replace(' ', '-', ucWords(str_replace('_', ' ', strToLower($name))));
                        $headers[$name] = $value;
                    }
                }
            }

            if (!isSet($headers['Authorization'])) {
                if (isSet($_SERVER['PHP_AUTH_USER'])) {
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
        return \array_intersect_ukey($headers, \array_flip($names), 'strCaseCmp');
    }


    /**
     * Return the value of the specified header. If multiple headers are specified or multiple headers have been
     * transmitted, return all values as a comma-separated list (in transmission order).
     *
     * @param  string|string[] $names - one or more header names
     *
     * @return string|null - value or NULL if no such headers have been transmitted
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
            return \array_map('trim', explode(',', join(',', $headers)));

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
    public function getAttribute($key) {
        if (key_exists($key, $this->attributes))
            return $this->attributes[$key];
        return null;
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
    public function setAttribute($key, $value) {
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
     * Return the ActionMessage for the specified key or the first ActionMessage if no key was given.
     *
     * @param  string $key [optional]
     *
     * @return string|null - ActionMessage
     */
    public function getActionMessage($key = null) {
        $messages = $this->getAttribute(ACTION_MESSAGES_KEY);

        if ($key === null) {                            // return the first one
            if ($messages) {
                foreach ($messages as $message) return $message;
            }
        }
        elseif (key_exists($key, $messages)) {          // return the specified one
            return $messages[$key];
        }
        return $this->getActionError($key);             // look-up separately stored ActionErrors
    }


    /**
     * Return all existing ActionMessages, including ActionErrors.
     *
     * @return array
     */
    public function getActionMessages() {
        $messages = $this->getAttribute(ACTION_MESSAGES_KEY);
        if ($messages === null)
            $messages = [];
        $errors = $this->getActionErrors();

        return \array_merge($messages, $errors);
    }


    /**
     * Whether or not an ActionMessage exists for one of the specified keys, or for any key if no key was given.
     *
     * @param  string|string[] $keys [optional] - message keys
     *
     * @return bool
     */
    public function isActionMessage($keys = null) {
        $messages = $this->getAttribute(ACTION_MESSAGES_KEY);
        if (!$messages)
            return $this->isActionError($keys);

        if (is_string($keys))
            $keys = [$keys];

        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (key_exists($key, $messages)) return true;
            }
            if ($keys)
                return $this->isActionError($keys);
            $keys = null;
        }

        if (is_null($keys))
            return true;
        throw new IllegalTypeException('Illegal type of parameter $keys: '.getType($keys));
    }


    /**
     * Store or delete an ActionMessage for the specified key.
     *
     * @param  string $key     - message key
     * @param  string $message - message; if NULL the message for the specified key is deleted
     *                           (an ActionError with the same key is not deleted)
     */
    public function setActionMessage($key, $message) {
        if (is_null($message)) {
            unset($this->attributes[ACTION_MESSAGES_KEY][$key]);
        }
        elseif (is_string($message)) {
            $this->attributes[ACTION_MESSAGES_KEY][$key] = $message;
        }
        else throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));
    }


    /**
     * Delete the ActionMessages with the specified keys.
     *
     * @param  string $key - zero or more message keys to delete; without a key all ActionMessages are deleted
     *                       (ActionErrors with the same keys are not deleted
     *
     * @return array - the deleted ActionMessages
     */
    public function removeActionMessages(/*$key1, $key2, $key3...*/) {
        $messages = $this->getAttribute(ACTION_MESSAGES_KEY);

        if ($args = func_get_args()) {
            $dropped = [];
            foreach ($args as $key) {
                if (isSet($messages[$key]))
                    $dropped[$key] = $messages[$key];
                unset($this->attributes[ACTION_MESSAGES_KEY][$key]);
            }
            return $dropped;
        }

        unset($this->attributes[ACTION_MESSAGES_KEY]);
        return $messages;
    }


    /**
     * Return the ActionError for the specified key or the first ActionError if no key was given.
     *
     * @param  string $key [optional]
     *
     * @return string|null - ActionError
     */
    public function getActionError($key = null) {
        $errors = $this->getAttribute(ACTION_ERRORS_KEY);

        if ($key === null) {                            // return the first one
            if ($errors) {
                foreach ($errors as $error) return $error;
            }
        }
        elseif (key_exists($key, $errors)) {            // return the specified one
            return $errors[$key];
        }
        return null;
    }


    /**
     * Return all existing ActionErrors.
     *
     * @return array
     */
    public function getActionErrors() {
        $errors = $this->getAttribute(ACTION_ERRORS_KEY);
        if ($errors === null)
            $errors = [];
        return $errors;
    }


    /**
     * Whether or not an ActionError exists for one of the specified keys or for any key if no key was given.
     *
     * @param  string|string[] $keys [optional] - error keys
     *
     * @return bool
     */
    public function isActionError($keys = null) {
        $errors = $this->getAttribute(ACTION_ERRORS_KEY);
        if (!$errors)
            return false;

        if (is_string($keys))
            $keys = [$keys];

        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (key_exists($key, $errors)) return true;
            }
            if ($keys)
                return false;
            $keys = null;
        }

        if (is_null($keys))
            return true;
        throw new IllegalTypeException('Illegal type of parameter $keys: '.getType($keys));
    }


    /**
     * Store or delete an ActionError for the specified key.
     *
     * @param  string $key     - error key
     * @param  string $message - error message; if NULL the error for the specified key is deleted
     */
    public function setActionError($key, $message) {
        if (is_null($message)) {
            unset($this->attributes[ACTION_ERRORS_KEY][$key]);
        }
        elseif (is_string($message)) {
            $this->attributes[ACTION_ERRORS_KEY][$key] = $message;
        }
        else throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));
    }


    /**
     * Delete the ActionErrors with the specified keys.
     *
     * @param  string $key - zero or more error keys to delete; without a key all ActionErrors are deleted
     *
     * @return array - the deleted ActionErrors
     */
    public function removeActionErrors(/*$key1, $key2, $key3...*/) {
        $errors = $this->getAttribute(ACTION_ERRORS_KEY);

        if ($args = func_get_args()) {
            $dropped = [];
            foreach ($args as $key) {
                if (isSet($errors[$key]))
                    $dropped[$key] = $errors[$key];
                unset($this->attributes[ACTION_ERRORS_KEY][$key]);
            }
            return $dropped;
        }

        unset($this->attributes[ACTION_ERRORS_KEY]);
        return $errors;
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

        $maxLen = 0;
        foreach ($headers as $key => $value) {
            $maxLen = max(strLen($key), $maxLen);
        }

        $maxLen++; // add a char for ':'
        foreach ($headers as $key => $value) {
            $string .= str_pad($key.':', $maxLen).' '.$value.NL;
        }

        // content (request body)
        $content = $this->getContent();
        if (strLen($content)) {
            $string .= NL.subStr($content, 0, 1024).NL;             // limit the request body to 1024 bytes
        }
        return $string;
    }
}
