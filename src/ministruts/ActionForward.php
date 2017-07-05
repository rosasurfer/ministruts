<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;


/**
 * ActionForward
 *
 * An ActionForward describes a target a request is forwarded to after processing. It has a logical name (for identification)
 * and points either to a physical resource (file, layout template) or to a URL.
 */
class ActionForward extends Object {


    /** @var string - default identifier for looking up a forward after a successful form validation */
    const VALIDATION_SUCCESS_KEY = 'success';

    /** @var string - default identifier for looking up a forward after a failed form validation */
    const VALIDATION_ERROR_KEY   = 'error';

    /** @var string - reserved identifier for looking up a forward to the currently used ActionMapping */
    const __SELF = '__self';

    /** @var string */
    protected $name;

    /** @var string */
    protected $path;

    /** @var string */
    protected $label;

    /** @var bool */
    protected $redirect;


    /**
     * Create a new instance with the specified properties.
     *
     * @param  string $name                - logical forward name
     * @param  string $path                - resource path
     * @param  bool   $redirect [optional] - redirect flag (default: FALSE)
     */
    public function __construct($name, $path, $redirect = false) {
        $this->setName($name)
             ->setPath($path)
             ->setRedirect($redirect);
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
     * Add a query parameter to the forward's URL.
     *
     * @param  string $key   - parameter name
     * @param  scalar $value - parameter value
     *
     * @return $this
     */
    public function addQueryData($key, $value) {
        if (!is_string($key))       throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
        if (is_null($value))        $value = '';
        elseif (is_bool($value))    $value = (int) $value;
        elseif (!is_scalar($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

        $value = (string) $value;

        // TODO: extend to process multiple parameters at once

        $separator = (strPos($this->path, '?')!==false) ? '&' : '?';

        $this->path .= $separator.$key.'='.str_replace(array(' ', '#', '&'), array('%20', '%23', '%26'), $value);

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
