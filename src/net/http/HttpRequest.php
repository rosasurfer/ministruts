<?php
namespace rosasurfer\net\http;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;


/**
 * HttpRequest
 */
class HttpRequest extends Object {


    /** @var string */
    protected $url;

    /** @var string - HTTP method (default: GET) */
    protected $method = 'GET';

    /** @var string[] - user-defined HTTP headers */
    protected $headers = [];


    /**
     * Constructor
     *
     * Create a new HttpRequest.
     *
     * @param  string $url [optional] - url (default: none)
     */
    public function __construct($url = null) {
        if (isSet($url)) {
            $this->setUrl($url);
        }
    }


    /**
     * Create a new instance.
     *
     * @return static
     *
     * @deprecated
     */
    public static function create() {
        return new static();
    }


    /**
     * Return the request's HTTP method.
     *
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }


    /**
     * Set the request's HTTP method. Currently only GET and POST are implemented.
     *
     * @param  string $method
     *
     * @return $this
     */
    public function setMethod($method) {
        if (!is_string($method))                 throw new IllegalTypeException('Illegal type of parameter $method: '.getType($method));
        if ($method!=='GET' && $method!=='POST') throw new InvalidArgumentException('Invalid argument $method: '.$method);

        $this->method = $method;
        return $this;
    }


    /**
     * Return the request's URL.
     *
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }


    /**
     * Set the request's URL.
     *
     * @param  string $url
     *
     * @return $this
     */
    public function setUrl($url) {
        if (!is_string($url)) throw new IllegalTypeException('Illegal type of parameter $url: '.getType($url));

        // TODO: validate URL

        if (strPos($url, ' ') !== false)
            throw new InvalidArgumentException('Invalid argument $url: '.$url);

        $this->url = $url;
        return $this;
    }


    /**
     * Set an HTTP header. This method overwrites an existing header of the same name.
     *
     * @param  string      $name  - header name
     * @param  string|null $value - header value (an empty value removes an existing header)
     *
     * @return $this
     */
    public function setHeader($name, $value) {
        if (!is_string($name))                   throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!strLen($name))                      throw new InvalidArgumentException('Invalid argument $name: '.$name);
        if (isSet($value) && !is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

        $name  = trim($name);
        $value = trim($value);

        // drop existing headers of the same name (ignore case)
        $existing = \array_intersect_ukey($this->headers, [$name => '1'], 'strCaseCmp');
        foreach ($existing as $key => $v) {
            unset($this->headers[$key]);
        }

        // set new header if non-empty
        if (!empty($value)) {
            $this->headers[$name] = $value;
        }
        return $this;
    }


    /**
     * Add an HTTP header to the existing ones. Existing headers of the same name are not overwritten.
     *
     * @param  string $name  - header name
     * @param  string $value - header value
     *
     * @return $this
     *
     * @see http://stackoverflow.com/questions/3241326/set-more-than-one-http-header-with-the-same-name
     */
    public function addHeader($name, $value) {
        if (!is_string($name))  throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!strLen($name))     throw new InvalidArgumentException('Invalid argument $name: '.$name);

        if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
        if (!strLen($value))    throw new InvalidArgumentException('Invalid argument $value: '.$value);

        $name  = trim($name);
        $value = trim($value);

        // memorize and drop existing headers of the same name (ignore case)
        $existing = \array_intersect_ukey($this->headers, [$name => '1'], 'strCaseCmp');
        foreach ($existing as $key => $v) {
            unset($this->headers[$key]);
        }

        // combine existing and new header (see RFC), set combined header
        $existing[] = $value;
        $this->headers[$name] = join(', ', $existing);

        return $this;
    }


    /**
     * Return the request header with the specified name. This method returns the value of a single header.
     *
     * @param  string $name - header name (case is ignored)
     *
     * @return string|null - header value or NULL if no such header was found
     */
    public function getHeader($name) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!strLen($name))    throw new InvalidArgumentException('Invalid argument $name: '.$name);

        $headers = $this->getHeaders($name);
        if ($headers)
            return join(', ', $headers);    // combine multiple headers of the same name according to RFC
        return null;
    }


    /**
     * Return the request headers with the specified names. This method returns a key-value pair for each found header.
     *
     * @param  string|string[] $names [optional] - one or more header names (case is ignored)
     *                                             (default: without a name all headers are returned)
     *
     * @return string[] - array of name-value pairs or an empty array if no such headers were found
     */
    public function getHeaders($names = null) {
        if     (!isSet($names))    $names = [];
        elseif (is_string($names)) $names = [$names];
        elseif (is_array($names)) {
            foreach ($names as $i => $name) {
                if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $names['.$i.']: '.getType($name));
            }
        }
        else                           throw new IllegalTypeException('Illegal type of parameter $names: '.getType($names));

        // without a name return all headers
        if (!$names) {
            return $this->headers;
        }
        return \array_intersect_ukey($this->headers, \array_flip($names), 'strCaseCmp');
    }
}
