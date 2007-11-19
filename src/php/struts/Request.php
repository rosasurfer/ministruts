<?
/**
 * Request
 *
 * Diese Klasse stellt den HTTP-Request mit seinen Daten dar, sowie er von PHP an das aufgerufene Script
 * übergeben wurde. Da es immer nur einen einzigen Request geben kann, ist er als Singleton implementiert.
 * Das bedeutet unter anderem, daß es keinen öffentlichen Konstruktor gibt, man kann also nicht selbst
 * einen neuen Request erzeugen (es gibt nur den einen, der vom Server an PHP weitergereicht wurde).
 */
final class Request extends Singleton {


   private $method;
   private $pathInfo;


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

      // Input-Variablen neu definieren ($_COOKIE und $_FILES sind kein User-Input)
      $_REQUEST = array_merge($_GET, $_POST);
   }


   /**
    * Gibt die HTTP-Meode des Requests zurück.
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
    * Gibt die Pfadkomponente der URL des Requests zurück.
    */
   public function getPathInfo() {
      if ($this->pathInfo === null) {
         $this->pathInfo = $_SERVER['PHP_SELF'] = preg_replace('/\/{2,}/', '/', $_SERVER['PHP_SELF']);
      }
      return $this->pathInfo;
   }


   /**
    * Prüft, ob eine aktuelle HttpSession existiert oder nicht.
    *
    * @return boolean
    */
   public function isSession() {
      return defined('SID');
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
    * Speichert einen Wert unter dem angegebenen Schlüssel im Request.
    *
    * @param string $key   - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value - der zu speichernde Wert
    */
   public function setAttribute($key, &$value) {
      $this->attributes[$key] = $value;
   }


   /**
    * Gibt den unter dem angegebenen Schlüssel gespeicherten Wert zurück oder NULL, wenn unter dem
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
      return $value;
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
}
?>
