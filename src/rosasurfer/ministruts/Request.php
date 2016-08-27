<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Singleton;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\DAY;
use const rosasurfer\L_NOTICE;
use const rosasurfer\NL;


/**
 * An object representing the current HTTP request. It provides an additional variables container (a context) with the
 * life-time of the HTTP request.
 *
 * @see  Request::getAttribute()
 * @see  Request::getAttributes()
 * @see  Request::setAttribute()
 * @see  Request::removeAttributes()
 *
 *
 * TODO: implement LinkTool
 * TODO: implement version hashes for CSS and JS links
 */
class Request extends Singleton {

   /** @var string */
   private $method;

   /** @var string */
   private $hostUrl;

   /** @var string */
   private $path;

   /** @var mixed[] - additional variables context */
   private $attributes = [];


   /**
    * Return the <tt>Singleton</tt> instance.
    *
    * @return Singleton
    *
    * @throws RuntimeException if not called from the web interface
    */
   public static function me() {
      if (isSet($_SERVER['REQUEST_METHOD']))
         return Singleton::getInstance(__CLASS__);
      throw new RuntimeException('Cannot create a '.__CLASS__.' instance in a non-web context.');
   }


   /**
    * Constructor
    */
   protected function __construct() {
      $this->method = $_SERVER['REQUEST_METHOD'];

      // If $_SERVER['QUERY_STRING'] is empty (e.g. at times in nginx) PHP will not parse url parameters
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
    * Return the request parameter with the specified name. Returns always a single value. If multiple parameters with
    * the name exist, it returns the last value.
    *
    * @param  string $name - parameter name
    *
    * @return string - value or NULL if no such parameter exists
    */
   public function getParameter($name) {
      if (isSet($_REQUEST[$name])) {
         $value = $_REQUEST[$name];
         if (is_array($value))
            return $value[sizeOf($value)-1];
         return $value;
      }
      return null;
   }


   /**
    * Return all request parameters with the specified name. Returns always an array.
    *
    * @param  string $name - parameter name
    *
    * @return string[] - values or an empty array if no such parameters exist
    */
   public function getParameters($name) {
      if (isSet($_REQUEST[$name])) {
         $value = $_REQUEST[$name];
         !is_array($value) && $value=[$value];
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
    * Return the uri of the current request (the value in the first line of the HTTP protocol). This value always starts
    * with a slash "/".
    *
    * @return string - uri: path + query_string
    *                  All urls in this framework are virtual, there is no "path info" as such.
    * @example
    * <pre>
    * "/application/module/foo/bar.html?key=value"
    * </pre>
    */
   public function getUri() {
      return $_SERVER['REQUEST_URI'];
   }


   /**
    * Return the path fragment of the current request's uri. This value always starts with a slash "/".
    *
    * @return string - path without query string
    *                  All urls in this framework are virtual, there is no "path info" as such.
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
      return strRightFrom($this->getPath(), $this->getApplicationBaseUri()).'/';
   }


   /**
    * Return the application's base uri. This value always starts and ends with a slash "/".
    *
    * @return string - uri: path (without query string)
    *
    * @example
    * <pre>
    * "/application/"
    * </pre>
    */
   public function getApplicationBaseUri() {
      // TODO: Move to application as this uri is not a property of the request.
      static $path = null;
      if (!$path && isSet($_SERVER['APPLICATION_BASE_URI'])) {
         $path = $_SERVER['APPLICATION_BASE_URI'];             // triggers error if not set, which is OK
         !strStartsWith($path, '/') && $path  = '/'.$path;
         !strEndsWith  ($path, '/') && $path .= '/';
      }
      return $path;
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
      !$hostname && $hostname=getHostByAddr($this->getRemoteAddress());
      return $hostname;
   }


   /**
    * Gibt den Wert des 'X-Forwarded-For'-Headers des aktuellen Requests zurück.
    *
    * @return string - Wert (ein oder mehrere IP-Adressen oder Hostnamen) oder NULL, wenn der Header nicht gesetzt ist
    */
   public function getForwardedRemoteAddress() {
      return $this->getHeaderValue(array('X-Forwarded-For', 'X-UP-Forwarded-For'));
   }


   /**
    * Gibt den Content dieses Requests zurück. Der Content ist ein ggf. übertragener Request-Body (nur bei POST-Requests).
    *
    * @return mixed - Request-Body oder NULL, wenn im Body keine Daten übertragen wurden.  Ist der Content-Typ des Requests
    *                 "multipart/form-data" (File-Upload), wird statt des Request-Bodies ein Array mit den geposteten
    *                 File-Informationen zurückgegeben.
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
    * Gibt den Content-Type dieses Requests zurück. Werden unsinnigerweise mehrere "Content-Type"-Header übertragen, wird
    * der erste gefundene Header zurückgegeben.
    *
    * @return string - Content-Type oder NULL, wenn kein "Content-Type"-Header übertragen wurde.
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
    * Ob mit dem Request eine Session-ID übertragen wurde.
    *
    * @return bool
    */
   public function isSessionId() {
      $name = session_name();
      return (isSet($_COOKIE[$name]) || isSet($_REQUEST[$name]));
   }


   /**
    * Ob eine aktuelle HttpSession existiert oder nicht.
    *
    * @return bool
    */
   public function isSession() {
      return defined('SID');
   }


   /**
    * Gibt die aktuelle HttpSession zurück. Existiert noch keine Session, wird eine erzeugt.
    *
    * @return HttpSession
    */
   public function getSession() {
      return Singleton::getInstance('HttpSession', $this);
   }


   /**
    * Zerstört die aktuelle HttpSession des Requests.
    *
    * @return bool
    */
   public function destroySession() {
      if ($this->isSession()) {
         // TODO: 1. Cookie mit 2. überschreiben statt einen weiteren hinzuzufügen
         // besser einen schon gesetzten Cookie mit header($replace = true) überschreiben
         // außerdem soll $value = '' nicht immer funktionieren, besser: $value = sess_id()

         // TODO: SID und die gesamte Session zurücksetzen
         setcookie(session_name(), '', time() - 1*DAY, '/');
         session_destroy();
      }
   }


   /**
    * Gibt den angegebenen Header als Name-Wert-Paar zurück.  Wurden mehrere Header dieses Namens übertragen,
    * wird der erste übertragene Header zurückgegeben.
    *
    * @param  string $name - Name des Headers
    *
    * @return array - Name-Wert-Paar oder NULL, wenn kein Header dieses Namens übertragen wurde
    */
   public function getHeader($name) {
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));

      $headers = $this->getHeaders($name);
      return array_shift($headers);
   }


   /**
    * Gibt die angegebenen Header als Array von Name-Wert-Paaren zurück (in der übertragenen Reihenfolge).
    *
    * @param  string|[] $names - ein oder mehrere Namen; ohne Angabe werden alle Header zurückgegeben
    *
    * @return array - Name-Wert-Paare
    */
   public function getHeaders($names = null) {
      if     ($names === null)   $names = array();
      elseif (is_string($names)) $names = array($names);
      elseif (is_array($names)) {
         foreach ($names as $name) {
            if (!is_string($name)) throw new IllegalTypeException('Illegal argument type in argument $names: '.getType($name));
         }
      }
      else throw new IllegalTypeException('Illegal type of parameter $names: '.getType($names));

      // einmal alle Header einlesen
      static $headers = null;
      if ($headers === null) {
         if (function_exists('getAllHeaders')) {
            $headers = getAllHeaders();
            if ($headers === false) throw new RuntimeException('Error reading request headers, getAllHeaders() returned: FALSE');
         }
         else {
            // TODO: in der PHP-Umgebung fehlen einige Header
            // z.B. 'Authorization' (Digest), Basic authorization prüfen, $_FILES prüfen !!!
            $headers = array();
            foreach ($_SERVER as $key => $value) {
               if(subStr($key, 0, 5) == 'HTTP_') {
                  $key = strToLower(subStr($key, 5));
                  $key = str_replace(' ', '-', ucWords(str_replace('_', ' ', $key)));
                  $headers[$key] = $value;
               }
            }
            if ($this->isPost()) {
               if (isSet($_SERVER['CONTENT_TYPE'  ])) $headers['Content-Type'  ] = $_SERVER['CONTENT_TYPE'  ];
               if (isSet($_SERVER['CONTENT_LENGTH'])) $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
            }
         }

         // Schauen wir mal, ob es immer noch solche falschen Header gibt...
         $tmp = array('HTTP-X-Forwarded-For'    => 1,
                      'HTTP_X-Forwarded-For'    => 1,
                      'HTTP-X-UP-Forwarded-For' => 1,
                      'HTTP_X-UP-Forwarded-For' => 1);
         if (array_intersect_ukey($headers, $tmp, 'strCaseCmp'))
            \Logger::log('Invalid X-Forwarded-For header found', null, L_NOTICE, __CLASS__);
      }

      // alle oder nur die gewünschten Header zurückgeben
      if (!$names)
         return $headers;

      return array_intersect_ukey($headers, array_flip($names), 'strCaseCmp');
   }


   /**
    * Gibt den Wert des angegebenen Headers als String zurück. Wird ein Array mit mehreren Namen angegeben oder wurden
    * mehrere Header des angegebenen Namens übertragen, werden alle Werte dieser Header als eine komma-getrennte Liste
    * zurückgegeben (in der übertragenen Reihenfolge).
    *
    * @param  string|[] $names - ein oder mehrere Headernamen
    *
    * @return string - Wert oder NULL, wenn die angegebenen Header nicht gesetzt sind
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
    * Gibt die einzelnen Werte aller angegebenen Header als Array zurück (in der übertragenen Reihenfolge).
    *
    * @param  string|[] $names - ein oder mehrere Headernamen
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
    * Gibt den im Request-Context unter dem angegebenen Schlüssel gespeicherten Wert zurück oder NULL,
    * wenn unter diesem Schlüssel kein Wert existiert.
    *
    * @param  string $key - Schlüssel, unter dem der Wert im Context gespeichert ist
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
    * Gibt alle im Request-Context gespeicherten Werte zurück.
    *
    * @return array - Werte-Array
    */
   public function getAttributes() {
      return $this->attributes;
   }


   /**
    * Store a value in the <tt>Request</tt> context. Can be used to transfer data from controllers or <tt>Action</tt>s to views.
    *
    * @param  string $key   - Schlüssel, unter dem der Wert gespeichert wird
    * @param  mixed  $value - der zu speichernde Wert
    */
   public function setAttribute($key, &$value) {
      $this->attributes[$key] = $value;
   }


   /**
    * Löscht die Werte mit den angegebenen Schlüsseln aus dem Request-Context. Es können mehrere Schlüssel
    * angegeben werden.
    *
    * @param  string $key - Schlüssel des zu löschenden Wertes
    */
   public function removeAttributes($key /*, $key2, $key3 ...*/) {
      foreach (func_get_args() as $key) {
         unset($this->attributes[$key]);
      }
   }


   /**
    * Setzt einen Cookie mit den angegebenen Daten.
    *
    * @param  string $name    - Name des Cookies
    * @param  mixed  $value   - der zu speichernde Wert (wird zu String gecastet)
    * @param  int    $expires - Lebenszeit des Cookies (0: bis zum Schließen des Browsers)
    * @param  string $path    - Pfad, für den der Cookie gültig sein soll
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
    * Ob der User, der den Request ausgelöst hat, Inhaber der angegebenen Rolle(n) ist.
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
    * Gibt die Error-Message für den angegebenen Schlüssel zurück.  Ohne Schlüssel wird die erste
    * vorhandene Error-Message zurückgegeben.
    *
    * @param  string $key - Schlüssel der Error-Message
    *
    * @return string - Error-Message
    */
   public function getActionError($key = null) {
      $errors =& $this->getAttribute(ACTION_ERRORS_KEY);

      if ($key === null) {       // die erste zurückgeben
         if ($errors) {
            foreach ($errors as $error)
               return $error;
         }
      }                          // eine bestimmte zurückgeben
      elseif (isSet($errors[$key])) {
         return $errors[$key];
      }
      return null;
   }


   /**
    * Gibt alle vorhandenen Error-Messages zurück.
    *
    * @return array - Error-Messages
    */
   public function getActionErrors() {
      $errors =& $this->getAttribute(ACTION_ERRORS_KEY);

      if ($errors === null)
         $errors = array();

      return $errors;
   }


   /**
    * Ob unter dem angegebenen Schlüssel eine Error-Message existiert.  Ohne Angabe eines Schlüssel
    * wird geprüft, ob überhaupt irgendeine Error-Message existiert.
    *
    * @param  string $key - Schlüssel
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
    * Setzt für den angegebenen Schlüssel eine Error-Message. Ist Message NULL, wird die Message mit
    * dem angegebenen Schlüssel aus dem Request gelöscht.
    *
    * @param  string $key     - Schlüssel der Error-Message
    * @param  string $message - Error-Message
    */
   public function setActionError($key, $message) {
      if ($message === null) {
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
    * Löscht Error-Messages aus dem Request.
    *
    * @param  string $key - die Schlüssel der zu löschenden Werte (ohne Angabe werden alle Error-Messages gelöscht)
    *
    * @return array - die gelöschten Error-Messages
    *
    * TODO: Error-Messages auch aus der Session löschen
    */
   public function removeActionErrors(/*$key1, $key2, $key3 ...*/) {
      $dropped = array();

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
    * Gibt das diesem Request zugeordnete ActionMapping zurück.
    *
    * @return ActionMapping - Mapping oder NULL, wenn die Request-Instance außerhalb des Struts-Frameworks benutzt wird.
    */
   public final function getMapping() {
      return $this->getAttribute(ACTION_MAPPING_KEY);
   }


   /**
    * Gibt das diesem Request zugeordnete Struts-Module zurück.
    *
    * @return Module - Module oder NULL, wenn die Request-Instance außerhalb des Struts-Frameworks benutzt wird.
    */
   public final function getModule() {
      return $this->getAttribute(MODULE_KEY);
   }


   /**
    * Reject serialization of Request instances.
    */
   final public function __sleep() {
      throw new IllegalStateException('You must not serialize a '.__CLASS__);
   }


   /**
    * Reject de-serialization of Request instances.
    */
   final public function __wakeUp() {
      throw new IllegalStateException('You must not deserialize a '.__CLASS__);
   }


   /**
    * Gibt eine lesbare Representation des Request zurück.
    *
    * @return string
    */
   public function __toString() {
      // Request
      $string = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL'].NL;

      // Header
      $headers = $this->getHeaders();
      $maxLen  = 0;
      foreach ($headers as $key => $value) {
         $maxLen = max(strLen($key), $maxLen);
      }
      $maxLen++; // +1 Zeichen für ':'
      foreach ($headers as $key => $value) {
         $string .= str_pad($key.':', $maxLen).' '.$value.NL;
      }

      // Content (Body)
      if ($this->isPost()) {
         $content = $this->getContent();
         if (strLen($content))
            $string .= NL.$content.NL;
      }

      return $string;
   }
}


!defined(__NAMESPACE__.'\MODULE_KEY') && include(__DIR__.'/definitions.php');
