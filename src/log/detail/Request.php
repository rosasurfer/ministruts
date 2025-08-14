<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\detail;

use Throwable;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;

use function rosasurfer\ministruts\preg_replace;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\NL;

/**
 * Request
 *
 * An object to stringify details of the current HTTP request.
 */
class Request extends CObject {

    /** @var ?array<string, string> - all request headers (no multi-field headers) */
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
     * Return all headers as an associative array of header values. Source is the $_SERVER array,
     * any raw multi-field headers are already combined into a single header line by PHP.
     *
     * @return array<string, string> - associative array of header values
     */
    protected function getNormalizedHeaders(): array {
        if (!isset($this->headers)) {
            // content related headers
            $contentNames = [
                'CONTENT_TYPE'   => 'Content-Type',
                'CONTENT_LENGTH' => 'Content-Length',
                'CONTENT_MD5'    => 'Content-MD5',
            ];

            // headers with commonly used non-standard spelling (not a RFC requirement)
            $fixHeaderNames = [
                'CDN'     => 'CDN',
                'DNT'     => 'DNT',
                'SEC_GPC' => 'Sec-GPC',
                'X_CDN'   => 'X-CDN',
            ];
            $headers = [];

            foreach ($_SERVER as $name => $value) {
                while (substr($name, 0, 9) == 'REDIRECT_') {
                    $name = substr($name, 9);
                    if (isset($_SERVER[$name])) {
                        continue 2;
                    }
                }
                if (substr($name, 0, 5) == 'HTTP_') {
                    $name = substr($name, 5);
                    // skip content headers
                    if (!isset($contentNames[$name])) {
                        $name = $fixHeaderNames[$name] ?? str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($name))));
                        $headers[$name] = $value;
                    }
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

            // finally group all content related headers at the end
            foreach ($contentNames as $name => $value) {
                if (isset($_SERVER[$name])) {
                    $headers[$contentNames[$name]] = $_SERVER[$name];
                }
            }
            $this->headers = $headers;
        }
        return $this->headers;
    }


    /**
     * Return the received headers with the specified names as an associative array of header values.
     * Any received multi-field headers are combined into a single header line.
     *
     * @param  string ...$names [optional] - zero or more header names (default: return all received headers)
     *
     * @return array<string, string> - associative array of header values
     */
    public function getHeaders(string ...$names): array {
        $allHeaders = $this->getNormalizedHeaders();
        if (!$names) {
            return $allHeaders;
        }
        /** @phpstan-var callable-string $func*/
        $func = 'strcasecmp';
        return array_intersect_ukey($allHeaders, array_flip($names), $func);
    }


    /**
     * Return the received value of the header with the specified name.
     *
     * @param  string $name - header name
     *
     * @return ?string - received header value, or NULL if no such header was received
     */
    public function getHeaderValue(string $name): ?string {
        $header = $this->getHeaders($name);

        foreach ($header as $value) {
            return $value;
        }
        return null;
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
     * Return the content of the request (the body). For file uploads the method doesn't return the uploaded content.
     * Instead it returns available metadata.
     *
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - request body or metadata
     */
    public function getContent(?ContentFilter $filter = null): string {
        if (!isset($this->content)) {
            $content = '';

            if ($_POST) {
                $post = $filter ? $filter->filterValues($_POST) : $_POST;
                $content .= '$_POST => '.trim(print_r($post, true)).NL;
            }
            else {
                $input = file_get_contents('php://input');  // not available with content type 'multipart/form-data'
                if (strlen($input)) {
                    if ($filter && $this->getContentType()=='application/json') {
                        try {
                            $values = json_decode($input, true, 512, JSON_BIGINT_AS_STRING | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
                            if (is_array($values)) {
                                $values = $filter->filterValues($values);
                                $input = json_encode($values, JSON_THROW_ON_ERROR);
                            }
                        }
                        catch (Throwable $th) {}            // intentionally eat it
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
                              // nginx proxy                // apache proxy                     // no proxy
            $addr = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
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
    public static function stringify(?ContentFilter $filter = null): string {
        $request = self::instance();

        // request
        $method   = $_SERVER['REQUEST_METHOD' ] ?? '';
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
        $uri      = $request->getUri($filter);
        $result = "$method $uri $protocol".NL;

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
                $authHeader = $headers['Authorization'];
                $redacted = ContentFilter::SUBSTITUTE;

                if (strStartsWith($authHeader, 'Basic')) {
                    $authHeader = "Basic $redacted:$redacted";
                }
                elseif (strStartsWith($authHeader, 'Digest')) {
                    $authHeader = preg_replace('/(c?nonce|response)="[^"]*"/', "\$1=\"$redacted\"", $authHeader);
                }
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
        if (strlen($content)) {
            $result .= NL.trim(substr($content, 0, 2_048)).NL;      // limit the request body to 2048 bytes
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
