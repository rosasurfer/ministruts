<?
/**
 * CurlHttpClient
 *
 * Eine Klasse, die mit CURL HttpRequests ausführen kann.
 */
final class CurlHttpClient extends HttpClient {


   // CURL-Handle
   private $handle;

   private $currentRedirect  = 0;               // int:  für manuelle Redirects (wenn open_basedir definiert ist und followRedirects TRUE ist)


   /**
    * Erzeugt eine neue Instanz von CurlHttpClient.
    *
    * @return CurlHttpClient
    */
   public static function create() {
      return new self;
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

      // CURL-Session initialisieren
      $handle = curl_init();

      // Optionen setzen
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

      // Request ausführen
      if (curl_exec($handle) === false) {
         $response = null;
         Logger ::log('CURL error: '.CURL ::getError($handle).', url: '.$request->getUrl(), L_WARN, __CLASS__);
      }
      else {
         $response->setStatus(curl_getinfo($handle, CURLINFO_HTTP_CODE));
      }
      curl_close($handle);

      // Response auswerten
      if ($response) {
         $status = $response->getStatus();
         // ggf. manuellen Redirect ausführen (falls "open_basedir" oder "safe_mode" aktiviert ist)
         if (($status==301 || $status==302) && $this->followRedirects && (ini_get('open_basedir') || ini_get('safe_mode'))) {
            if ($this->currentRedirect < $this->maxRedirects) {
               $this->currentRedirect++;
               $request  = HttpRequest ::create()->setUrl($response->getHeader('Location')); // !!! to-do: relative Redirects abfangen
               $response = $this->send($request);
            }
            else {
               Logger ::log('CURL error: maxRedirects limit exceeded - '.$this->maxRedirects, L_WARN, __CLASS__);
            }
         }
         return $response;
      }
      return null;
   }
}
?>
