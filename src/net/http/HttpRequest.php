<?php
namespace rosasurfer\net\http;

use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\InvalidTypeException;


/**
 * HttpRequest
 */
class HttpRequest extends CObject {


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
     * @param  string $url [optional] - URL (default: none)
     */
    public function __construct($url = null) {
        if (isset($url)) {
            $this->setUrl($url);
        }
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
     * Set the request's HTTP method&#46;  Currently only GET and POST are implemented.
     *
     * @param  string $method
     *
     * @return $this
     */
    public function setMethod($method) {
        Assert::string($method);
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
        Assert::string($url);
        // TODO: validate URL
        if (strpos($url, ' ') !== false)throw new InvalidArgumentException('Invalid argument $url: '.$url);
        $this->url = $url;
        return $this;
    }


    /**
     * Set an HTTP header&#46;  This method overwrites an existing header of the same name.
     *
     * @param  string      $name  - header name
     * @param  string|null $value - header value (an empty value removes an existing header)
     *
     * @return $this
     */
    public function setHeader($name, $value) {
        Assert::string($name, '$name');
        if (!strlen($name)) throw new InvalidArgumentException('Invalid argument $name: '.$name);
        Assert::nullOrString($value, '$value');

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
     * Add an HTTP header to the existing ones&#46;  Existing headers of the same name are not overwritten.
     *
     * @param  string $name  - header name
     * @param  string $value - header value
     *
     * @return $this
     *
     * @see http://stackoverflow.com/questions/3241326/set-more-than-one-http-header-with-the-same-name
     */
    public function addHeader($name, $value) {
        Assert::string($name,  '$name');
        Assert::string($value, '$value');
        if (!strlen($name))     throw new InvalidArgumentException('Invalid argument $name: '.$name);
        if (!strlen($value))    throw new InvalidArgumentException('Invalid argument $value: '.$value);

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
     * Return the request header with the specified name&#46;  This method returns the value of a single header.
     *
     * @param  string $name - header name (case is ignored)
     *
     * @return string|null - header value or NULL if no such header was found
     */
    public function getHeader($name) {
        Assert::string($name);
        if (!strlen($name)) throw new InvalidArgumentException('Invalid argument $name: '.$name);

        $headers = $this->getHeaders($name);
        if ($headers)
            return join(', ', $headers);    // combine multiple headers of the same name according to RFC
        return null;
    }


    /**
     * Return the request headers with the specified names&#46;  This method returns a key-value pair for each found header.
     *
     * @param  string|string[] $names [optional] - one or more header names (case is ignored)
     *                                             (default: without a name all headers are returned)
     *
     * @return string[] - array of name-value pairs or an empty array if no such headers were found
     */
    public function getHeaders($names = null) {
        if     (!isset($names))    $names = [];
        elseif (is_string($names)) $names = [$names];
        elseif (is_array($names)) {
            foreach ($names as $i => $name) {
                Assert::string($name, '$names['.$i.']');
            }
        }
        else throw new InvalidTypeException('Illegal type of parameter $names: '.gettype($names));

        // without a name return all headers
        if (!$names) {
            return $this->headers;
        }
        return \array_intersect_ukey($this->headers, \array_flip($names), 'strCaseCmp');
    }
}
