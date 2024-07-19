<?php
namespace rosasurfer\ministruts;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\ErrorHandler;
use rosasurfer\core\exception\IllegalStateException;
use rosasurfer\core\exception\IllegalTypeException;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\net\NetTools;

use function rosasurfer\first;
use function rosasurfer\ini_get_bool;
use function rosasurfer\strCompareI;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\DAY;
use const rosasurfer\NL;


/**
 * Request
 *
 * An object representing the current HTTP request. Provides helper methods and an additional variables context with the
 * life-time of the request.
 */
class Request extends CObject {


    /** @var string */
    private $method;

    /** @var string */
    private $hostUrl;

    /** @var string */
    private $path;

    /** @var ?array - normalized structure of files uploaded with the request */
    private $files = null;

    /** @var array - additional variables context */
    private $attributes = [];


    /**
     * Constructor
     */
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];

        // issue: if $_SERVER['QUERY_STRING'] is empty (e.g. at times in nginx) PHP will not parse
        //        query parameters and it has to be done manually
        $query = $this->getQueryString();

        if (!$_GET && strlen($query)) {
            $this->parseQueryString($query);
        }
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
            $name  = trim(urldecode($parts[0])); if (!strlen($name)) continue;
            //$name  = str_replace(['.', ' '], '_', $name);                         // replace as the PHP implementation does
            $value = sizeof($parts)==1 ? '' : urldecode($parts[1]);

            // TODO: process multi-dimensional arrays

            if (($open=strpos($name, '[')) && ($close=strpos($name, ']')) && strlen($name)==$close+1) {
                // name is an array index
                $name = trim(substr($name, 0, $open));
                $key  = trim(substr($name, $open+1, $close-$open-1));

                if (!strlen($key)) {
                    $_GET[$name][] = $_REQUEST[$name][] = $value;
                }
                else {
                    $_GET[$name][$key]                                    = $value;
                    !isset($_POST[$name][$key]) && $_REQUEST[$name][$key] = $value; // GET must not over-write POST
                }
            }
            else {
                // name is not an array index
                $_GET[$name]                              = $value;
                !isset($_POST[$name]) && $_REQUEST[$name] = $value;                 // GET must not over-write POST
            }
        }
    }


    /**
     * Return the HTTP method of the request.
     *
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }


    /**
     * Whether the request is a GET request.
     *
     * @return bool
     */
    public function isGet() {
        return ($this->method == 'GET');
    }


    /**
     * Whether the request is a POST request.
     *
     * @return bool
     */
    public function isPost() {
        return ($this->method == 'POST');
    }


    /**
     * Whether the request is a result of an AJAX call.
     *
     * @return bool
     */
    public function isAjax() {
        static $isAjax;
        !isset($isAjax) && $isAjax = strCompareI($this->getHeader('X-Requested-With'), 'XMLHttpRequest');
        return $isAjax;
    }


    /**
     * Whether the request was made over a secure connection (HTTPS).
     *
     * @return bool
     */
    public function isSecure() {
        return !empty($_SERVER['HTTPS']) && !strCompareI($_SERVER['HTTPS'], 'off');
    }


    /**
     * Return the single $_REQUEST parameter with the specified name. If multiple $_REQUEST parameters with that name have
     * been transmitted, the last one is returned. A transmitted array of $_REQUEST parameters with that name is ignored.
     *
     * @param  string $name - parameter name
     *
     * @return ?string - value or NULL if no such $_REQUEST parameter has been transmitted
     */
    public function getParameter($name) {
        if (isset($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
            if (!is_array($value))
                return $value;
        }
        return null;
    }


    /**
     * Return an array of $_REQUEST parameters with the specified name. A single transmitted $_REQUEST parameter with that
     * name is ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string[] - values or an empty array if no such array of $_REQUEST parameters has been transmitted
     */
    public function getParameters($name) {
        if (\key_exists($name, $_REQUEST)) {
            $value = $_REQUEST[$name];
            if (is_array($value))
                return $value;
        }
        return [];
    }


    /**
     * Return the single $_GET parameter with the specified name. If multiple $_GET parameters with that name have been
     * transmitted, the last one is returned. A transmitted array of $_GET parameters with that name is ignored.
     *
     * @param  string $name - parameter name
     *
     * @return ?string - value or NULL if no such $_GET parameter has been transmitted
     */
    public function getGetParameter($name) {
        if (isset($_GET[$name])) {
            $value = $_GET[$name];
            if (!is_array($value))
                return $value;
        }
        return null;
    }


    /**
     * Return an array of $_GET parameters with the specified name. A single transmitted $_GET parameter with that name is
     * ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string[] - values or an empty array if no such array of $_GET parameters has been transmitted
     */
    public function getGetParameters($name) {
        if (isset($_GET[$name])) {
            $value = $_GET[$name];
            if (is_array($value))
                return $value;
        }
        return [];
    }


    /**
     * Return the single $_POST parameter with the specified name. If multiple $_POST parameters with that name have been
     * transmitted, the last one is returned. A transmitted array of $_POST parameters with that name is ignored.
     *
     * @param  string $name - parameter name
     *
     * @return ?string - value or NULL if no such $_POST parameter has been transmitted
     */
    public function getPostParameter($name) {
        if (isset($_POST[$name])) {
            $value = $_POST[$name];
            if (!is_array($value))
                return $value;
        }
        return null;
    }


    /**
     * Return an array of $_POST parameters with the specified name. A single transmitted $_POST parameter with that name
     * is ignored.
     *
     * @param  string $name - parameter name
     *
     * @return string[] - values or an empty array if no such array of $_POST parameters has been transmitted
     */
    public function getPostParameters($name) {
        if (isset($_POST[$name])) {
            $value = $_POST[$name];
            if (is_array($value))
                return $value;
        }
        return [];
    }


    /**
     * Return an object-oriented representation of the files uploaded with the request. The PHP array structure of $_FILES
     * is converted to normalized arrays.
     *
     * @return array - associative array of files
     *
     * @todo   Convert the returned arrays to instances of {@link UploadedFile}.
     */
    public function getFiles() {
        if (!isset($this->files)) {
            $normalizeLevel = null;                     // prevent Eclipse PDT validation error
            $normalizeLevel = function(array $file) use (&$normalizeLevel) {
                if (isset($file['name']) && is_array($file['name'])) {
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
     * @return ?array - array or NULL if no such file was uploaded
     *
     * @todo   Convert the returned array to an instance of {@link UploadedFile}.
     */
    public function getFile($name) {
        Assert::string($name);

        $files = $this->getFiles();

        if (isset($files[$name]))
            return $files[$name];
        return null;
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
    public function getHostname() {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $httpHost = strtolower(trim($_SERVER['HTTP_HOST']));    // nginx doesn't set $_SERVER[SERVER_NAME]
            if (strlen($httpHost))                                  // automatically to $_SERVER[HTTP_HOST]
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
     *  $request->getUrl();             // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getHostUrl();         // "http://a.domain.tld/"
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
     *
     * @example
     * <pre>
     *  $request->getUrl();         // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
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
     *
     * @example
     * <pre>
     *  $request->getUrl();         // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getUri();         // "/path/application/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getUri() {
        return $_SERVER['REQUEST_URI'];
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
     *  $request->getUrl();             // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getApplicationUrl();  // "http://a.domain.tld/path/application/"
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
     *
     * @example
     * <pre>
     *  $request->getUrl();                     // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getApplicationRelativeUri();  // "/module/foo/bar.html?key=value"
     * </pre>
     */
    public function getApplicationRelativeUri() {
        // TODO: Move to application as this is not a property of the request.
        return '/'.strRightFrom($this->getUri(), $this->getApplicationBaseUri());
    }


    /**
     * Return the request's path fragment relative to the application's base URL. This value always starts with
     * a slash "/".
     *
     * @return string - path fragment: slash + module prefix + path (without query string)
     *
     * @example
     * <pre>
     *  $request->getUrl();                     // "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     *  $request->getApplicationRelativePath(); // "/module/foo/bar.html"
     * </pre>
     */
    public function getApplicationRelativePath() {
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
    public function getApplicationBaseUri() {
        // TODO: Move to application as this is not a property of the request.
        static $baseUri;
        if (!isset($baseUri)) {
            $baseUri = $this->resolveBaseUriVar();

            if (!isset($baseUri)) {
                /** @var ConfigInterface $config */
                $config  = $this->di('config');
                $baseUri = $config->get('app.base-uri', false);
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
    private function resolveBaseUriVar() {
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
    public function getQueryString() {
        // The variable $_SERVER['QUERY_STRING'] is set by the server and can differ from the transmitted query string.
        // It might hold additional parameters added by the server or it might be empty (e.g. on a mis-configured nginx).

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
     * Return the value of a transmitted "X-Forwarded-For" header.
     *
     * @return ?string - header value (one or more ip addresses or hostnames) or NULL if the header was not transmitted
     */
    public function getForwardedRemoteAddress() {
        return $this->getHeaderValue(['X-Forwarded-For', 'X-UP-Forwarded-For']);
    }


    /**
     * Return the content of the request (the body). For file uploads the method doesn't return the real binary content.
     * Instead it returns available metadata.
     *
     * @return string - request body or metadata
     */
    public function getContent() {
        static $content  = '';
        static $isRead = false;

        if (!$isRead) {
            if ($this->getContentType() == 'multipart/form-data') {
                // file upload
                if ($_POST) {                                                   // php://input is not available with
                    $content = '$_POST => '.print_r($_POST, true).NL;           // enctype="multipart/form-data"
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
     * @return ?string - "Content-Type" header or NULL if no "Content-Type" header was transmitted
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
     * Return the current HTTP session object. If a session object does not yet exist, one is created.
     *
     * @return HttpSession
     */
    public function getSession() {
        return HttpSession::me();
    }


    /**
     * Whether an HTTP session was started during the request. Not whether the session is still open (active).
     *
     * @return bool
     */
    public function isSession() {
        return (session_id() !== '');
    }


    /**
     * Whether a session attribute of the specified name exists. If no session exists none is started.
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
     * Return the session id transmitted with the request (not the id sent with the response, which may differ).
     *
     * @return string
     */
    public function getSessionId() {
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
     * Whether a valid session id was transmitted with the request. An invalid id is a URL based session id when the php.ini
     * setting "session.use_only_cookies" is enabled.
     *
     * @return bool
     */
    public function hasSessionId() {
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
     */
    public function destroySession() {
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
    public function getHeader($name) {
        Assert::string($name);
        $headers = $this->getHeaders($name);
        return \array_shift($headers);
    }


    /**
     * Return all headers with the specified name as an associative array of header values (in transmitted order).
     *
     * @param  string|string[] $names [optional] - one or more header names (default: all headers)
     *
     * @return string[] - associative array of header values
     */
    public function getHeaders($names = []) {
        if (is_string($names)) {
            $names = [$names];
        }
        elseif (is_array($names)) {
            foreach ($names as $i => $name) {
                Assert::string($name, '$names['.$i.']');
            }
        }
        else throw new IllegalTypeException('Illegal type of parameter $names: '.gettype($names));

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
                    while (substr($name, 0, 9) == 'REDIRECT_') {
                        $name = substr($name, 9);
                        if (isset($_SERVER[$name]))
                            continue 2;
                    }
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $name = substr($name, 5);
                        if (!isset($fixHeaderNames[$name]))
                            $name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($name))));
                        $headers[$name] = $value;
                    }
                }
            }

            if (!isset($headers['Authorization'])) {
                if (isset($_SERVER['PHP_AUTH_USER'])) {
                    $passwd = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
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
     * Return a single value of all specified header(s). If multiple headers are specified or multiple headers have been
     * transmitted, return all values as one comma-separated value (in transmission order).
     *
     * @param  string|string[] $names - one or multiple header names
     *
     * @return ?string - value or NULL if no such headers have been transmitted
     */
    public function getHeaderValue($names) {
        if (is_string($names))
            $names = [$names];
        elseif (is_array($names)) {
            foreach ($names as $i => $name) {
                Assert::string($name, '$names['.$i.']');
            }
        }
        else throw new IllegalTypeException('Illegal type of parameter $names: '.gettype($names));

        $headers = $this->getHeaders($names);
        if ($headers)
            return join(',', $headers);
        return null;
    }


    /**
     * Return the values of all specified header(s) as an array (in transmission order).
     *
     * @param  string|string[] $names - one or multiple header names
     *
     * @return string[] - values or an empty array if no such headers have been transmitted
     */
    public function getHeaderValues($names) {
        if (is_string($names))
            $names = [$names];
        elseif (is_array($names)) {
            foreach ($names as $i => $name) {
                Assert::string($name, '$names['.$i.']');
            }
        }
        else throw new IllegalTypeException('Illegal type of parameter $names: '.gettype($names));

        $headers = $this->getHeaders($names);
        if (!$headers)
            return [];
        return \array_map('trim', explode(',', join(',', $headers)));
    }


    /**
     * Return a value stored in the request's variables context under the specified name.
     *
     * @param  string $name - attribute name
     *
     * @return mixed - attribute value or NULL if no value is stored under the specified name
     */
    public function getAttribute($name) {
        Assert::string($name);
        if (\key_exists($name, $this->attributes))
            return $this->attributes[$name];
        return null;
    }


    /**
     * Return all values stored in the request's variables context.
     *
     * @return array
     */
    public function getAttributes() {
        return $this->attributes;
    }


    /**
     * Store a value in the request's variables context. May be used to transfer data from controllers or {@link Action}s
     * to views.
     *
     * @param  string $name  - name under which the value is stored
     * @param  mixed  $value - value to store
     */
    public function setAttribute($name, $value) {
        Assert::string($name);
        $this->attributes[$name] = $value;
    }


    /**
     * Remove the value(s) with the specified name(s) from the request's variables context.
     *
     * @param  string ...$names - names of the values to remove
     */
    public function removeAttributes(...$names) {
        foreach ($names as $name) {
            unset($this->attributes[$name]);
        }
    }


    /**
     * Send a cookie.
     *
     * @param  string $name               - cookie name
     * @param  string $value              - cookie value
     * @param  int    $expires [optional] - timestamp when the cookie expires (default: when the browser is closed)
     * @param  string $path    [optional] - path the cookie will be available for (default: the application)
     */
    public function setCookie($name, $value, $expires = 0, $path = null) {
        Assert::string($name, '$name');
        Assert::string($value, '$value');
        Assert::int($expires, '$expires');
        Assert::nullOrString($path, '$path');

        if ($expires < 0) throw new InvalidArgumentException('Invalid argument $expires: '.$expires);

        if (!isset($path)) {
            $path = $this->getApplicationBaseUri();
        }
        \setcookie($name, $value, $expires, $path);
    }


    /**
     * Whether the current web user owns the specified role.
     *
     * @param  string $role - a single role identifier (not an expression)
     *
     * @return bool
     */
    public function isUserInRole($role) {
        Assert::string($role);

        /** @var ?Module $module */
        $module = $this->getAttribute(MODULE_KEY);
        if (!$module) throw new RuntimeException('Current Struts module not found');

        $processor = $module->getRoleProcessor();
        if (!$processor) throw new RuntimeException('No RoleProcessor configured for Struts module with prefix "'.$module->getPrefix().'"');

        return $processor->isUserInRole($this, $role);
    }


    /**
     * Return the stored ActionMessage for the specified key, or the first ActionMessage if no key was given.
     *
     * @param  string $key [optional]
     *
     * @return ?string - message
     */
    public function getActionMessage($key = null) {
        Assert::nullOrString($key);
        $messages = $this->getAttribute(ACTION_MESSAGES_KEY);

        if (!isset($key)) {                             // return the first one
            if ($messages)
                return first($messages);
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
    public function getActionMessages() {
        $messages = $this->getAttribute(ACTION_MESSAGES_KEY) ?: [];
        $errors = $this->getActionErrors();
        return \array_merge($messages, $errors);
    }


    /**
     * Whether an ActionMessage exists for one of the specified keys, or for any key if no key was given.
     *
     * @param  string|string[] $keys [optional] - message keys
     *
     * @return bool
     */
    public function isActionMessage($keys = null) {
        $messages = $this->getActionMessages();

        if (!isset($keys))
            return (bool) $messages;

        if (is_string($keys)) $keys = [$keys];
        else                  Assert::isArray($keys);

        if (!$keys)
            return (bool) $messages;

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
     */
    public function setActionMessage($key, $message) {
        if (!is_string($key) && !is_int($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.gettype($key));    // @phpstan-ignore-line
        Assert::nullOrString($message, '$message');

        if (!isset($message)) {
            unset($this->attributes[ACTION_MESSAGES_KEY][$key]);
        }
        else {
            $this->attributes[ACTION_MESSAGES_KEY][$key] = $message;
        }
    }


    /**
     * Remove the ActionMessage(s) with the specified key(s).
     *
     * @param  string ...$keys - zero or more message keys to remove; without a key all ActionMessages are removed
     *
     * @return string[] - the removed ActionMessages
     */
    public function removeActionMessages(...$keys) {
        $messages = $this->getAttribute(ACTION_MESSAGES_KEY) ?: [];
        $removed = [];

        foreach ($keys as $key) {
            Assert::string($key, '$keys');
            if (isset($messages[$key]))
                $removed[$key] = $messages[$key];
            unset($this->attributes[ACTION_MESSAGES_KEY][$key]);
        }
        if ($keys)
            return $removed;

        unset($this->attributes[ACTION_MESSAGES_KEY]);
        return $messages;
    }


    /**
     * Return the stored ActionError for the specified key, or the first ActionError if no key was given.
     *
     * @param  string $key [optional]
     *
     * @return ?string - message
     */
    public function getActionError($key = null) {
        Assert::nullOrString($key);
        $errors = $this->getAttribute(ACTION_ERRORS_KEY);

        if (!isset($key)) {                             // return the first one
            if ($errors)
                return first($errors);
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
    public function getActionErrors() {
        return $this->getAttribute(ACTION_ERRORS_KEY) ?: [];
    }


    /**
     * Whether an ActionError exists for one of the specified keys, or for any key if no key was given.
     *
     * @param  string|string[] $keys [optional] - error keys
     *
     * @return bool
     */
    public function isActionError($keys = null) {
        $errors = $this->getActionErrors();

        if (!isset($keys))
            return (bool) $errors;

        if (is_string($keys)) $keys = [$keys];
        else                  Assert::isArray($keys);

        if (!$keys)
            return (bool) $errors;

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
     */
    public function setActionError($key, $message) {
        if (!is_string($key) && !is_int($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.gettype($key));    // @phpstan-ignore-line
        Assert::nullOrString($message, '$message');

        if (!isset($message)) {
            unset($this->attributes[ACTION_ERRORS_KEY][$key]);
        }
        else {
            $this->attributes[ACTION_ERRORS_KEY][$key] = $message;
        }
    }


    /**
     * Remove the ActionError(s) with the specified key(s).
     *
     * @param  string ...$keys - zero or more error keys to delete; without a key all ActionErrors are removed
     *
     * @return string[] - the removed ActionErrors
     */
    public function removeActionErrors(...$keys) {
        $errors = $this->getAttribute(ACTION_ERRORS_KEY) ?: [];
        $removed = [];

        foreach ($keys as $key) {
            Assert::string($key, '$keys');
            if (isset($errors[$key]))
                $removed[$key] = $errors[$key];
            unset($this->attributes[ACTION_ERRORS_KEY][$key]);
        }
        if ($keys) return $removed;

        unset($this->attributes[ACTION_ERRORS_KEY]);
        return $errors;
    }


    /**
     * Return the MiniStruts {@link ActionMapping} responsible for processing the current request.
     *
     * @return ?ActionMapping - instance or NULL if the request doesn't match any of the configured mappings
     */
    final public function getMapping() {
        return $this->getAttribute(ACTION_MAPPING_KEY);
    }


    /**
     * Return the MiniStruts {@link Module} the current request is assigned to.
     *
     * @return ?Module - instance or NULL if the request doesn't match any of the configured modules
     */
    final public function getModule() {
        return $this->getAttribute(MODULE_KEY);
    }


    /**
     * Reject serialization of request instances.
     */
    final public function __sleep() {
        throw new IllegalStateException('You cannot serialize a '.get_class($this));
    }


    /**
     * Reject deserialization of Request instances.
     */
    final public function __wakeUp() {
        throw new IllegalStateException('You cannot deserialize a '.get_class($this));
    }


    /**
     * Return a human-readable version of the request.
     *
     * @return string
     */
    public function __toString() {
        $string = '';

        try {
            // request
            $string = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL'].NL;

            // headers
            $headers = $this->getHeaders() ?: [];

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
                $string .= NL.substr($content, 0, 1024).NL;                     // limit the request body to 1024 bytes
            }

            Assert::string($string);                                            // Ensure __toString() returns a string as otherwise...
        }                                                                       // PHP will trigger a non-catchable fatal error.
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }
        catch (\Exception $ex) { ErrorHandler::handleToStringException($ex); }  // @phpstan-ignore-line

        return $string;
    }


    /**
     * Return the instance currently registered in the service container.
     *
     * @return static
     *
     * @deprecated
     */
    public static function me() {
        /** @var static $instance */
        $instance = self::di(__CLASS__);
        return $instance;
    }
}
