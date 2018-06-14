<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\net\http\HttpResponse;

use function rosasurfer\strLeftTo;


/**
 * ActionForward
 *
 * An ActionForward describes a target a request is forwarded to after processing. It has a logical name (for identification)
 * and points either to a physical resource (file, layout template) or to a URI.
 */
class ActionForward extends Object {


    /** @var string - default identifier for looking up a forward after a successful form validation */
    const VALIDATION_SUCCESS_KEY = 'success';

    /** @var string - default identifier for looking up a forward after a failed form validation */
    const VALIDATION_ERROR_KEY   = 'error';

    /** @var string - reserved identifier for looking up a forward to the currently used ActionMapping */
    const SELF = 'self';

    /** @var string */
    protected $name;

    /** @var string */
    protected $path;

    /** @var string */
    protected $label;

    /** @var bool */
    protected $redirect;

    /** @var int */
    protected $redirectType;


    /**
     * Create a new instance with the specified properties.
     *
     * @param  string $name                    - logical forward name
     * @param  string $path                    - resource path
     * @param  bool   $redirect     [optional] - redirect flag (default: FALSE)
     * @param  int    $redirectType [optional] - redirect type (default: "Moved Temporarily" = 302)
     */
    public function __construct($name, $path, $redirect=false, $redirectType=HttpResponse::SC_MOVED_TEMPORARILY) {
        $this->setName    ($name)
             ->setPath    ($path)
             ->setRedirect($redirect);

        if ($redirect)
            $this->setRedirectType($redirectType);
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
     * Return the forward's resource path.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }


    /**
     * Return the forward's label.
     *
     * @return string
     */
    public function getLabel() {
        return $this->label;
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
     * Return the forward's redirect type (if any).
     *
     * @return int - HTTP status code
     */
    public function getRedirectType() {
        return (int) $this->redirectType;
    }


    /**
     * Set the forward's name.
     *
     * @param  string $name
     *
     * @return $this
     */
    public function setName($name) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!strLen($name))    throw new InvalidArgumentException('Invalid argument $name: '.$name);

        $this->name = $name;
        return $this;
    }


    /**
     * Set the forward's resource path.
     *
     * @param  string $path
     *
     * @return $this
     */
    public function setPath($path) {
        if (!is_string($path)) throw new IllegalTypeException('Illegal type of parameter $path: '.getType($path));
        if (!strLen($path))    throw new InvalidArgumentException('Invalid argument $path: '.$path);

        $this->path = $path;
        return $this;
    }


    /**
     * Set the forward's label (used only in HTML comments).
     *
     * @param  string $label
     *
     * @return $this
     */
    public function setLabel($label) {
        if (!is_string($label)) throw new IllegalTypeException('Illegal type of parameter $label: '.getType($label));
        if (!strLen($label))    throw new InvalidArgumentException('Invalid argument $label: '.$label);

        $this->label = $label;
        return $this;
    }


    /**
     * Set the forward's redirect status.
     *
     * @param  bool $redirect
     *
     * @return $this
     */
    public function setRedirect($redirect) {
        if (!is_bool($redirect)) throw new IllegalTypeException('Illegal type of parameter $redirect: '.getType($redirect));

        $this->redirect = $redirect;
        return $this;
    }


    /**
     * Set the forward's redirect type.
     *
     * @param  int type - HTTP status type
     *
     * @return $this
     */
    public function setRedirectType($type) {
        if (!is_int($type)) throw new IllegalTypeException('Illegal type of parameter $type: '.getType($type));

        $this->redirectType = $type;
        return $this;
    }


    /**
     * Add a query parameter to the forward's URL.
     *
     * @param  string $key   - parameter name
     * @param  scalar $value - parameter value
     *
     * @return $this
     */
    public function addQueryData($key, $value) {

        // TODO: freeze the instance after configuration and automatically call copy()

        if (!is_string($key))       throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
        if (is_null($value))        $value = '';
        elseif (is_bool($value))    $value = (int) $value;
        elseif (!is_scalar($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

        $value = (string) $value;

        // TODO: extend to process multiple parameters at once
        $path      = $this->getPath();
        $separator = (strPos($path, '?')!==false) ? '&' : '?';
        $this->setPath($path.$separator.$key.'='.str_replace([' ','#','&'], ['%20','%23','%26'], $value));

        return $this;
    }


    /**
     * Set the hash fragment of the forward's URL.
     *
     * @param  string $value - hash value
     *
     * @return $this
     */
    public function setHash($value) {

        // TODO: freeze the instance after configuration and automatically call copy()

        if (isSet($value)) {
            if     (is_bool($value))    $value = (int) $value;
            elseif (!is_scalar($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
        }
        $value = (string) $value;
        $path = $this->getPath();
        $this->setPath(strLeftTo($path, '#', $count=1, $includeLimiter=false, $onNotFound=$path).'#'.$value);

        return $this;
    }


    /**
     * Return an identical copy of forward.
     *
     * @return self
     */
    public function copy() {
        return clone $this;
    }
}
