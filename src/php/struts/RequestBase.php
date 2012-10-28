<?php
/**
 * RequestBase
 *
 * Diese Klasse stellt den HTTP-Request mit seinen Daten dar, sowie er von PHP an das aufgerufene Script übergeben wird.
 * Da es immer nur einen einzigen Request geben kann, ist er als Singleton implementiert.  Das bedeutet unter anderem,
 * daß es keinen öffentlichen Konstruktor gibt, man kann also nicht selbst einen neuen Request erzeugen (es gibt nur den
 * einen, der vom Server an PHP weitergereicht wurde).
 *
 * Warum eine abstrakte Klasse RequestBase und eine zusätzliche, funktional leere Klasse Request als Implementierung?
 *
 * Wird eine projektspezifische Implementierung oder Erweiterung der Klasse Request benötigt, müßte eine Projektklasse von
 * der Basisklasse der Library abgeleitet werden. Da PHP 5.2 keine Namespaces hat, würde für das Projekt der Klassenname
 * Request für diese erweiterte Klasse nicht mehr zur Verfügung stehen (Überlappung mit Klasse desselben Namens in der Library).
 * Durch die abstrakte Klasse RequestBase kann im Projekt eigene Funktionalität implementiert werden, und der Klassenname
 * Request steht trotz fehlender Namespaces weiterhin frei zur Verfügung. Dieser Workaround wird erst mit Umstellung der Library
 * auf PHP 5.3 überflüssig.
 *
 * Bis dahin beten wir zu den Göttern... und wenn es dann soweit ist: Da war ihres "Allah" Rufens kein Ende.¹
 *
 * ¹ Wörtlich: es war hoch ihr <i>takbìr</i>, d.h. sie begannen, als Zeichen größten Staunens, immer wieder <i>Allah akbar</i>
 *   "Gott ist groß" zu rufen.
 *
 *
 * NOTE:
 * -----
 * Diese Klasse verarbeitet die Request-Parameter intern wie in Java, mehrfache Werte je Parameter mit
 * numerischen oder assoziativen Schlüsseln werden automatisch verarbeitet ("a=1&a=2&a=3&a[]=4&a[key]=5").
 *
 * Die globalen PHP-Variablen $_GET, $_POST und $_REQUEST entsprechen der Originalimplementierung,
 * mehrfache Werte werden also überschrieben.
 *
 * @see RequestBase::getParameter()
 * @see RequestBase::getParameters()
 *
 * TODO: LinkTool implementieren, um path-info verwenden zu können
 * TODO: Versions-String in css- und js-Links einfügen
 */
abstract class RequestBase extends Singleton {


   private /*string*/ $method;
   private /*string*/ $hostURL;
   private /*string*/ $path;


   // Parameterhalter
   private $parameters = array('REQUEST' => array(),
                               'GET'     => array(),
                               'POST'    => array());

   // Attribute-Pool
   private $attributes = array();


   /**
    * Gibt die Singleton-Instanz des konkreten Requests zurück, wenn das Script im Kontext eines HTTP-Requestes
    * aufgerufen wurde. In allen anderen Fällen, z.B. bei Aufruf in der Konsole, wird NULL zurückgegeben.
    *
    * @return Singleton - Instanz oder NULL
    *
    * NOTE: Diese Methode muß nicht hier, sondern in der konkreten Request-Klasse implementiert werden.
    *       Der entsprechende Code ist nachfolgend angegeben und muß unverändert übernommen werden.
    */
   public static function me() {
      throw new plRuntimeException('Method '.__METHOD__.'() must not be called directly, it needs to be implemented in the concrete Request class.');

      // !!! begin code snippet !!!
      if (isSet($_SERVER['REQUEST_METHOD']))
         return Singleton ::getInstance(__CLASS__);
      return null;
      // !!! end code snippet   !!!
   }


   /**
    * Konstruktor
    */
   protected function __construct() {
      if (!ini_get('track_errors'))
         throw new plRuntimeException('PHP configuration setting "track_errors" is not "On" but needed for correct request decoding.');

      $this->method = $_SERVER['REQUEST_METHOD'];
      /**
       * Die Arrays mit den Requestparametern $_GET, $_POST und $_REQUEST werden manuell erstellt.
       * Die PHP-Implementierung ist aus den folgenden Gründen indiskutabel:
       *
       * (1) - Parameternamen werden modifiziert (image.x => image_x, image.y => image_y)
       * (2) - später auftretende Werte überschreiben vorhergehende, statt in einem Array abgelegt zu werden ("?a=1&a=2" => a=2)
       * (3) - UTF8-kodierte Parameternamen (wie sie beim Internet Explorer häufig auftreten) werden nicht dekodiert
       * (4) - Cookies sind kein User-Input, werden aber in $_REQUEST eingefügt und überschreiben dort existierende Parameter.
       */
      $_REQUEST = $_GET = array();

      // POST-Parameter haben höhere Priorität als GET und werden zuerst verarbeitet
      if ($this->isPost()) {
         if ($this->getContentType() != 'multipart/form-data') {
            $_POST = array();
            $this->parseParameters(file_get_contents('php://input'), 'POST');
         }
         else {
            // php://input is not available with enctype="multipart/form-data"
            // Wir behalten das originale $_POST-Array mit der PHP-Implementierung bei und überschreiben $_REQUEST

            foreach ($_POST as $name => $value) {
               $name  = String ::decodeUtf8($name);                  // (3) UTF8-Values dekodieren
               $value = String ::decodeUtf8($value);

               if (strPos($name, '_') != false)                      // (1) evt. modifizierte Parameter restaurieren
                  $_REQUEST[str_replace('_', '.', $name)] = $value;
               $_REQUEST[$name] = $value;
            }
            $_POST = $_REQUEST;                                      // $_POST mit neuer Version überschreiben
         }
      }

      // GET-Parameter (nicht per $this->isGet(), denn sie können auch in anderen Requests vorhanden sein)
      if (strLen($_SERVER['QUERY_STRING']))
         $this->parseParameters($_SERVER['QUERY_STRING'], 'GET');
   }


   /**
    * Parst die Parameter im übergebenen String und speichert die Ergebnisse in den entsprechenden Arrays.
    *
    * NOTE:
    * -----
    * Die Request-Parameter werden wie in Java verarbeitet, es sind also mehrfache Werte je Parameter
    * möglich ("a=1&a=2&a=3&a[]=4&a[key]=5").
    *
    * Die globalen PHP-Variablen $_GET, $_POST und $_REQUEST entsprechen der Originalimplementierung,
    * mehrfache Werte werden also überschrieben.
    *
    * @param string $rawData - Parameter-Rohdaten
    * @param string $target  - Bezeichner für das Zielarray: 'GET' oder 'POST'
    *
    * @see RequestBase::getParameter()
    * @see RequestBase::getParameters()
    */
   protected function parseParameters($rawData, $target) {
      $pairs = explode('&', $rawData);

      foreach ($pairs as $pair) {
         $parts = explode('=', $pair, 2);
         if (($name = trim(urlDecode($parts[0]))) == '')
            continue;
         // UTF8-Values nach ISO-8859 konvertieren (Internet Explorer etc.)
         $name  =                          String ::decodeUtf8($name);
         $value = sizeOf($parts)==1 ? '' : String ::decodeUtf8(urlDecode($parts[1]));
         $key   = null;

         // TODO: Arrays rekursiv verarbeiten
         if (($open=strPos($name, '[')) && ($close=strPos($name, ']')) && strLen($name)==$close+1) {
            // Arrayindex angegeben
            $key  = trim(subStr($name, $open+1, $close-$open-1));
            $name = trim(subStr($name, 0, $open));

            if (!strLen($key)) $this->parameters['REQUEST'][$name][]     = $this->parameters[$target][$name][]     = $value;
            else               $this->parameters['REQUEST'][$name][$key] = $this->parameters[$target][$name][$key] = $value;

            if ($target == 'GET') {
               if     (!strLen($key))               $_REQUEST[$name][]     = $value;
               elseif (!isSet($_POST[$name][$key])) $_REQUEST[$name][$key] = $value;   // GET darf POST nicht überschreiben

               if (!strLen($key)) $_GET[$name][]     = $value;
               else               $_GET[$name][$key] = $value;
            }
            else {
               if (!strLen($key)) $_REQUEST[$name][]     = $_POST[$name][]     = $value;
               else               $_REQUEST[$name][$key] = $_POST[$name][$key] = $value;
            }
         }
         else {
            // normaler Name, kein Array
            $this->parameters['REQUEST'][$name][] = $this->parameters[$target][$name][] = $value;

            if ($target == 'GET') {
               if (!isSet($_POST[$name])) $_REQUEST[$name] = $value;                   // GET darf POST nicht überschreiben
               $_GET[$name] = $value;
            }
            else {
               $_REQUEST[$name] = $_POST[$name] = $value;
            }
         }
      }
   }


   /**
    * Gibt die HTTP-Methode des Requests zurück.
    *
    * @return string
    */
   final public function getMethod() {
      return $this->method;
   }


   /**
    * Ob der Request ein GET-Request ist.
    *
    * @return bool
    */
   final public function isGet() {
      return ($this->method === 'GET');
   }


   /**
    * Ob der Request ein POST-Request ist.
    *
    * @return bool
    */
   final public function isPost() {
      return ($this->method === 'POST');
   }


   /**
    * Gibt den Requestparameter mit dem angegebenen Namen zurück.  Diese Methode gibt einen einzelnen Wert zurück.
    * Wurden mehrere Parameter dieses Namens übertragen, wird der letzte Parameter zurückgegeben.
    *
    * @param string $name - Parametername
    *
    * @return string - Parameterwert oder NULL, wenn kein Parameter dieses Namens übertragen wurde
    */
   public function getParameter($name) {
      if (isSet($this->parameters['REQUEST'][$name])) {
         $values = $this->parameters['REQUEST'][$name];
         return $values[sizeOf($values)-1];
      }
      return null;
   }


   /**
    * Gibt alle Requestparameter mit dem angegebenen Namen zurück.  Diese Methode gibt ein Array mit
    * den übertragenen Parametern zurück.
    *
    * @param string $name - Parametername
    *
    * @return array - String-Array
    */
   public function getParameters($name) {
      if (isSet($this->parameters['REQUEST'][$name]))
         return $this->parameters['REQUEST'][$name];

      return array();
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
    * Gibt die Wurzel-URL des Webservers zurück, über den der Request läuft. Dieser Wert endet NICHT
    * mit einem Slash "/".
    *
    * z.B.: https://www.domain.tld:433   (Protokoll + Hostname + Port)
    *
    * @return string
    */
   public function getHostURL() {
      if ($this->hostURL === null) {
         $protocol = isSet($_SERVER['HTTPS']) ? 'https' : 'http';
         $host     = $this->getHostname();
         $port     = $_SERVER['SERVER_PORT']=='80' ? '' : ':'.$_SERVER['SERVER_PORT'];

         $this->hostURL = "$protocol://$host$port";
      }
      return $this->hostURL;
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
    * Gibt die vollständige URL des Requests zurück.
    *
    * z.B.: https://www.domain.tld:433/myapplication/foo/bar.php/info?key=value
    *
    * @return string
    */
   public function getURL() {
      return $this->getHostURL().$this->getURI();
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
      return $_SERVER['REQUEST_URI'];
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
      // TODO: gibt absoluten Link auf falsches Verzeichnis zurück
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
         // TODO: schneidet path-info einfach ab
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
    * Alias für RequestBase::getApplicationPath() (für die Java-Abteilung :-)
    *
    * @return string
    *
    * @see RequestBase::getApplicationPath()
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
    * Gibt die IP-Adresse zurück, von der aus der Request ausgelöst wurde.
    *
    * @return string - IP-Adresse
    */
   public function getRemoteAddress() {
      return $_SERVER['REMOTE_ADDR'];
   }


   /**
    * Gibt den Hostnamen zurück, von dem aus der Request ausgelöst wurde.
    *
    * @return string
    */
   public function getRemoteHostname() {
      static $hostname = null;

      if ($hostname === null)
         $hostname = getHostByAddr($this->getRemoteAddress());

      return $hostname;
   }


   /**
    * Versucht, unter Berücksichtigung evt. zwischengeschalteter Proxys die tatsächliche IP-Adresse, von der
    * aus der Request gemacht wurde, zu ermitteln.  Kann diese Adresse nicht ermittelt werden, wird die letzte
    * ermittelbare Adresse zurückgegeben, unter Umständen also die herkömmliche Remote-Adresse.
    *
    * @return string - IP-Adresse
    */
   public function getRealRemoteAddress() {
      /**
      I am just starting to try to tackle this issue of getting the real ip address.
      One flaw I see in the preceding notes is that none will bring up a true live ip address when the first proxy
      is behind a nat router/firewall.  I am about to try and nut this out but from the data I see so far the true
      ip address (live ip of the nat router) is included in $_SERVER['HTTP_CACHE_CONTROL'] as bypass-client=xx.xx.xx.xx
      $_SERVER['HTTP_X_FORWARDED_FOR'] contains the proxy behind the nat router.  $_SERVER['REMOTE_ADDR'] is the isp
      proxy (from what I read this can be a list of proxies if you go through more than one).  I am not sure if
      bypass-client holds true when you get routed through several proxies along the way.
       *
       *
       * TODO: Diese IP kann einfach gefaked werden, wenn der Angreifer selbst einen x-beliebigen Forwarded-Header setzt.
       *       Abhilfe: Es müßten immer Remote-Adresse UND ALLE Forwarded-Header gespeichert werden. Sind einige oder alle
       *       Forwarded-Header gefaked, hat man immer noch die Remote-Adresse.
       */

      static $guessed = null;

      if ($guessed === null) {
         $names[] = 'X-Forwarded-For';
         $names[] = 'X-UP-Forwarded-For'; // mobile device

         $values = $this->getHeaderValues($names);

         if ($values) {
            foreach ($values as $value) {
               if ($value === '') {
                  continue;
               }
               elseif (CommonValidator ::isIPAddress($value)) {
                  $ip = $value;
               }
               elseif ($value=='unknown' || $value=='localhost' || $value==($ip=NetTools ::getHostByName($value))) {
                  continue;
               }

               if (CommonValidator ::isIPWanAddress($ip)) {
                  $guessed = $ip;
                  //if ($ip == NetTools ::getHostByAddress($ip))
                  //   Logger::log('Guessed a non-resolvable IP address as a WAN address: '.$ip, L_DEBUG, __CLASS__);
                  break;
               }
            }
         }

         // Fallback
         if ($guessed === null)
            $guessed = $this->getRemoteAddress();
      }

      return $guessed;
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
    * Gibt den Content dieses Requests zurück.  Der Content ist ein ggf. übertragener Request-Body (nur bei POST-Requests).
    *
    * @return mixed - Request-Body oder NULL, wenn im Body keine Daten übertragen wurden.  Ist der Content-Typ des Requests "multipart/form-data"
    *                 (File-Upload), wird statt des Request-Bodies ein Array mit den geposteten File-Informationen zurückgegeben.
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
      return (isSet($_COOKIE [$name]) || isSet($_REQUEST[$name]));
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
      return Singleton ::getInstance('HttpSession', $this);
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
    * @param string $name - Name des Headers
    *
    * @return array - Name-Wert-Paar oder NULL, wenn kein Header dieses Namens übertragen wurde
    */
   public function getHeader($name) {
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));

      $headers = $this->getHeaders($name);
      return array_shift($headers);
   }


   /**
    * Gibt die angegebenen Header als Array von Name-Wert-Paaren zurück (in der übertragenen Reihenfolge).
    *
    * @param string|array $names - ein oder mehrere Namen; ohne Angabe werden alle Header zurückgegeben
    *
    * @return array - Name-Wert-Paare
    */
   public function getHeaders($names = null) {
      if     ($names === null)   $names = array();
      elseif (is_string($names)) $names = array($names);
      elseif (is_array($names)) {
         foreach ($names as $name)
            if ($name !== (string)$name)
               throw new IllegalTypeException('Illegal argument type in argument $names: '.getType($name));
      }
      else {
         throw new IllegalTypeException('Illegal type of argument $names: '.getType($names));
      }

      // einmal alle Header einlesen
      static $headers = null;
      if ($headers === null) {
         if (function_exists('getAllHeaders')) {
            $headers = getAllHeaders();
            if ($headers === false) throw new plRuntimeException('Error reading request headers, getAllHeaders() returned: FALSE');
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
            Logger ::log('Invalid X-Forwarded-For header found', L_NOTICE, __CLASS__);
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
    * @param string|array $names - ein oder mehrere Headernamen
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
      else {
         throw new IllegalTypeException('Illegal type of argument $names: '.getType($names));
      }

      $headers = $this->getHeaders($names);
      if ($headers)
         return join(',', $headers);

      return null;
   }


   /**
    * Gibt die einzelnen Werte aller angegebenen Header als Array zurück (in der übertragenen Reihenfolge).
    *
    * @param string|array $names - ein oder mehrere Headernamen
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
      else {
         throw new IllegalTypeException('Illegal type of argument $names: '.getType($names));
      }

      $headers = $this->getHeaders($names);
      if ($headers)
         return array_map('trim', explode(',', join(',', $headers)));

      return $headers; // empty array;
   }


   /**
    * Gibt den im Request-Context unter dem angegebenen Schlüssel gespeicherten Wert zurück oder NULL,
    * wenn unter diesem Schlüssel kein Wert existiert.
    *
    * @param string $key - Schlüssel, unter dem der Wert im Context gespeichert ist
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
    * Speichert einen Wert unter dem angegebenen Schlüssel im Request-Context.
    *
    * @param string $key   - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value - der zu speichernde Wert
    */
   public function setAttribute($key, &$value) {
      $this->attributes[$key] = $value;
   }


   /**
    * Löscht die Werte mit den angegebenen Schlüsseln aus dem Request-Context. Es können mehrere Schlüssel
    * angegeben werden.
    *
    * @param string $key - Schlüssel des zu löschenden Wertes
    */
   public function removeAttributes($key /*, $key2, $key3 ...*/) {
      foreach (func_get_args() as $key) {
         unset($this->attributes[$key]);
      }
   }


   /**
    * Setzt einen Cookie mit den angegebenen Daten.
    *
    * @param string $name    - Name des Cookies
    * @param mixed  $value   - der zu speichernde Wert (wird zu String gecastet)
    * @param int    $expires - Lebenszeit des Cookies (0: bis zum Schließen des Browsers)
    * @param string $path    - Pfad, für den der Cookie gültig sein soll
    */
   public function setCookie($name, $value, $expires = 0, $path = null) {
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of argument $expires: '.getType($expires));
      if ($expires < 0)      throw new plInvalidArgumentException('Invalid argument $expires: '.$expires);

      $value = (string) $value;

      if ($path === null)
         $path = $this->getApplicationPath().'/';

      if (!is_string($path)) throw new IllegalTypeException('Illegal type of argument $path: '.getType($path));

      setCookie($name, $value, $expires, $path);
   }


   /**
    * Ob der User, der den Request ausgelöst hat, Inhaber der angegebenen Rolle(n) ist.
    *
    * @param string $roles - Rollenbezeichner
    *
    * @return bool
    */
   public function isUserInRole($roles) {
      if (!is_string($roles)) throw new IllegalTypeException('Illegal type of argument $roles: '.getType($roles));

      // Module holen
      $module = $this->getAttribute(Struts ::MODULE_KEY);
      if (!$module) throw new plRuntimeException('You can not call '.get_class($this).__FUNCTION__.'() in this context');

      // RoleProcessor holen ...
      $processor = $module->getRoleProcessor();
      if (!$processor) throw new plRuntimeException('You can not call '.get_class($this).__FUNCTION__.'() without configuring a RoleProcessor');

      // ... und Aufruf weiterreichen
      return $processor->isUserInRole($this, $roles);
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
    * @return bool
    */
   public function isActionError($key = null) {
      if ($key !== null) {
         return ($this->getActionError($key) !== null);
      }
      return (sizeOf($this->getAttribute(Struts ::ACTION_ERRORS_KEY)) > 0);
   }


   /**
    * Setzt für den angegebenen Schlüssel eine Error-Message. Ist Message NULL, wird die Message mit
    * dem angegebenen Schlüssel aus dem Request gelöscht.
    *
    * @param string $key     - Schlüssel der Error-Message
    * @param string $message - Error-Message
    */
   public function setActionError($key, $message) {
      if ($message === null) {
         if (isSet($this->attributes[Struts ::ACTION_ERRORS_KEY][$key]))
            unset($this->attributes[Struts ::ACTION_ERRORS_KEY][$key]);
      }
      elseif (is_string($message)) {
         $this->attributes[Struts ::ACTION_ERRORS_KEY][$key] = $message;
      }
      else {
         throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));
      }
   }


   /**
    * Löscht einzelne oder alle Error-Messages aus dem Request.
    *
    * @param string $key - die Schlüssel der zu löschenden Werte (ohne Angabe werden alle Error-Messages gelöscht)
    *
    * @return array - die gelöschten Error-Messages
    *
    * TODO: Error-Messages auch aus der Session löschen
    */
   public function dropActionErrors(/*$key1, $key2, $key3 ...*/) {
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
      unset($this->attributes[Struts ::ACTION_ERRORS_KEY]);
      return $dropped;
   }


   /**
    * Alias für self:: dropActionErrors()
    *
    * Löscht einzelne oder alle Error-Messages aus dem Request.
    *
    * @param string $key - die Schlüssel der zu löschenden Werte (ohne Angabe werden alle Error-Messages gelöscht)
    *
    * @return array - die gelöschten Error-Messages
    */
   public function removeActionErrors() {
      return $this->dropActionErrors();
   }


   /**
    * Gibt das diesem Request zugeordnete ActionMapping zurück.
    *
    * @return ActionMapping - Mapping oder NULL, wenn die Request-Instance außerhalb des Struts-Frameworks benutzt wird.
    */
   public final function getMapping() {
      return $this->getAttribute(Struts ::ACTION_MAPPING_KEY);
   }


   /**
    * Gibt das diesem Request zugeordnete Struts-Module zurück.
    *
    * @return Module - Module oder NULL, wenn die Request-Instance außerhalb des Struts-Frameworks benutzt wird.
    */
   public final function getModule() {
      return $this->getAttribute(Struts ::MODULE_KEY);
   }


   /**
    * Verhindert das Serialisieren von Request-Instanzen.
    */
   final public function __sleep() {
      throw new IllegalStateException('You cannot serialize me: '.get_class($this));
   }


   /**
    * Verhindert das Deserialisieren von Request-Instanzen.
    */
   final public function __wakeUp() {
      throw new IllegalStateException('You cannot deserialize me: '.get_class($this));
   }


   /**
    * Gibt eine lesbare Representation des Request zurück.
    *
    * @return string
    */
   public function __toString() {
      // Request
      $string = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL']."\n";

      // Header
      $headers = $this->getHeaders();
      $maxLen  = 0;
      foreach ($headers as $key => $value) {
         $maxLen = max(strLen($key), $maxLen);
      }
      $maxLen++; // +1 Zeichen für ':'
      foreach ($headers as $key => $value) {
         $string .= str_pad($key.':', $maxLen).' '.$value."\n";
      }

      // Content (Body)
      if ($this->isPost()) {
         $content = $this->getContent();
         if (strLen($content))
            $string .= "\n".$content."\n";
      }

      return $string;
   }
}
?>
