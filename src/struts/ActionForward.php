<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\net\http\HttpResponse;

use function rosasurfer\ministruts\strLeftTo;


/**
 * ActionForward
 *
 * An ActionForward describes a target a request is forwarded to after processing. It has a logical name (for identification)
 * and points either to a physical resource (a file, a layout or a template) or to an URI.
 */
class ActionForward extends CObject {


    /** @var string - reserved identifier which references a forward to the currently used ActionMapping */
    const SELF = 'self';

    /** @var string - default identifier for looking up a forward after a successful form validation */
    const VALIDATION_SUCCESS_KEY = 'success';

    /** @var string - default identifier for looking up a forward after a failed form validation */
    const VALIDATION_ERROR_KEY = 'error';


    /** @var string */
    protected $name;

    /** @var string - a URI, a tile name (if starting with ".") or a filename (if not starting with ".") */
    protected $path;

    /** @var bool - whether $path is a URI and a redirect will be issued */
    protected $redirect;

    /** @var int - type (HTTP status code) of the redirect to issue (if any) */
    protected $redirectType = HttpResponse::SC_MOVED_TEMPORARILY;


    /**
     * Constructor
     *
     * @param  string $name                    - logical forward name
     * @param  string $resource                - resource path
     * @param  bool   $redirect     [optional] - whether $resource is a redirect (default: no)
     * @param  int    $redirectType [optional] - redirect type (default: 302=SC_MOVED_TEMPORARILY)
     */
    public function __construct($name, $resource, $redirect=false, $redirectType=HttpResponse::SC_MOVED_TEMPORARILY) {
        $this->setName($name)
             ->setPath($resource)
             ->setRedirect($redirect);

        if ($redirect) {
            $this->setRedirectType($redirectType);
        }
    }


    /**
     * Return the foward's logical name (the identifier).
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * Set the forward's logical name.
     *
     * @param  string $name
     *
     * @return $this
     */
    public function setName($name) {
        Assert::string($name);
        if (!strlen($name)) throw new InvalidValueException('Invalid parameter $name: '.$name);

        $this->name = $name;
        return $this;
    }


    /**
     * Return the forward's resource path.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }


    /**
     * Set the forward's resource path.
     *
     * @param  string $path
     *
     * @return $this
     */
    public function setPath($path) {
        Assert::string($path);
        if (!strlen($path)) throw new InvalidValueException('Invalid parameter $path: '.$path);

        $this->path = $path;
        return $this;
    }


    /**
     * Return the forward's redirect flag.
     *
     * @return bool
     */
    public function isRedirect() {
        return $this->redirect;
    }


    /**
     * Set the forward's redirect flag.
     *
     * @param  bool $status
     *
     * @return $this
     */
    public function setRedirect($status) {
        Assert::bool($status);
        $this->redirect = $status;
        return $this;
    }


    /**
     * Return the forward's redirect type (if any).
     *
     * @return int - HTTP status code
     */
    public function getRedirectType() {
        return (int) $this->redirectType;
    }


    /**
     * Set the forward's redirect type.
     *
     * @param  int $type - HTTP status type
     *
     * @return $this
     */
    public function setRedirectType($type) {
        Assert::int($type);
        $this->redirectType = $type;
        return $this;
    }


    /**
     * Add a query parameter to the forward's URI.
     *
     * @param  string $key   - parameter name
     * @param  scalar $value - parameter value
     *
     * @return $this
     */
    public function addQueryData($key, $value) {
        // TODO: freeze the instance after configuration and automatically call copy()
        Assert::string      ($key,   '$key');
        Assert::nullOrScalar($value, '$value');

        if (is_bool($value)) $value = (int) $value;
        $value = (string) $value;

        // TODO: extend to process multiple parameters at once
        $path = $this->getPath();
        $separator = (strpos($path, '?')!==false) ? '&' : '?';
        $this->setPath($path.$separator.$key.'='.str_replace([' ','#','&'], ['%20','%23','%26'], $value));

        return $this;
    }


    /**
     * Set the hash fragment of the forward's URI.
     *
     * @param  scalar $value - hash value
     *
     * @return $this
     */
    public function setHash($value) {
        // TODO: freeze the instance after configuration and automatically call copy()
        Assert::scalar($value);

        if (is_bool($value)) $value = (int) $value;
        $value = (string) $value;

        $path = $this->getPath();
        $this->setPath(strLeftTo($path, '#', 1, false, $path).'#'.$value);

        return $this;
    }


    /**
     * Return an identical copy of the instance.
     *
     * @return ActionForward
     */
    public function copy() {
        return clone $this;
    }
}
