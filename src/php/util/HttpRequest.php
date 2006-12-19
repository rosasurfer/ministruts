<?
/**
 * HttpRequest
 */
class HttpRequest extends Object {

   private $debug = false;
   private $error = false;          // whether or not an error occurred (to avoid recursions)

   private $host;
   private $ip;
   private $port;
   private $uri;
   private $headers = array('User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8b) Gecko/20050217',
                            'Accept'     => 'text/html;q=0.9,text/plain;q=0.8,*/*;q=0.5',
                            'Connection' => 'close');
   private $cookieStore;
   private $followRedirects = true;

   private $socket;
   private $timeout = 60;
   private $responseCode;
   private $responseHeaders;
   private $responseHeaderMap;
   private $lcResponseHeaderMap;
   private $responseBody;


   /**
    * Constructor
    */
   function HttpRequest($host, $port, $uri, $headers = null) {
      if (!is_string($host)  || $host=='')           throw new InvalidArgumentException('Invalid host: '.$host);
      if (!is_int($port)     || $port < 1)           throw new InvalidArgumentException('Invalid port: '.$port);
      if (!is_string($uri)   || $uri=='')            throw new InvalidArgumentException('Invalid uri: '.$uri);
      if ($headers !== null && !is_array($headers)) throw new InvalidArgumentException('Invalid headers array: '.$headers);

      $this->debug = @$GLOBALS['debug'];
      $this->host = trim(strToLower($host));
      $this->port = $port;
      $this->uri = trim($uri);

      if ($headers !== null)
         $this->headers = array_merge($this->headers, $headers);

      if (!is_array(@$_SERVER['STORED_COOKIES']))
         $_SERVER['STORED_COOKIES'] = array();

      $this->cookieStore =& $_SERVER['STORED_COOKIES'];

      // falls ein Domainname übergeben wurde, dessen IP ermitteln
      if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $this->host, $matches) == 0) {
         $this->ip = getHostByName($this->host);
         if ($this->ip == $this->host)
            throw new RuntimeException('Cannot resolve ip address of: '.$host);
      }
      else {
         array_shift($matches);
         foreach ($matches as $value) {
            if ((int) $value > 255)
               throw new RuntimeException('Invalid ip address: '.$host);
         }
         $this->ip = $this->host;
      }
   }

   /**
    * Connect and read the HTTP response.
    */
   function connect() {
      if ($this->error)
         return false;

      if ($this->socket)
         throw new RuntimeException('Cannot reconnect already opened socket');

      // Socket öffnen
      $socket = fSockOpen('tcp://'.$this->ip, $this->port, $errorNo, $errorMsg, $this->timeout) or trigger_error("Could not open socket - error $errorNo: $errorMsg", E_USER_WARNING);
      if (!$socket) {
         $this->error = true;
         return false;
      }
      $data = stream_get_meta_data($socket);
      if ($data['timed_out']) {
         $this->error = true;
         trigger_error('Timeout on socket connection', E_USER_WARNING);
         return false;
      }
      socket_set_timeout($socket, $this->timeout);
      $this->socket =& $socket;

      // im Server gespeicherte Cookies suchen und zum Header hinzufügen
      $cookies = $this->getStoredCookies($this->host, $this->uri);
      if ($cookies) {
         $this->headers['Cookie'] = $cookies;
      }

      // Request senden
      $this->writeData("GET $this->uri HTTP/1.0");
      if ($this->host != $this->ip)
         $this->writeData("Host: $this->host");
      foreach ($this->headers as $header => $value) {
         $this->writeData("$header: $value");
      }
      $this->writeData('');

      // Debugging: Request-Header anzeigen
      if ($this->debug) {
         $headers = array();
         $headers[] = "GET $this->uri HTTP/1.0";
         if ($this->host != $this->ip)
            $headers[] = "Host: $this->host";
         foreach ($this->headers as $header => $value) {
            $headers[] = "$header: $value";
         }
         echoPre(join("\n", $headers));
      }

      // Response auslesen
      $headers = array();
      while (($line=trim(fGets($socket))) != '') {
         $headers[] = $line;
      }
      $data = stream_get_meta_data($socket);
      if ($data['timed_out']) {
         $this->error = true;
         trigger_error("Timeout on socket connection to http://$this->host:$this->port$this->uri", E_USER_WARNING);
         return false;
      }

      $body = null;
      while (!fEof($socket)) {
         $body .= fGets($socket);
      }
      $data = stream_get_meta_data($socket);
      if ($data['timed_out']) {
         $this->error = true;
         trigger_error("Timeout on socket connection to http://$this->host:$this->port$this->uri", E_USER_WARNING);
         return false;
      }

      $this->responseHeaders = $headers;
      $this->responseBody = $body;

      // Debugging: Response-Header anzeigen
      if ($this->debug)
         echoPre(join("\n", $headers));

      // Socket schließen
      fClose($socket);

      // gesendete Cookies speichern (specification: 'Set-Cookie: NAME=VALUE; expires=DATE; path=PATH; domain=DOMAIN_NAME; secure')
      $cookies = $this->getResponseHeader('set-cookie');
      if (sizeOf($cookies) > 0) {
         foreach ($cookies as $cookie) {
            $parts = explode(';', $cookie);
            $nameValue = $parts[0];
            array_shift($parts);

            $sentCookie = array();
            foreach ($parts as $part) {
               $pair = explode('=', trim($part));
               $sentCookie[$pair[0]] = $pair[1];
            }
            $pair = explode('=', trim($nameValue));
            $sentCookie['name'] = $pair[0];
            $sentCookie['value'] = $pair[1];

            if (isSet($sentCookie['expires'])) {
               // Cookie löschen, wenn DATE in der Vergangenheit liegt
               // continue;
            }
            if (!isSet($sentCookie['path'])) {
               $questMark = strPos($this->uri, '?');
               $hash = strPos($this->uri, '#');
               $pos = is_int($questMark) ? (is_int($hash) ? max($questMark, $hash) : $questMark) : (is_int($hash) ? $hash : false);
               $path = ($pos === false) ? $this->uri : subStr($this->uri, 0, $pos);
               $sentCookie['path'] = $path;
            }
            if (!isSet($sentCookie['domain'])) {
               $sentCookie['domain'] = $this->host;
            }
            else {
               if (subStr($sentCookie['domain'], 0,1) == '.')
                  $sentCookie['domain'] = subStr($sentCookie['domain'], 1);
               $sentCookie['domain'] = strToLower($sentCookie['domain']);
            }
            $key = $sentCookie['domain'].'|'.$sentCookie['path'].'|'.$sentCookie['name'];
            $this->cookieStore[$key] = $sentCookie;
         }
      }

      // Redirects mit neuem HttpRequest verfolgen
      if ($this->followRedirects && ($this->getResponseCode()==301 || $this->getResponseCode()==302)) {
         $host    = $this->host;
         $uri     = current($this->getResponseHeader('location'));
         $headers = array('Referer' => 'http://'.$this->host.$this->uri);
         $cookies = $this->getStoredCookies($host, $uri);
         if ($cookies) {
            $headers['Cookie'] = $cookies;
         }
         if ($this->debug)
            echoPre("redirection to: $uri");

         $request = new HttpRequest($host, 80, $uri, $headers);

         if (($this->responseHeaders = $request->getResponseHeaders()) === null || ($this->responseBody = $request->getResponseBody()) === null) {
            $this->error = true;
            return false;
         }
      }
      return true;
   }

   /**
    * Get the HTTP response body.
    */
   function getResponseBody() {
      if ($this->responseHeaders === null) {
         if (!$this->connect())
            return null;
      }
      return $this->responseBody;
   }

   /**
    * Get all HTTP response headers.
    */
   function getResponseHeaders() {
      if ($this->responseHeaders === null) {
         if (!$this->connect())
            return null;
      }
      return $this->responseHeaders;
   }

   /**
    * Get all HTTP response headers as an associative array (a map).
    */
   function getResponseHeaderMap() {
      if ($this->responseHeaderMap === null) {
         if (($headers=$this->getResponseHeaders()) === null)
            return null;
         $map = array();

         foreach ($headers as $header) {
            $pos = strPos($header, ': ');
            if (is_int($pos)) {
               $name = subStr($header, 0, $pos);
               $value = subStr($header, $pos+2);
               $map[$name] = $value;
            }
            else {
               $map[$header] = null;
            }
         }
         $this->responseHeaderMap = $map;
      }
      return $this->responseHeaderMap;
   }

   /**
    * Get the HTTP response headers with the specified name.
    */
   function getResponseHeader($name) {
      $map =& $this->lcResponseHeaderMap;
      if ($map === null) {
         if (($temp=$this->getResponseHeaderMap()) === null)
            return null;
         $map = array_change_key_case($temp, CASE_LOWER);
      }
      $name = strToLower($name);
      $foundHeaders = array();
      foreach($map as $key => $value) {
         if ($key == $name) {
            $foundHeaders[] = $value;
         }
      }
      return $foundHeaders;
   }

   /**
    * Get the HTTP response status code.
    */
   function getResponseCode() {
      if ($this->responseCode === null) {
         if (($headers=$this->getResponseHeaders()) === null)
            return null;
         $statusLine = $headers[0];
         if (strPos($statusLine, 'HTTP/1.') !== 0)
            throw new RuntimeException('Invalid HTTP status line: '.$statusLine);

         $tokens = explode(' ', $statusLine);
         $status = (int) $tokens[1];
         if ($tokens[1] != "$status")
            throw new RuntimeException('Invalid HTTP status code: '.$statusLine);

         $this->responseCode = $status;
      }
      return $this->responseCode;
   }

   /**
    * Get all stored cookies matching the specified domain and path.
    */
   function getStoredCookies($domain, $path) {
      $cookies = array();
      $domain = strToLower($domain);
      $storedCookies =& $this->cookieStore;
      foreach ($storedCookies as $cookie) {
         $cDomain = $cookie['domain'];
         if ((striStr($domain, '.'.$cDomain)==$cDomain || $domain==$cDomain) && strPos($path, $cookie['path'])===0) {
            $cookies[] = $cookie['name'].'='.$cookie['value'];
         }
      }
      return join('; ', $cookies);
   }

   /**
    * Daten in die Socketverbindung schreiben
    */
   function writeData($data) {
      $count = fWrite($this->socket, $data."\r\n", strLen($data)+2);

      if ($count != strLen($data)+2)
         throw new RuntimeException('Error writing to socket, length of data: '.(strLen($data)+2).', bytes written: '.$count."\ndata: ".$data);
   }
}
?>
