<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\CObject;
use rosasurfer\core\exception\IllegalAccessException;


/**
 * ActionInput
 *
 * An object providing access to the current HTTP request's raw user input parameters. Use the {@link ActionForm} to access
 * the request's validated and interpreted input parameters.
 */
class ActionInput extends CObject implements \ArrayAccess {


    /** @var array */
    protected $parameters;


    /**
     * Constructor
     *
     * @param  string[] $parameters
     */
    public function __construct(array $parameters) {
        $this->parameters = $parameters;
    }


    /**
     * Return all raw input parameters.
     *
     * @return string[]
     */
    public function all() {
        return $this->parameters;
    }


    /**
     * Return a single raw input parameter with the specified name, or the passed default value if no such input parameter
     * was transmitted. If multiple parameters with that name have been transmitted the last one is returned. If an array
     * of parameters with that name has been transmitted it is ignored. Use {@link ActionInput::getArray()} to access an
     * array of raw input parameters.
     *
     * @param  string $name               - parameter name
     * @param  string $default [optional] - value to return if the specified parameter was not transmitted
     *                                      (default: none)
     * @return ?string
     */
    public function get($name, $default = null) {
        if (\key_exists($name, $this->parameters)) {
            if (!is_array($this->parameters[$name]))
                return $this->parameters[$name];
        }
        return $default;
    }


    /**
     * Return an array of raw input parameter with the specified name, or the passed default values if no such input
     * parameter array was transmitted. If a single parameter with that name has been transmitted it is ignored.
     * Use {@link ActionInput::get()} to access a single raw input parameter.
     *
     * @param  string   $name               - parameter name
     * @param  string[] $default [optional] - values to return if the specified parameter array was not transmitted
     *                                        (default: empty array)
     * @return string[]
     */
    public function getArray($name, array $default = []) {
        if (\key_exists($name, $this->parameters)) {
            if (is_array($this->parameters[$name]))
                return $this->parameters[$name];
        }
        return $default;
    }


    /**
     * Whether a single raw input parameter with the specified name has been transmitted. A transmitted array of parameters
     * with that name is ignored. Use {@link ActionInput::hasArray()} to test for an array of parameters.
     *
     * @param  string $name - parameter name
     *
     * @return bool
     */
    public function has($name) {
        if (\key_exists($name, $this->parameters))
            return !is_array($this->parameters[$name]);
        return false;
    }


    /**
     * Whether an array of raw input parameter with the specified name has been transmitted. A transmitted single parameter
     * with that name is ignored. Use {@link ActionInput::has()} to test for a single parameter.
     *
     * @param  string $name - parameter name
     *
     * @return bool
     */
    public function hasArray($name) {
        if (\key_exists($name, $this->parameters))
            return is_array($this->parameters[$name]);
        return false;
    }


    /**
     * Whether a single or an array parameter with the specified name exists.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function offsetExists($name) {
        return \key_exists($name, $this->parameters);
    }


    /**
     * Return the single or array input parameter with the specified name.
     *
     * @param  string $name
     *
     * @return ?string|array - parameter or NULL if no such input parameter exists
     */
    public function offsetGet($name) {
        if (\key_exists($name, $this->parameters))
            return $this->parameters[$name];
        return null;
    }


    /**
     * Setting/modifying input parameters is not allowed.
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * $throws IllegalAccessException
     */
    final public function offsetSet($name, $value) {
        throw new IllegalAccessException('Cannot set/modify ActionInput parameters');
    }


    /**
     * Unsetting input parameters is not allowed.
     *
     * @param  string $name
     *
     * $throws IllegalAccessException
     */
    final public function offsetUnset($name) {
        throw new IllegalAccessException('Cannot set/modify ActionInput parameters');
    }
}
