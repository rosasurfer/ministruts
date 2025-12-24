<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\detail;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;

use function rosasurfer\ministruts\preg_replace;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\toString;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\NL;

/**
 * Request
 *
 * An object to access details of the current HTTP request.
 */
class Request extends CObject {

    /** @var ?array<string, string> - all received request headers */
    protected ?array $headers = null;

    /** @var ?array<string, string[]> - normalized metadata of uploaded files */
    protected ?array $files = null;

    /** @var ?string - request body */
    protected ?string $content = null;

    /** @var ?string - host URL */
    protected ?string $hostUrl = null;

    /** @var ?string - remote IP of the request */
    protected ?string $remoteIP = null;

    /** @var Request */
    protected static self $instance;


    /**
     * Constructor
     */
    private function __construct() {
        if (CLI) throw new IllegalStateException('Cannot read HTTP request in CLI mode');
    }


    /**
     * Return all received headers as an associative array of header names/values. Source is the $_SERVER[] array.
     * Multiple headers of the same name are already collapsed into a single header by PHP.
     *
     * @return array<string, string> - associative array of header values
     */
    protected function getNormalizedHeaders(): array {
        if (!isset($this->headers)) {
            // headers with custom name representation
            $customHeaders = [
                'CDN'                => 'CDN',
                'CF_CONNECTING_IP'   => 'CF-Connecting-IP',
                'CLIENT_IP'          => 'Client-IP',
                'CONTENT_ID'         => 'Content-ID',
                'CONTENT_MD5'        => 'Content-MD5',
                'DASL'               => 'DASL',
                'DAV'                => 'DAV',
                'DNT'                => 'DNT',
                'ETAG'               => 'ETag',
                'MIME_VERSION'       => 'MIME-Version',
                'SEC_GPC'            => 'Sec-GPC',
                'TE'                 => 'TE',
                'TRUE_CLIENT_IP'     => 'True-Client-IP',
                'WWW_AUTHENTICATE'   => 'WWW-Authenticate',
                'X_CDN'              => 'X-CDN',
                'X_MINISTRUTS_UI'    => 'X-Ministruts-UI',
                'X_REAL_IP'          => 'X-Real-IP',
                'X_UP_FORWARDED_FOR' => 'X-UP-Forwarded-For',
            ];
            $headers = [];

            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
            }
            else {
                foreach ($_SERVER as $name => $value) {
                    while (substr($name, 0, 9) == 'REDIRECT_') {
                        $name = substr($name, 9);
                        if (isset($_SERVER[$name])) {
                            continue 2;
                        }
                    }
                    if (substr($name, 0, 8) == 'CONTENT_') {
                        $name = $customHeaders[$name] ?? str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($name))));
                        $headers[$name] = $value;
                    }
                    elseif (substr($name, 0, 5) == 'HTTP_') {
                        $name = substr($name, 5);
                        $name = $customHeaders[$name] ?? str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($name))));
                        $headers[$name] = $value;
                    }
                }

                // reconstruct an existing 'Authorization' header
                if (!isset($headers['Authorization']) && isset($_SERVER['AUTH_TYPE'])) {
                    $authType = $_SERVER['AUTH_TYPE'];
                    if ($authType == 'Basic') {
                        $user = $_SERVER['PHP_AUTH_USER'] ?? '';
                        $pass = $_SERVER['PHP_AUTH_PW'] ?? '';
                        $headers['Authorization'] = 'Basic '.base64_encode("$user:$pass");
                    }
                    if ($authType == 'Digest') {
                        $digest = $_SERVER['PHP_AUTH_DIGEST'] ?? '';
                        $headers['Authorization'] = "Digest $digest";
                    }
                }
            }

            // sort headers
            uksort($headers, 'strnatcasecmp');

            // move "Host" header to the beginning
            $value = $headers[$name = 'Host'] ?? null;
            if (isset($value)) {
                unset($headers[$name]);
                $headers = [$name => $value] + $headers;
            }

            $this->headers = $headers;
        }
        return $this->headers;
    }


    /**
     * Return the headers with the specified names as an associative array of header names/values.
     * Multiple headers of the same name are already collapsed into a single header by PHP.
     *
     * @param  string ...$names [optional] - one or more header names (default: all headers)
     *
     * @return array<string, string> - header names/values
     */
    public function getHeaders(string ...$names): array {
        $allHeaders = $this->getNormalizedHeaders();
        if (!$names) {
            return $allHeaders;
        }
        return array_intersect_ukey($allHeaders, array_flip($names), 'strcasecmp');
    }


    /**
     * Return the value of the header with the specified name.
     *
     * @param  string $name - header name
     *
     * @return ?string - header value or NULL if no such header was received
     */
    public function getHeaderValue(string $name): ?string {
        $header = $this->getHeaders($name);
        return $header ? reset($header) : null;
    }


    /**
     * Return a representation of the files uploaded with the request. The PHP array structure of $_FILES is converted
     * to normalized arrays.
     *
     * @return array<string, string[]> - associative array of files
     */
    public function getFiles(): array {
        if (!isset($this->files)) {
            $normalizeArrayLevel = null;
            $normalizeArrayLevel = static function(array $file) use (&$normalizeArrayLevel) {
                if (isset($file['name']) && is_array($file['name'])) {
                    $properties = array_keys($file);
                    $normalized = [];
                    foreach ($file['name'] as $name => $_) {
                        foreach ($properties as $property) {
                            $normalized[$name][$property] = $file[$property][$name];
                        }
                        $normalized[$name] = $normalizeArrayLevel($normalized[$name]);
                    }
                    $file = $normalized;
                }
                return $file;
            };

            $this->files = [];
            foreach ($_FILES as $key => $file) {
                $this->files[$key] = $normalizeArrayLevel($file);
            }
        }
        return $this->files;
    }


    /**
     * Return the content of the request (the body). For file uploads the method available metadata.
     *
     * @param  ?ContentFilter $filter [optional] - a content filter to apply (default: none)
     *
     * @return string - request body or metadata
     */
    public function getContent(?ContentFilter $filter = null): string {
        if (!isset($this->content)) {
            $content = '';

            if ($_GET) {
                $get = $filter ? $filter->filterValues($_GET) : $_GET;
                $content .= '$_GET => '.trim(print_r($get, true)).NL;
            }
            if ($_POST) {
                $post = $filter ? $filter->filterValues($_POST) : $_POST;
                $content .= '$_POST => '.trim(print_r($post, true)).NL;
            }
            else {
                $input = file_get_contents('php://input');      // not available with content type 'multipart/form-data'
                if (strlen($input)) {
                    $contentType = $this->getContentType() ?? 'application/json';
                    if ($contentType == 'application/json') {
                        //if ($filter) {                        // filter a possible JSON content
                        //    try {
                        //        $values = json_decode_or_throw($input, true, 512, JSON_BIGINT_AS_STRING | JSON_INVALID_UTF8_SUBSTITUTE);
                        //        if (is_array($values)) {
                        //            $values = $filter->filterValues($values);
                        //            $input = json_encode_or_throw($values);
                        //        }
                        //    }
                        //    catch (Throwable $ex) {}
                        //}
                        $input = toString($input);              // format a possible JSON content
                    }
                    $content .= trim($input).NL;
                }
            }

            if ($_FILES) {
                $content .= '$_FILES => '.trim(print_r($this->getFiles(), true)).NL;
            }
            $this->content = $content;
        }
        return $this->content;
    }


    /**
     * Return the "Content-Type" header of the request. If a list of "Content-Type" headers has been
     * received the first one is returned.
     *
     * @return ?string - "Content-Type" header value, or NULL if no "Content-Type" header was received
     */
    public function getContentType(): ?string {
        $contentType = $this->getHeaderValue('Content-Type');

        if ($contentType) {
            $values = explode(',', $contentType, 2);
            $contentType = array_shift($values);

            $values = explode(';', $contentType, 2);
            $contentType = trim(array_shift($values));
        }
        return $contentType;
    }


    /**
     * Return the remote IP address the request was made from.
     *
     * @return string - IP address or an empty string if the IP address is unknown
     */
    public static function getRemoteIP(): string {
        $request = self::instance();

        if (!isset($request->remoteIP)) {
            $addr = $_SERVER['HTTP_X_REAL_IP']              // nginx and others
                 ?? $_SERVER['HTTP_TRUE_CLIENT_IP']         // Akamai, Cloudflare Enterprise
                 ?? $_SERVER['HTTP_CF_CONNECTING_IP']       // Cloudflare
                 ?? $_SERVER['HTTP_X_FORWARDED_FOR']        // de facto standard
                 ?? $_SERVER['HTTP_X_UP_FORWARDED_FOR']     // legacy mobile
                 ?? $_SERVER['HTTP_CLIENT_IP']              // legacy proxies
                 ?? $_SERVER['REMOTE_ADDR'] ?? '';
            $request->remoteIP = trim(explode(',', $addr, 2)[0]);
        }
        return $request->remoteIP;
    }


    /**
     * Return the remote host name the request was made from.
     *
     * @return string - host name or an empty string if the name cannot be resolved
     */
    public static function getRemoteHost(): string {
        $request = self::instance();

        $ip = $request->getRemoteIP();
        $host = @gethostbyaddr($ip);            // intentionally suppress DNS resolver errors
        if ($host === $ip) {
            $host = false;
        }
        return $host ?: '';
    }


    /**
     * Return the host name the request was made to (the value of the received "Host" header).
     *
     * @return string - host name, e.g. "a.domain.tld"
     */
    public function getHostName(): string {
        // nginx doesn't set $_SERVER[SERVER_NAME] automatically to $_SERVER[HTTP_HOST]
        if (!empty($_SERVER['HTTP_HOST'])) {
            $hostname = strtolower(trim($_SERVER['HTTP_HOST']));
            $hostname = strLeftTo($hostname, ':');
            if (strlen($hostname) > 0) {
                return $hostname;
            }
        }
        return $_SERVER['SERVER_NAME'];
    }


    /**
     * Return the root URL of the server the request was made to.
     * This value ends with a slash "/".
     *
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - root URL: protocol + hostname + port + "/" <br/>
     *                  e.g. "https://a.domain.tld/"
     */
    public function getHostUrl(?ContentFilter $filter = null): string {
        if (!isset($this->hostUrl)) {
            $protocol = $this->isSecure() ? 'https':'http';
            $host     = $this->getHostName();
            $port     = ':'.$_SERVER['SERVER_PORT'];
            if ($protocol.$port=='http:80' || $protocol.$port=='https:443') {
                $port = '';
            }
            $this->hostUrl = $protocol.'://'.$host.$port.'/';
        }
        return $filter ? $filter->filterUri($this->hostUrl) : $this->hostUrl;
    }


    /**
     * Return the URI of the request (i.e. the value in the first line of the HTTP protocol).
     * This value starts with a slash "/".
     *
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - URI: path + query-string + anchor <br/>
     *                  e.g. "/path/application/module/foo/bar.html?key=value"
     */
    public function getUri(?ContentFilter $filter = null): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return $filter ? $filter->filterUri($uri) : $uri;
    }


    /**
     * Return the full URL of the request.
     *
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - full URL: protocol + hostname + port + path + query-string + anchor <br/>
     *                  e.g. "http://a.domain.tld/path/application/module/foo/bar.html?key=value"
     */
    public static function getUrl(?ContentFilter $filter = null): string {
        $request = self::instance();
        $hostUrl = $request->getHostUrl($filter);
        $uri     = $request->getUri($filter);
        return $hostUrl.substr($uri, 1);
    }


    /**
     * Whether the request was made over a secure connection (HTTPS).
     *
     * @return bool
     */
    public function isSecure(): bool {
        return !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off';
    }


    /**
     * Return a readable string representation of the current HTTP request.
     *
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string
     */
    public static function toString(?ContentFilter $filter = null): string {
        $request = self::instance();

        // request
        $method   = $_SERVER['REQUEST_METHOD' ] ?? '';
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
        $uri      = $request->getUri($filter);
        $result   = "$method $uri $protocol".NL;

        // headers
        $headers = $request->getHeaders();
        if ($filter) {
            if (isset($headers['Referer'])) {
                $headers['Referer'] = $filter->filterUri($headers['Referer']);
            }
            if (isset($headers['Cookie'])) {
                $cookies = explode('; ', $headers['Cookie']);
                foreach ($cookies as $i => $cookie) {
                    $args = explode('=', $cookie, 2);
                    if (sizeof($args) > 1) {
                        $cookies[$i] = $args[0].'='.$filter->filterValue($args[0], $args[1]);
                    }
                }
                $headers['Cookie'] = join('; ', $cookies);
            }
            if (isset($headers['Authorization'])) {
                $redacted = ContentFilter::SUBSTITUTE;
                $authHeader = preg_replace('/^(\S+)\s/', "\$1 $redacted", $headers['Authorization']);
                $headers['Authorization'] = $authHeader;
            }
        }

        $maxLen = 0;
        foreach ($headers as $name => $value) {
            $maxLen = max(strlen($name), $maxLen);
        }
        $maxLen++;                                                  // plus one char for the colon ':'
        foreach ($headers as $name => $value) {
            $result .= str_pad($name.':', $maxLen).' '.$value.NL;
        }

        // content (request body)
        $content = $request->getContent($filter);
        if ($content != '') {
            $result .= NL.trim(substr($content, 0, 2_048)).NL;      // limit the request body to 2KB
        }

        return $result;
    }


    /**
     * Return the object instance (there can be only one).
     *
     * @return Request
     */
    public static function instance(): self {
        self::$instance ??= new self();
        return self::$instance;
    }
}
