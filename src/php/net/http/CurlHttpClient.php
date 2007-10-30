<?
/**
 * CurlHttpClient
 *
 * Eine Klasse, die mit CURL HttpRequests ausführen kann.
 */
final class CurlHttpClient implements IHttpClient {


   // CURL-Handle
   private $handle;

   private $timeout          = 30;              // int
   private $followRedirects  = false;           // boolean
   private $maxRedirects     = 10;              // int
   private $currentRedirect  = 0;               // int:  für manuelle Redirects (wenn open_basedir definiert ist und followRedirects TRUE ist)
   private $userAgent        = 'Mozilla/5.0';   // string


   /**
    * Erzeugt eine neue Instanz von CurlHttpClient.
    *
    * @return CurlHttpClient
    */
   public static function create() {
      return new self;
   }


   /**
    * Setzt den Verbindungs-Timeout.
    *
    * @param int $timeout - Timeout in Sekunden
    *
    * @return CurlHttpClient
    */
   public function setTimeout($timeout) {
      if (!is_int($timeout)) throw new IllegalTypeException('Illegal type of argument $timeout: '.getType($timeout));
      if ($timeout < 1)      throw new InvalidArgumentException('Invalid argument $timeout: '.$timeout);

      $this->timeout = $timeout;
      return $this;
   }


   /**
    * Gibt den eingestellten Verbindungs-Timeout zurück.
    *
    * @param int $timeout - Timeout in Sekunden
    *
    * @return CurlHttpClient
    */
   public function getTimeout() {
      return $this->timeout;
   }


   /**
    * Ob Redirect-Headern gefolgt werden soll oder nicht.
    *
    * @param boolean $follow
    *
    * @return CurlHttpClient
    */
   public function setFollowRedirects($follow) {
      if ($follow!==true && $follow!==false) throw new IllegalTypeException('Illegal type of argument $follow: '.getType($follow));

      $this->followRedirects = $follow;
      return $this;
   }


   /**
    * Gibt die aktuelle Redirect-Einstellung zurück.
    *
    * @return boolean
    */
   public function isFollowRedirects() {
      return $this->followRedirects;
   }


   /**
    * Setzt die maximale Anzahl der Redirects, denen gefolgt werden soll.
    *
    * @param int $maxRedirects
    *
    * @return CurlHttpClient
    */
   public function setMaxRedirects($maxRedirects) {
      $this->maxRedirects = $maxRedirects;
      return $this;
   }


   /**
    * Gibt die Anzahl der Redirects zurück, denen gefolgt wird.
    *
    * @return int
    */
   public function getMaxRedirects() {
      return $this->maxRedirects;
   }


   /**
    * Führt den übergebenen Request aus und gibt die empfangene Antwort zurück.
    *
    * @param HttpRequest $request
    *
    * @return HttpResponse
    */
   public function send(HttpRequest $request) {
      $response = CurlHttpResponse ::create();

      $handle = curl_init();

      $options = array(CURLOPT_WRITEFUNCTION  => array($response, 'writeContent'),
                       CURLOPT_HEADERFUNCTION => array($response, 'writeHeader'),
                       CURLOPT_URL            => $request->getUrl(),
                       CURLOPT_TIMEOUT        => $this->timeout,
                       CURLOPT_USERAGENT      => $this->userAgent,
                       );

      if ($this->followRedirects && !ini_get('open_basedir')) {
         $options[CURLOPT_FOLLOWLOCATION] = true;
         $options[CURLOPT_MAXREDIRS]      = $this->maxRedirects;
      }

      if ($request->getMethod() == 'GET') {
         $options[CURLOPT_HTTPGET] = true;
      }
      else {
         $options[CURLOPT_POST]       = true;
         $options[CURLOPT_URL]        = subStr($request->getUrl(), 0, strPos($request->getUrl(), '?'));
         $options[CURLOPT_POSTFIELDS] = strStr($request->getUrl(), '?');
      }
      curl_setopt_array($handle, $options);

      if (curl_exec($handle) === false)
         Logger ::log('Could not retrieve url, CURL error: '.CURL ::getError($handle).', url: '.$request->getUrl(), L_INFO, __CLASS__);
      $response->setStatus($status = curl_getinfo($handle, CURLINFO_HTTP_CODE));

      curl_close($handle);


      // ggf. einen manuellen Redirect ausführen (falls "open_basedir" gesetzt ist)
      if (($status==301 || $status==302) && $this->followRedirects && ini_get('open_basedir')) {
         if ($this->currentRedirect < $this->maxRedirects) {
            $this->currentRedirect++;
            $request  = HttpRequest ::create()->setUrl($response->getHeader('Location')); // !!! to-do: relative Redirects abfangen
            $response = $this->send($request);
         }
         else {
            Logger ::log('maxRedirects limit exceeded: '.$this->maxRedirects, L_WARN, __CLASS__);
         }
      }

      return $response;
   }
}
?>
