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
    * Gibt die aktuelle Klasseninstanz zurück, wenn das Script im Kontext eines HTTP-Requestes aufgerufen
    * wurde. In allen anderen Fällen, z.B. bei Aufruf in der Konsole, wird NULL zurückgegeben.
    *
    * @return Request - Instanz oder NULL
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
    * Gibt die HTTP-Methode dieses Requests zurück.
    *
    * @return string
    */
   public function getMethod() {
      return $this->method;
   }


   /**
    * Ob dieser Request ein GET-Request ist.
    *
    * @return boolean
    */
   public function isGet() {
      return ($this->method === 'GET');
   }


   /**
    * Ob dieser Request ein POST-Request ist.
    *
    * @return boolean
    */
   public function isPost() {
      return ($this->method === 'POST');
   }


   /**
    * Gibt die Basis-URL des Servers zurück, über den dieser Request läuft.
    * (Protokoll + Hostname + Port).
    *
    * z.B.: https://www.domain.tld:433/
    *
    * @return string
    */
   public function getHostURL() {
      if ($this->hostURL === null) {
         $http = isSet($_SERVER['HTTPS']) ? 'https' : 'http';
         $host = $_SERVER['SERVER_NAME'];
         $port = $_SERVER['SERVER_PORT']=='80' ? '' : ':'.$_SERVER['SERVER_PORT'];
         $this->hostURL = "$http://$host$port/";
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
    * Gibt den Teil der URL dieses Requests zurück, wie er in der ersten Zeile des HTTP-Protokolls
    * erscheint, relativ zum Server-Wurzelverzeichnis (Pfadname + Pfadinfo + Querystring).
    *
    * z.B.: /myapp/stuff/view.php/info?key=value
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
    * Gibt den Teil der URL dieses Requests zurück, wie er in der ersten Zeile des HTTP-Protokolls
    * erscheint, relativ zur Context-URL der Anwendung (Pfadname + Pfadinfo + Querystring).
    *
    * z.B.: /view.php?key=value
    *
    * @return string
    */
   public function getRelativeURI() {
      return subStr($this->getURI(), strLen($this->getContextPath()));
   }


   /**
    * Gibt den Pfadbestandteil der URL dieses Requests zurück.
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
    * Gibt den zur Context-URL relativen Pfadbestandteil der URL dieses Requests zurück.
    *
    * @return string
    */
   public function getRelativePath() {
      return subStr($this->getPath(), strLen($this->getContextPath()));
   }


   /**
    * Gibt die Context-URL der Anwendung zurück.
    *
    * @return string
    */
   public function getContextPath() {
      return $this->getAttribute(Struts ::APPLICATION_PATH_KEY);
   }


   /**
    * Gibt die Querystring der URL dieses Requests zurück.
    *
    * @return string
    */
   public function getQueryString() {
      return isSet($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
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
    * Ob der User, der den Request ausgelöst hat, Inhaber der angegebenen Rollen ist.
    *
    * @param string $roles - Rollenbeschränkung
    *
    * @return boolean
    */
   public function isUserInRole($roles) {
      if (!is_string($roles)) throw new IllegalTypeException('Illegal type of argument $roles: '.getType($roles));

      // ActionMapping holen
      $mapping = $this->getAttribute(Struts ::ACTION_MAPPING_KEY);
      if (!$mapping)
         throw new RuntimeException('Illegal method call.');

      // RoleProcessor holen und Rückgabewert prüfen
      $forward = $mapping->getModule()
                         ->getRoleProcessor()
                         ->processRoles($this, Response ::me(), $mapping);
      return (!$forward);
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
}
?>
