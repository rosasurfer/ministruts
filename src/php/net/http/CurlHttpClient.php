<?php
/**
 * CurlHttpClient
 *
 * Eine Klasse, die mit CURL HttpRequests ausführen kann.
 */
final class CurlHttpClient extends HttpClient {


   private static /*bool*/ $logDebug, $logInfo, $logNotice;

   private /*resource*/ $hCurl;                       // Curl-Handle
   private /*int*/      $manualRedirects = 0;         // Zähler für manuelle Redirects (falls open_basedir|safe_mode aktiv ist)
   private /*mixed[]*/  $options         = array();   // zusätzliche CURL-Optionen


   // CURL-Fehlerbeschreibungen
   private static $errors = array(CURLE_OK                      => 'CURLE_OK'                     ,
                                  CURLE_UNSUPPORTED_PROTOCOL    => 'CURLE_UNSUPPORTED_PROTOCOL'   ,
                                  CURLE_FAILED_INIT             => 'CURLE_FAILED_INIT'            ,
                                  CURLE_URL_MALFORMAT           => 'CURLE_URL_MALFORMAT'          ,
                                  CURLE_COULDNT_RESOLVE_PROXY   => 'CURLE_COULDNT_RESOLVE_PROXY'  ,
                                  CURLE_COULDNT_RESOLVE_HOST    => 'CURLE_COULDNT_RESOLVE_HOST'   ,
                                  CURLE_COULDNT_CONNECT         => 'CURLE_COULDNT_CONNECT'        ,
                                  CURLE_FTP_WEIRD_SERVER_REPLY  => 'CURLE_FTP_WEIRD_SERVER_REPLY' ,
                                  9                             => 'CURLE_REMOTE_ACCESS_DENIED'   ,
                                  CURLE_FTP_WEIRD_PASS_REPLY    => 'CURLE_FTP_WEIRD_PASS_REPLY'   ,
                                  CURLE_FTP_WEIRD_PASV_REPLY    => 'CURLE_FTP_WEIRD_PASV_REPLY'   ,
                                  CURLE_FTP_WEIRD_227_FORMAT    => 'CURLE_FTP_WEIRD_227_FORMAT'   ,
                                  CURLE_FTP_CANT_GET_HOST       => 'CURLE_FTP_CANT_GET_HOST'      ,
                                  17                            => 'CURLE_FTP_COULDNT_SET_TYPE'   ,
                                  CURLE_PARTIAL_FILE            => 'CURLE_PARTIAL_FILE'           ,
                                  CURLE_FTP_COULDNT_RETR_FILE   => 'CURLE_FTP_COULDNT_RETR_FILE'  ,
                                  21                            => 'CURLE_QUOTE_ERROR'            ,
                                  22                            => 'CURLE_HTTP_RETURNED_ERROR'    ,
                                  CURLE_WRITE_ERROR             => 'CURLE_WRITE_ERROR'            ,
                                  25                            => 'CURLE_UPLOAD_FAILED'          ,
                                  CURLE_READ_ERROR              => 'CURLE_READ_ERROR'             ,
                                  CURLE_OUT_OF_MEMORY           => 'CURLE_OUT_OF_MEMORY'          ,
                                  28                            => 'CURLE_OPERATION_TIMEDOUT'     ,
                                  CURLE_FTP_PORT_FAILED         => 'CURLE_FTP_PORT_FAILED'        ,
                                  CURLE_FTP_COULDNT_USE_REST    => 'CURLE_FTP_COULDNT_USE_REST'   ,
                                  33                            => 'CURLE_RANGE_ERROR'            ,
                                  CURLE_HTTP_POST_ERROR         => 'CURLE_HTTP_POST_ERROR'        ,
                                  CURLE_SSL_CONNECT_ERROR       => 'CURLE_SSL_CONNECT_ERROR'      ,
                                  CURLE_FTP_BAD_DOWNLOAD_RESUME => 'CURLE_FTP_BAD_DOWNLOAD_RESUME',
                                  CURLE_FILE_COULDNT_READ_FILE  => 'CURLE_FILE_COULDNT_READ_FILE' ,
                                  CURLE_LDAP_CANNOT_BIND        => 'CURLE_LDAP_CANNOT_BIND'       ,
                                  CURLE_LDAP_SEARCH_FAILED      => 'CURLE_LDAP_SEARCH_FAILED'     ,
                                  CURLE_FUNCTION_NOT_FOUND      => 'CURLE_FUNCTION_NOT_FOUND'     ,
                                  CURLE_ABORTED_BY_CALLBACK     => 'CURLE_ABORTED_BY_CALLBACK'    ,
                                  CURLE_BAD_FUNCTION_ARGUMENT   => 'CURLE_BAD_FUNCTION_ARGUMENT'  ,
                                  45                            => 'CURLE_INTERFACE_FAILED'       ,
                                  CURLE_TOO_MANY_REDIRECTS      => 'CURLE_TOO_MANY_REDIRECTS'     ,
                                  CURLE_UNKNOWN_TELNET_OPTION   => 'CURLE_UNKNOWN_TELNET_OPTION'  ,
                                  CURLE_TELNET_OPTION_SYNTAX    => 'CURLE_TELNET_OPTION_SYNTAX'   ,
                                  CURLE_SSL_PEER_CERTIFICATE    => 'CURLE_SSL_PEER_CERTIFICATE'   ,
                                  CURLE_GOT_NOTHING             => 'CURLE_GOT_NOTHING'            ,
                                  CURLE_SSL_ENGINE_NOTFOUND     => 'CURLE_SSL_ENGINE_NOTFOUND'    ,
                                  CURLE_SSL_ENGINE_SETFAILED    => 'CURLE_SSL_ENGINE_SETFAILED'   ,
                                  CURLE_SEND_ERROR              => 'CURLE_SEND_ERROR'             ,
                                  CURLE_RECV_ERROR              => 'CURLE_RECV_ERROR'             ,
                                  CURLE_SSL_CERTPROBLEM         => 'CURLE_SSL_CERTPROBLEM'        ,
                                  CURLE_SSL_CIPHER              => 'CURLE_SSL_CIPHER'             ,
                                  CURLE_SSL_CACERT              => 'CURLE_SSL_CACERT'             ,
                                  CURLE_BAD_CONTENT_ENCODING    => 'CURLE_BAD_CONTENT_ENCODING'   ,
                                  CURLE_LDAP_INVALID_URL        => 'CURLE_LDAP_INVALID_URL'       ,
                                  CURLE_FILESIZE_EXCEEDED       => 'CURLE_FILESIZE_EXCEEDED'      ,
                                  64                            => 'CURLE_USE_SSL_FAILED'         ,
                                  65                            => 'CURLE_SEND_FAIL_REWIND'       ,
                                  66                            => 'CURLE_SSL_ENGINE_INITFAILED'  ,
                                  67                            => 'CURLE_LOGIN_DENIED'           ,
                                  68                            => 'CURLE_TFTP_NOTFOUND'          ,
                                  69                            => 'CURLE_TFTP_PERM'              ,
                                  70                            => 'CURLE_REMOTE_DISK_FULL'       ,
                                  71                            => 'CURLE_TFTP_ILLEGAL'           ,
                                  72                            => 'CURLE_TFTP_UNKNOWNID'         ,
                                  73                            => 'CURLE_REMOTE_FILE_EXISTS'     ,
                                  74                            => 'CURLE_TFTP_NOSUCHUSER'        ,
                                  75                            => 'CURLE_CONV_FAILED'            ,
                                  76                            => 'CURLE_CONV_REQD'              ,
                                  77                            => 'CURLE_SSL_CACERT_BADFILE'     ,
                                  78                            => 'CURLE_REMOTE_FILE_NOT_FOUND'  ,
                                  79                            => 'CURLE_SSH'                    ,
                                  80                            => 'CURLE_SSL_SHUTDOWN_FAILED'    );


   /**
    * Erzeugt eine neue Instanz.
    *
    * @param  mixed[] $options - Array mit zusätzlichen CURL-Optionen (default: keine)
    */
   public function __construct(array $options=null) {
      $loglevel        = Logger::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      if (!is_null($options))
         $this->options = $options;
   }


   /**
    * Destructor. Schließt ggf. ein noch offenes CURL-Handle
    */
   public function __destruct() {
      // Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
      // @see http://php.net/manual/en/language.oop5.decon.php
      try {
         if (is_resource($this->hCurl)) {
            $hTmp=$this->hCurl; $this->hCurl=null;
            curl_close($hTmp);
         }
      }
      catch (Exception $ex) {
         Logger::handleException($ex, $inShutdownOnly=true);
         throw $ex;
      }
   }


   /**
    * Erzeugt eine neue Instanz der Klasse.
    *
    * @param  mixed[] $options - Array mit zusätzlichen CURL-Optionen (default: keine)
    *
    * @return CurlHttpClient
    */
   public static function create(array $options=null) {
      return new self($options);
   }


   /**
    * Führt den übergebenen Request aus und gibt die empfangene Antwort zurück.
    *
    * @param  HttpRequest $request
    *
    * @return HttpResponse
    *
    * @throws IOException - wenn ein Fehler auftritt
    */
   public function send(HttpRequest $request) {
      $response = CurlHttpResponse::create();

      // CURL-Session initialisieren
      !is_resource($this->hCurl) && $this->hCurl=curl_init();

      // Optionen setzen, sofern sie nicht schon gesetzt sind
      $options = $this->options;             $options[CURLOPT_URL      ] = $request->getUrl();
      !isSet($options[CURLOPT_TIMEOUT  ]) && $options[CURLOPT_TIMEOUT  ] = $this->timeout;      // Execution-Timeout
      !isSet($options[CURLOPT_USERAGENT]) && $options[CURLOPT_USERAGENT] = $this->userAgent;
      !isSet($options[CURLOPT_ENCODING ]) && $options[CURLOPT_ENCODING ] = '';                  // sets all supported encodings

      if (!isSet($options[CURLOPT_WRITEHEADER]))
         if (!isSet($options[CURLOPT_HEADERFUNCTION])) $options[CURLOPT_HEADERFUNCTION] = array($response, 'writeHeader');
      if (!isSet($options[CURLOPT_FILE]))                                                       // ein gesetztes CURLOPT_RETURNTRANSFER wird überschrieben
         if (!isSet($options[CURLOPT_WRITEFUNCTION]))  $options[CURLOPT_WRITEFUNCTION ] = array($response, 'writeContent');

      // zusätzliche Header überschreiben automatisch generierte Header
      foreach ($request->getHeaders() as $key => $value) {
         $options[CURLOPT_HTTPHEADER][] = $key.': '.$value;
      }

      // CURLOPT_FOLLOWLOCATION funktioniert nur bei deaktiviertem "open_basedir"-Setting
      if (!ini_get('open_basedir')) {
         if ($this->followRedirects) {
            $options[CURLOPT_FOLLOWLOCATION] = true;
         }
         else if (isSet($options[CURLOPT_FOLLOWLOCATION]) && $options[CURLOPT_FOLLOWLOCATION]) {
            $this->followRedirects(true);
         }
         if ($this->followRedirects) {
            !isSet($options[CURLOPT_MAXREDIRS]) && $options[CURLOPT_MAXREDIRS]=$this->maxRedirects;
         }
      }

      if ($request->getMethod() == 'POST') {
         $options[CURLOPT_POST      ] = true;
         $options[CURLOPT_URL       ] = subStr($request->getUrl(), 0, strPos($request->getUrl(), '?'));
         $options[CURLOPT_POSTFIELDS] = strStr($request->getUrl(), '?');
      }
      curl_setopt_array($this->hCurl, $options);

      // Request ausführen
      if (curl_exec($this->hCurl) === false) throw new IOException('CURL error '.self::getError($this->hCurl).', url: '.$request->getUrl());
      $response->setStatus($status=curl_getinfo($this->hCurl, CURLINFO_HTTP_CODE));

      // ggf. manuellen Redirect ausführen (falls "open_basedir" oder "safe_mode" aktiviert sind)
      if (($status==301 || $status==302) && $this->followRedirects && (ini_get('open_basedir') || ini_get('safe_mode'))) {
         if ($this->manualRedirects >= $this->maxRedirects)
            throw new IOException('CURL error: maxRedirects limit exceeded - '.$this->maxRedirects.', url: '.$request->getUrl());

         // TODO: relative Redirects abfangen
         // TODO: verschachtelte IOExceptions abfangen
         $this->manualRedirects++;
         self::$logInfo && Logger::log('Performing manual redirect to: '.$response->getHeader('Location'), L_INFO, __CLASS__);

         $request  = HttpRequest::create()->setUrl($response->getHeader('Location'));
         $me       = __FUNCTION__;
         $response = $this->$me($request);
      }

      return $response;
   }


   /**
    * Gibt eine Beschreibung des letzten CURL-Fehlers zurück.
    *
    * @param  resource $hCurl - CURL-Handle
    *
    * @return string
    */
   private static function getError(&$hCurl) {
      $errorNo  = curl_errno($hCurl);
      $errorStr = curl_error($hCurl);

      if (isSet(self::$errors[$errorNo])) {
         $errorNo = self::$errors[$errorNo];
      }
      else {
         Logger::log('Unknown CURL error code: '.$errorNo, L_WARN, __CLASS__);
      }

      return "$errorNo ($errorStr)";
   }
}
