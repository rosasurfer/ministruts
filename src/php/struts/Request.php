<?
/**
 * Request
 *
 * Diese Klasse stellt den HTTP-Request mit seinen Daten dar, sowie er von PHP an das aufgerufene
 * Script übergeben wurde. Da es immer nur einen einzigen Request geben kann, ist er als Singleton
 * implementiert.  Das bedeutet unter anderem, daß es keinen öffentlichen Konstruktor gibt, man kann
 * also nicht selbst einen neuen Request erzeugen (es gibt nur den einen, der vom Server an PHP
 * weitergereicht wurde).
 *
 * TODO: LinkTool implementieren, um path-info verwenden zu können
 * TODO: Versions-String in css- und js-Links einfügen
 */
final class Request extends Singleton {


   private $method;
   private $hostURL;
   private $uri;
   private $path;


   // Attribute-Pool
   private $attributes = array();


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück, wenn das Script im Kontext eines HTTP-Requestes aufgerufen
    * wurde. In allen anderen Fällen, z.B. bei Aufruf in der Konsole, wird NULL zurückgegeben.
    *
    * @return Singleton - Instanz oder NULL
    */
   public static function me() {
      if (isSet($_SERVER['REQUEST_METHOD']))
         return Singleton ::getInstance(__CLASS__);

      return null;
   }


   /**
    * Konstruktor
    */
   protected function __construct() {
      $this->method = $_SERVER['REQUEST_METHOD'];

      // UTF8-kodierte Parameter nach ISO-8859 konvertieren (Internet Explorer läßt grüßen)
      $_GET  = String ::decodeUtf8($_GET);
      $_POST = String ::decodeUtf8($_POST);           // TODO: POST-Encodings berücksichtigen

      // $_REQUEST-Array neu definieren ($_COOKIE und $_FILES sind kein User-Input)
      // TODO: array_merge() auf Request-Parametern macht Übergabe von Arrays unmöglich
      $_REQUEST = array_merge($_GET, $_POST);
   }


   /**
    * Gibt die HTTP-Methode des Requests zurück.
    *
    * @return string
    */
   public function getMethod() {
      return $this->method;
   }


   /**
    * Ob der Request ein GET-Request ist.
    *
    * @return boolean
    */
   public function isGet() {
      return ($this->method === 'GET');
   }


   /**
    * Ob der Request ein POST-Request ist.
    *
    * @return boolean
    */
   public function isPost() {
      return ($this->method === 'POST');
   }


   /**
    * Gibt den vollen Hostnamen des Servers zurück, über den der Request läuft.  Dieser Wert enthält
    * eventuelle Subdomains.
    *
    * z.B.: www.domain.tld
    *
    * @return string
    */
   public function getHostname() {
      return $_SERVER['SERVER_NAME'];
   }


   /**
    * Gibt den einfachen Domainnamen des Servers zurück, über den der Request läuft.  Dieser Wert
    * enthält keine Subdomains.
    *
    * z.B.: domain.tld
    *
    * @return string
    */
   public function getDomainName() {
      static $domain = null;

      if (!$domain) {
         $parts = array_reverse(explode('.', $this->getHostname()));

         $domain = $parts[0];

         if (sizeOf($parts) > 1 && $parts[1]!='www' && $parts[1]!='local')
            $domain = $parts[1].'.'.$domain;
      }
      return $domain;
   }


   /**
    * Gibt die Wurzel-URL des Webservers zurück, über den der Request läuft. Dieser Wert endet NICHT
    * mit einem Slash "/".
    *
    * z.B.: https://www.domain.tld:433   (Protokoll + Hostname + Port)
    *
    * @return string
    */
   public function getHostURL() {
      if ($this->hostURL === null) {
         $http = isSet($_SERVER['HTTPS']) ? 'https' : 'http';
         $host = $_SERVER['SERVER_NAME'];
         $port = $_SERVER['SERVER_PORT']=='80' ? '' : ':'.$_SERVER['SERVER_PORT'];
         $this->hostURL = "$http://$host$port";
      }
      return $this->hostURL;

      /*
      function baseurl() {
         if(!empty($_SERVER["HTTPS"])){$http = "https";}else{$http = 'http';}
         $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
         $basename = preg_replace('/^.+[\\\\\\/]/', '', $_SERVER['PHP_SELF'] );
         $dir = substr($_SERVER['REQUEST_URI'], 0, -strlen($basename)-1);
         return "$http://".$_SERVER['SERVER_NAME'].$port.$dir;
      }
      */
   }


   /**
    * Gibt die Wurzel-URL der Anwendung zurück. Dieser Wert endet NICHT mit einem Slash "/".
    *
    * z.B.: https://www.domain.tld:433/myapplication   (Protokoll + Hostname + Port + Anwendungspfad)
    *
    * @return string
    */
   public function getApplicationURL() {
      // TODO: ApplicationURL ist Eigenschaft der Anwendung, nicht des Requests -> auslagern
      return $this->getHostURL().$this->getApplicationPath();
   }


   /**
    * Gibt den Teil der URL des Requests zurück, wie er in der ersten Zeile des HTTP-Protokolls
    * erscheint, relativ zum Wurzelverzeichnis des Webservers. Dieser Wert beginnt mit einem Slash "/".
    *
    * z.B.: /myapplication/foo/bar.php/info?key=value   (Pfadname + Pfadinfo + Querystring)
    *
    * @return string
    */
   public function getURI() {
      if ($this->uri === null) {
         $this->uri = $_SERVER['REQUEST_URI'];
      }
      return $this->uri;
   }


   /**
    * Gibt den Teil der URL des Requests zurück, wie er in der ersten Zeile des HTTP-Protokolls
    * erscheint, relativ zur Wurzel-URL der Anwendung. Dieser Wert beginnt mit einem Slash "/".
    *
    * z.B.: /foo/bar.php/info?key=value   (Pfadname + Pfadinfo + Querystring)
    *
    * @return string
    */
   public function getRelativeURI() {
      return subStr($this->getURI(), strLen($this->getApplicationPath()));
   }


   /**
    * Gibt den Pfadbestandteil der URL des Requests zurück. Dieser Wert beginnt mit einem Slash "/".
    *
    * z.B.: /myapplication/foo/bar.php   (Pfad ohne Pfadinfo und ohne Querystring)
    *
    * @return string
    */
   public function getPath() {
      if ($this->path === null) {
         $this->path = $_SERVER['PHP_SELF'] = preg_replace('/\/{2,}/', '/', $_SERVER['PHP_SELF']);
      }
      return $this->path;
   }


   /**
    * Gibt den Pfadbestandteil der URL des Requests relativ zur Wurzel-URL der Anwendung zurück.
    * Dieser Wert beginnt mit einem Slash "/".
    *
    * z.B.: /foo/bar.php   (Pfad ohne Pfadinfo und ohne Querystring)
    *
    * @return string
    */
   public function getRelativePath() {
      return subStr($this->getPath(), strLen($this->getApplicationPath()));
   }


   /**
    * Gibt den Pfadbestandteil der Wurzel-URL der Anwendung zurück. Liegt die Anwendung im Wurzel-
    * verzeichnis des Webservers, ist dieser Wert ein Leerstring "". Anderenfalls beginnt dieser Wert
    * mit einem Slash "/".
    *
    * z.B.: /myapplication   (Pfad ohne Pfadinfo und ohne Querystring)
    *
    * @return string
    */
   public function getApplicationPath() {
      // TODO: ApplicationPath ist Eigenschaft der Anwendung, nicht des Requests -> auslagern

      static $path = null;
      if ($path === null && isSet($_SERVER['APPLICATION_PATH'])) {
         $path = $_SERVER['APPLICATION_PATH'];

         // syntaktisch zwar nicht korrekt, doch wir wissen, was mit "/" gemeint ist
         if ($path == '/')
            $path = '';
      }

      return $path;
   }


   /**
    * Alias für Request::getApplicationPath()
    *
    * @return string
    *
    * @see Request::getApplicationPath()
    */
   public function getContextPath() {
      return $this->getApplicationPath();
   }


   /**
    * Gibt den Querystring der URL des Requests zurück.
    *
    * z.B.: key1=value1&key2=value2
    *
    * @return string
    */
   public function getQueryString() {
      return isSet($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
   }


   /**
    * Gibt die IP-Adresse zurück, von der aus der Request gemacht wurde.
    *
    * @return string - IP-Adresse
    */
   public function getRemoteAddress() {
      return $_SERVER['REMOTE_ADDR'];
   }


   /**
    * Gibt den Hostnamen zurück, von dem aus der Request gemacht wurde.
    *
    * @return string
    */
   public function getRemoteHostname() {
      return getHostByAddr($this->getRemoteAddress());
   }


   /**
    * Gibt den Wert des 'Forwarded-IP'-Headers des aktuellen Request zurück.
    *
    * @return string - IP-Adresse oder NULL, wenn der entsprechende Header nicht gesetzt ist
    */
   public function getForwardedRemoteAddress() {
      static $address = false;

     // TODO: Request::getForwardedRemoteAddress() überarbeiten

      if ($address === false) {
         if (isSet($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $address = $_SERVER['HTTP_X_FORWARDED_FOR'];
         }
         elseif (isSet($_SERVER['HTTP_HTTP_X_FORWARDED_FOR'])) {
            $address = $_SERVER['HTTP_HTTP_X_FORWARDED_FOR'];
         }
         elseif (isSet($_SERVER['HTTP_X_UP_FORWARDED_FOR'])) {       // mobile device
            $address = $_SERVER['HTTP_X_UP_FORWARDED_FOR'];
         }
         elseif (isSet($_SERVER['HTTP_HTTP_X_UP_FORWARDED_FOR'])) {  // mobile device
            $address = $_SERVER['HTTP_HTTP_X_UP_FORWARDED_FOR'];
         }
         elseif (isSet($_SERVER[''])) {
            $address = $_SERVER[''];
         }
         else {
            $address = null;
         }
      }
      return $address;
   }


   /**
    * Ob mit dem Request eine Session-ID übertragen wurde.
    *
    * @return boolean
    */
   public function isSessionId() {
      $name = session_name();
      return (isSet($_COOKIE [$name]) || isSet($_REQUEST[$name]));
   }


   /**
    * Ob eine aktuelle HttpSession existiert oder nicht.
    *
    * @return boolean
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
      return Singleton ::getInstance('HttpSession', $this);
   }


   /**
    * Gibt alle Request-Header zurück.
    *
    * @return array
    */
   public function getHeaders() {

      // muß noch umfassend überarbeitet werden !!!!

      if (function_exists('apache_request_headers')) {
         $headers = apache_request_headers();
         if ($headers === false) {
            Logger ::log('Error reading request headers, apache_request_headers(): false', L_ERROR, __CLASS__);
            $headers = array();
         }
         return $headers;
      }

      $headers = array();
      foreach ($_SERVER as $key => $value) {
         if (ereg('HTTP_(.+)', $key, $matches) > 0) {
            $key = strToLower($matches[1]);
            $key = str_replace(' ', '-', ucWords(str_replace('_', ' ', $key)));
            $headers[$key] = $value;
         }
      }
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
         if (isSet($_SERVER['CONTENT_TYPE']))
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
         if (isSet($_SERVER['CONTENT_LENGTH']))
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
      }
      return $headers;
   }


   /**
    * Gibt eine lesbare Representation des Request zurück.
    *
    * @return string
    */
   public function __toString() {

      // muß noch umfassend überarbeitet werden !!!!

      $result = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL']."\n";
      $headers = $this->getHeaders();
      $maxLen = 0;
      foreach ($headers as $key => &$value) {
         $maxLen = (strLen($key) > $maxLen) ? strLen($key) : $maxLen;
      }
      $maxLen++;
      foreach ($headers as $key => &$value) {
         $result .= str_pad($key.':', $maxLen).' '.$value."\n";
      }

      if ($_SERVER['REQUEST_METHOD']=='POST' && isSet($headers['Content-Length']) && (int)$headers['Content-Length'] > 0) {
         if (isSet($headers['Content-Type'])) {
            if ($headers['Content-Type'] == 'application/x-www-form-urlencoded') {
               $params = array();
               foreach ($_POST as $name => &$value) {
                  $params[] = $name.'='.urlEncode((string) $value);
               }
               $result .= "\n".implode('&', $params)."\n";
            }
            else if ($headers['Content-Type'] == 'multipart/form-data') {
               ;                    // !!! to do
            }
            else {
               ;                    // !!! to do
            }
         }
         else {
               ;                    // !!! to do
         }
      }
      return $result;
   }


   /**
    * Gibt den unter dem angegebenen Schlüssel gespeicherten Wert zurück oder NULL, wenn unter diesem
    * Schlüssel kein Wert existiert.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
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
    * Speichert einen Wert unter dem angegebenen Schlüssel im Request.
    *
    * @param string $key   - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value - der zu speichernde Wert
    */
   public function setAttribute($key, &$value) {
      $this->attributes[$key] = $value;
   }


   /**
    * Löscht den Wert unter dem angegebenen Schlüssel aus dem Request.
    *
    * @param string $key - Schlüssel des zu löschenden Wertes
    */
   public function removeAttribute($key) {
      if (isSet($this->attributes[$key])) {
         unset($this->attributes[$key]);
      }
   }


   /**
    * Ob der User, der den Request ausgelöst hat, Inhaber der angegebenen Rolle(n) ist.
    *
    * @param string $roles - Rollenbezeichner
    *
    * @return boolean
    */
   public function isUserInRole($roles) {
      if (!is_string($roles)) throw new IllegalTypeException('Illegal type of argument $roles: '.getType($roles));

      // RoleProcessor holen und Aufruf weiterreichen
      $processor = $this->getAttribute(Struts ::MODULE_KEY)
                        ->getRoleProcessor();
      if ($processor)
         return $processor->isUserInRole($this, $roles);

      return false;
   }


   /**
    * Gibt die Error-Message für den angegebenen Schlüssel zurück.  Ohne Schlüssel wird die erste
    * vorhandene Error-Message zurückgegeben.
    *
    * @param string $key - Schlüssel der Error-Message
    *
    * @return string - Error-Message
    */
   public function getActionError($key = null) {
      $errors =& $this->getAttribute(Struts ::ACTION_ERRORS_KEY);

      if ($key === null) {       // die erste zurückgeben
         if ($errors !== null) {
            foreach ($errors as &$error)
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
      $errors =& $this->getAttribute(Struts ::ACTION_ERRORS_KEY);

      if ($errors === null)
         $errors = array();

      return $errors;
   }


   /**
    * Ob unter dem angegebenen Schlüssel eine Error-Message existiert.  Ohne Angabe eines Schlüssel
    * wird geprüft, ob überhaupt irgendeine Error-Message existiert.
    *
    * @param string $key - Schlüssel
    *
    * @return boolean
    */
   public function isActionError($key = null) {
      if ($key !== null) {
         return ($this->getActionError($key) !== null);
      }
      return (sizeOf($this->getAttribute(Struts ::ACTION_ERRORS_KEY)) > 0);
   }


   /**
    * Setzt für den angegebenen Schlüssel eine Error-Message.
    *
    * @param string $key     - Schlüssel der Error-Message
    * @param string $message - Error-Message
    */
   public function setActionError($key, $message) {
      if (is_string($message)) {
         $this->attributes[Struts ::ACTION_ERRORS_KEY][$key] = $message;
      }
      elseif ($message === null) {
         if (isSet($this->attributes[Struts ::ACTION_ERRORS_KEY]) && isSet($this->attributes[Struts ::ACTION_ERRORS_KEY][$key]))
            unset($this->attributes[Struts ::ACTION_ERRORS_KEY][$key]);
      }
      else {
         throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));
      }
   }


   /**
    * Verhindert das Serialisieren von Request-Instanzen.
    */
   final public function __sleep() {
      $ex = new IllegalStateException('You cannot serialize me: '.__CLASS__);
      /**
       * TODO: Definition des Exceptionhandlers aus Root-Script entfernen, damit Fehler abgefangen werden
       *
       *   IllegalStateException: You cannot serialize me: Request
       *
       *   Stacktrace:
       *   -----------
       *   Request->__sleep(): # line 461, file: E:\Projekte\ministruts\src\php\struts\Request.php
       *   main():                         [php]
       *
       *
       *   Fatal error: Exception thrown without a stack frame in Unknown on line 0
       */
      Logger ::log($ex, L_ERROR, __CLASS__);
      throw $ex;
   }


   /**
    * Verhindert das Deserialisieren von Request-Instanzen.
    */
   final public function __wakeUp() {
      throw new IllegalStateException('You cannot unserialize me: '.__CLASS__);
   }
}
?>
