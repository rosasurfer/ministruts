<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\CObject;


/**
 * ActionInput
 *
 * An object providing access to the current HTTP request's raw user input parameters. Use the {@link ActionForm} to access
 * the request's validated and interpreted input parameters.
 */
class ActionInput extends CObject {


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
     * Return a single raw input parameter with the specified name, or the passed default value if no such input parameter
     * was transmitted. If multiple parameters with that name have been transmitted the last one is returned. If an array
     * of parameters with that name has been transmitted it is ignored. Use {@link ActionInput::getArray()} to access an
     * array of raw input parameters.
     *
     * @param  string $name               - parameter name
     * @param  string $default [optional] - value to return if the specified parameter was not transmitted
     *                                      (default: none)
     * @return string|null
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
     * Return all raw input parameters.
     *
     * @return string[]
     */
    public function getAll() {
        return $this->parameters;
    }
}
