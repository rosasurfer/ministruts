<?
/**
 * HttpRequest
 */
class HttpRequest {

   var $debug = false;
   var $error = false;        // whether or not an error occurred (to avoid recursions)

   var $host;
   var $ip;
   var $port;
   var $uri;
   var $headers = Array('User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8b) Gecko/20050217',
                        'Accept'     => 'text/html;q=0.9,text/plain;q=0.8,*/*;q=0.5',
                        'Connection' => 'close');
   var $cookieStore;
   var $followRedirects = true;

   var $socket;
   var $timeout = 60;
   var $responseCode;
   var $responseHeaders;
   var $responseHeaderMap;
   var $lcResponseHeaderMap;
   var $responseBody;


   /**
    * Constructor
    */
   function HttpRequest($host, $port, $uri, $headers = null) {
      if (!is_string($host)  || !$host)              trigger_error('Invalid host: '.$host, E_USER_ERROR);
      if (!is_int($port)     || $port < 1)           trigger_error('Invalid port: '.$port, E_USER_ERROR);
      if (!is_string($uri)   || !$uri)               trigger_error('Invalid uri: '.$uri, E_USER_ERROR);
      if (!is_null($headers) && !is_array($headers)) trigger_error('Invalid argument headers: '.$headers, E_USER_ERROR);

      $this->debug = @$GLOBALS['debug'];
      $this->host = trim(strToLower($host));
      $this->port = $port;
      $this->uri = trim($uri);

      if (!is_null($headers))
         $this->headers =& array_merge($this->headers, $headers);

      if (!is_array(@$_SERVER['STORED_COOKIES']))
         $_SERVER['STORED_COOKIES'] = Array();

      $this->cookieStore =& $_SERVER['STORED_COOKIES'];

      // falls ein Domainname übergeben wurde, dessen IP ermitteln
      if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $this->host, $matches) == 0) {
         $this->ip = getHostByName($this->host);
         ($this->ip == $this->host) && trigger_error('Cannot resolve ip address of: '.$host, E_USER_ERROR);
      }
      else {
         array_shift($matches);
         foreach ($matches as $value) {
            ((int) $value > 255) && trigger_error('Invalid ip address: '.$host, E_USER_ERROR);
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
      $this->socket && trigger_error('Cannot reconnect already opened socket', E_USER_ERROR);

      // Socket öffnen
      $socket = fSockOpen('tcp://'.$this->ip, $this->port, $errorNo, $errorMsg, $this->timeout) or trigger_error("Could not open socket - error $errorNo: $errorMsg", E_USER_WARNING);
      if (!$socket) {
         $this->error = true;
         return false;
      }
      $data =& stream_get_meta_data($socket);
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
         $headers = Array();
         $headers[] = "GET $this->uri HTTP/1.0";
         if ($this->host != $this->ip)
            $headers[] = "Host: $this->host";
         foreach ($this->headers as $header => $value) {
            $headers[] = "$header: $value";
         }
         echoPre(join("\n", $headers));
      }

      // Response auslesen
      $headers = Array();
      while (($line=trim(fGets($socket))) != '') {
         $headers[] = $line;
      }
      $data =& stream_get_meta_data($socket);
      if ($data['timed_out']) {
         $this->error = true;
         trigger_error("Timeout on socket connection to http://$this->host:$this->port$this->uri", E_USER_WARNING);
         return false;
      }

      $body = null;
      while (!fEof($socket)) {
         $body .= fGets($socket);
      }
      $data =& stream_get_meta_data($socket);
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
            $parts =& explode(';', $cookie);
            $nameValue = $parts[0];
            array_shift($parts);

            $sentCookie = Array();
            foreach ($parts as $part) {
               $pair =& explode('=', trim($part));
               $sentCookie[$pair[0]] = $pair[1];
            }
            $pair =& explode('=', trim($nameValue));
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
         $uri     =& current($this->getResponseHeader('location'));
         $headers = Array('Referer' => 'http://'.$this->host.$this->uri);
         $cookies = $this->getStoredCookies($host, $uri);
         if ($cookies) {
            $headers['Cookie'] = $cookies;
         }
         if ($this->debug)
            echoPre("redirection to: $uri");

         $request =& new HttpRequest($host, 80, $uri, $headers);

         if (is_null($this->responseHeaders = $request->getResponseHeaders()) || is_null($this->responseBody = $request->getResponseBody())) {
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
      if (is_null($this->responseHeaders)) {
         if (!$this->connect())
            return null;
      }
      return $this->responseBody;
   }

   /**
    * Get all HTTP response headers.
    */
   function getResponseHeaders() {
      if (is_null($this->responseHeaders)) {
         if (!$this->connect())
            return null;
      }
      return $this->responseHeaders;
   }

   /**
    * Get all HTTP response headers as an associative array (a map).
    */
   function getResponseHeaderMap() {
      if (is_null($this->responseHeaderMap)) {
         if (is_null($headers=$this->getResponseHeaders()))
            return null;
         $map = Array();

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
      if (is_null($map)) {
         if (is_null($temp=$this->getResponseHeaderMap()))
            return null;
         $map =& array_change_key_case($temp, CASE_LOWER);
      }
      $name = strToLower($name);
      $foundHeaders = Array();
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
      if (is_null($this->responseCode)) {
         if (is_null($headers=$this->getResponseHeaders()))
            return null;
         $statusLine = $headers[0];

         if (strPos($statusLine, 'HTTP/1.') !== 0) {
            trigger_error('Invalid HTTP status line: '.$statusLine, E_USER_ERROR);
         }
         $tokens =& explode(' ', $statusLine);
         $status = (int) $tokens[1];
         if ($tokens[1] != "$status") {
            trigger_error('Invalid HTTP status code: '.$statusLine, E_USER_ERROR);
         }
         $this->responseCode = $status;
      }
      return $this->responseCode;
   }

   /**
    * Get all stored cookies matching the specified domain and path.
    */
   function getStoredCookies($domain, $path) {
      $cookies = Array();
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
         trigger_error('Error writing to socket, length of data: '.(strLen($data)+2).', bytes written: '.$count."\ndata: ".$data, E_USER_ERROR);
   }
}
?>
