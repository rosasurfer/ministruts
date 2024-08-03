<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\detail;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\core\exception\InvalidTypeException;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\NL;


/**
 * Request
 *
 * An object to stringify the current HTTP request.
 */
final class Request extends CObject {


    /** @var ?array<string, string> - request headers */
    private $headers = null;

    /** @var ?array<string, string[]> - normalized metadata of uploaded files */
    private $files = null;

    /** @var ?string - request body */
    private $content = null;

    /** @var ?self */
    private static $instance = null;


    /**
     * Create a new instance.
     */
    private function __construct() {
        if (CLI) throw new IllegalStateException('Cannot read HTTP request in CLI mode');
    }


    /**
     * Return all headers with the specified name as an associative array of header values (in transmitted order).
     *
     * @param  string|string[] $names [optional] - one or more header names (default: all headers)
     *
     * @return array<string, string> - associative array of header values
     */
    private function getHeaders($names = []) {
        if (is_string($names)) {
            $names = [$names];
        }
        elseif (!is_array($names)) throw new InvalidTypeException('Invalid type of parameter $names: '.gettype($names));

        if (!isset($this->headers)) {
            $fixHeaderNames = ['CDN'=>1, 'DNT'=>2, 'X-CDN'=>3];
            $headers = [];

            foreach ($_SERVER as $name => $value) {
                while (substr($name, 0, 9) == 'REDIRECT_') {
                    $name = substr($name, 9);
                    if (isset($_SERVER[$name])) continue 2;
                }
                if (substr($name, 0, 5) == 'HTTP_') {
                    $name = substr($name, 5);
                    if (!isset($fixHeaderNames[$name]))
                        $name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($name))));
                    $headers[$name] = $value;
                }
            }

            if (!isset($headers['Authorization'])) {
                if (isset($_SERVER['PHP_AUTH_USER'])) {
                    $passwd = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                    $headers['Authorization'] = 'Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$passwd);
                }
                elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                    $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
                }
            }
            $this->headers = $headers;
        }

        // return all or just the specified headers
        if (!$names)
            return $this->headers;
        return array_intersect_ukey($this->headers, array_flip($names), 'strcasecmp');
    }


    /**
     * Return a single value of the specified header/s. If multiple headers are specified or multiple headers have been
     * transmitted, return all values as one comma-separated value (in transmission order).
     *
     * @param  string|string[] $names - one or multiple header names
     *
     * @return ?string - value or NULL if no such headers have been transmitted
     */
    private function getHeaderValue($names) {
        if (is_string($names)) {
            $names = [$names];
        }
        elseif (!is_array($names)) throw new InvalidTypeException('Invalid type of parameter $names: '.gettype($names));

        $headers = $this->getHeaders($names);
        if ($headers)
            return join(',', $headers);
        return null;
    }


    /**
     * Return a representation of the files uploaded with the request. The PHP array structure of $_FILES is converted
     * to normalized arrays.
     *
     * @return array<string, string[]> - associative array of files
     */
    private function getFiles() {
        if (!isset($this->files)) {
            $normalizeLevel = null;
            $normalizeLevel = function(array $file) use (&$normalizeLevel) {
                if (isset($file['name']) && is_array($file['name'])) {
                    $properties = array_keys($file);
                    $normalized = [];
                    foreach ($file['name'] as $name => $v) {
                        foreach ($properties as $property) {
                            $normalized[$name][$property] = $file[$property][$name];
                        }
                        $normalized[$name] = $normalizeLevel($normalized[$name]);
                    }
                    $file = $normalized;
                }
                return $file;
            };

            $this->files = [];
            foreach ($_FILES as $key => $file) {
                $this->files[$key] = $normalizeLevel($file);
            }
        }
        return $this->files;
    }


    /**
     * Return the content of the request (the body). For file uploads the method doesn't return the real binary content.
     * Instead it returns available metadata.
     *
     * @return string - request body or metadata
     */
    private function getContent() {
        if (!isset($this->content)) {
            $content = '';
            if ($this->getContentType() == 'multipart/form-data') {
                // file upload
                if ($_POST) {                                           // php://input is not available with enctype="multipart/form-data"
                    $content .= '$_POST => '.print_r($_POST, true).NL;
                }
                $content .= '$_FILES => '.print_r($this->getFiles(), true);
            }
            else {
                // regular request body
                $content .= file_get_contents('php://input');
            }
            $this->content = $content;
        }
        return $this->content;
    }


    /**
     * Return the "Content-Type" header of the request. If multiple "Content-Type" headers have been transmitted the first
     * one is returned.
     *
     * @return ?string - "Content-Type" header or NULL if no "Content-Type" header was transmitted
     */
    private function getContentType() {
        $contentType = $this->getHeaderValue('Content-Type');

        if ($contentType) {
            $headers     = explode(',', $contentType, 2);
            $contentType = array_shift($headers);

            $values      = explode(';', $contentType, 2);
            $contentType = trim(array_shift($values));
        }
        return $contentType;
    }


    /**
     * Return a readable string representation of the instance.
     *
     * @return string
     */
    public function __toString() {
        // request
        $string = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL'].NL;

        // headers
        $headers = $this->getHeaders();
        $maxLen = 0;
        foreach ($headers as $key => $value) {
            $maxLen = max(strlen($key), $maxLen);
        }
        $maxLen++;                                                  // add one char for ':'
        foreach ($headers as $key => $value) {
            $string .= str_pad($key.':', $maxLen).' '.$value.NL;
        }

        // content (request body)
        $content = $this->getContent();
        if (strlen($content)) {
            $string .= NL.substr($content, 0, 2048).NL;             // limit the request body to 2048 bytes
        }
        return $string;
    }


    /**
     * Return the data of the instance (there can be only one).
     *
     * @return string
     */
    public static function current() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return (string) self::$instance;
    }
}
