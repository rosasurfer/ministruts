<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\http;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\InvalidValueException;


/**
 * HttpRequest
 */
class HttpRequest extends CObject {


    /** @var string */
    protected string $url = '';

    /** @var string - HTTP method (default: GET) */
    protected string $method = 'GET';

    /** @var string[] - user-defined HTTP headers */
    protected array $headers = [];


    /**
     * Constructor
     *
     * @param ?string $url [optional] - URL (default: none)
     */
    public function __construct(?string $url = null) {
        if (isset($url)) {
            $this->setUrl($url);
        }
    }


    /**
     * Return the request's HTTP method.
     *
     * @return string
     */
    public function getMethod(): string {
        return $this->method;
    }


    /**
     * Set the request's HTTP method.  Currently only GET and POST are implemented.
     *
     * @param  string $method
     *
     * @return $this
     */
    public function setMethod(string $method): self {
        if ($method!=='GET' && $method!=='POST') throw new InvalidValueException('Invalid parameter $method: '.$method);
        $this->method = $method;
        return $this;
    }


    /**
     * Return the request's URL.
     *
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }


    /**
     * Set the request's URL.
     *
     * @param  string $url
     *
     * @return $this
     */
    public function setUrl(string $url): self {
        // TODO: validate URL
        if (strpos($url, ' ') !== false) throw new InvalidValueException('Invalid parameter $url: '.$url);
        $this->url = $url;
        return $this;
    }


    /**
     * Set an HTTP header.  This method overwrites an existing header of the same name.
     *
     * @param  string  $name  - header name
     * @param  ?string $value - header value (NULL removes an existing header)
     *
     * @return $this
     */
    public function setHeader(string $name, ?string $value): self {
        $name = trim($name);
        if (!strlen($name)) throw new InvalidValueException('Invalid parameter $name: "" (empty)');

        // drop existing headers of the same name (case-insensitive)
        /** @phpstan-var callable-string $func*/
        $func = 'strcasecmp';
        $existing = \array_intersect_ukey($this->headers, [$name => '1'], $func);

        foreach ($existing as $key => $_) {
            unset($this->headers[$key]);
        }

        // set new header if non-empty
        if (isset($value)) {
            $this->headers[$name] = trim($value);
        }
        return $this;
    }


    /**
     * Add an HTTP header to the existing ones.  Existing headers of the same name are not overwritten.
     *
     * @param  string $name  - header name
     * @param  string $value - header value
     *
     * @return $this
     *
     * @see    https://stackoverflow.com/questions/3241326/set-more-than-one-http-header-with-the-same-name
     */
    public function addHeader(string $name, string $value): self {
        if (!strlen($name))  throw new InvalidValueException('Invalid parameter $name: '.$name);
        if (!strlen($value)) throw new InvalidValueException('Invalid parameter $value: '.$value);

        $name  = trim($name);
        $value = trim($value);

        // memorize and drop existing headers of the same name (ignore case)
        /** @phpstan-var callable-string $func*/
        $func = 'strcasecmp';
        $existing = \array_intersect_ukey($this->headers, [$name => '1'], $func);

        foreach ($existing as $key => $_) {
            unset($this->headers[$key]);
        }

        // combine existing and new header (see RFC), set combined header
        $existing[] = $value;
        $this->headers[$name] = join(', ', $existing);

        return $this;
    }


    /**
     * Return the request header with the specified name.  This method returns the value of a single header.
     *
     * @param  string $name - header name (case insensitive)
     *
     * @return ?string - header value or NULL if no such header was found
     */
    public function getHeader(string $name): ?string {
        if (!strlen($name)) throw new InvalidValueException('Invalid parameter $name: '.$name);

        $headers = $this->getHeaders($name);
        if ($headers) {
            return join(', ', $headers);    // combine multiple headers of the same name according to RFC
        }
        return null;
    }


    /**
     * Return the request headers with the specified names.  This method returns a key-value pair for each found header.
     *
     * @param  string ...$names [optional] - one or more header names (case insensitive)
     *                                       (default: without a name all headers are returned)
     *
     * @return string[] - array of name-value pairs or an empty array if no such headers were found
     */
    public function getHeaders(string ...$names): array {
        // without a name return all headers
        if (!$names) {
            return $this->headers;
        }
        /** @phpstan-var callable-string $func*/
        $func = 'strcasecmp';
        return \array_intersect_ukey($this->headers, \array_flip($names), $func);
    }
}
