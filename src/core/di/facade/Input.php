<?php
namespace rosasurfer\core\di\facade;

use rosasurfer\core\di\proxy\Request as RequestProxy;
use rosasurfer\ministruts\ActionInput;
use rosasurfer\ministruts\Request;

use const rosasurfer\ministruts\ACTION_INPUT_KEY;


/**
 * Input
 *
 * A {@link Facade} for accessing the raw input parameters of the current or the previous HTTP {@link Request}.
 */
class Input extends Facade {


    /**
     * Return all raw input parameters of the current HTTP request.
     *
     * @return string[]
     */
    public static function all() {
        $input = static::current();
        return $input->all();
    }


    /**
     * Return a single raw input parameter of the current HTTP request matching the specified name, or the passed default
     * value if no such input parameter was transmitted. If multiple parameters with that name have been transmitted the
     * last one is returned. If an array of parameters with that name has been transmitted it is ignored.
     * Use {@link Input::getArray()} to access an array of raw input parameters.
     *
     * @param  string $name               - parameter name
     * @param  string $default [optional] - value to return if the specified parameter was not transmitted
     *                                      (default: none)
     * @return string?
     */
    public static function get($name, $default = null) {
        $input = static::current();
        return $input->get($name, $default);
    }


    /**
     * Return an array of raw input parameter of the current HTTP request, matching the specified name, or the passed default
     * values if no such input parameter array was transmitted. If a single parameter with that name has been transmitted it
     * is ignored. Use {@link Input::get()} to access a single raw parameter.
     *
     * @param  string   $name               - parameter name
     * @param  string[] $default [optional] - values to return if the specified parameter array was not transmitted
     *                                        (default: empty array)
     * @return string[]
     */
    public static function getArray($name, array $default = []) {
        $input = static::current();
        return $input->getArray($name, $default);
    }


    /**
     * Whether a single raw input parameter with the specified name has been transmitted with the current HTTP request.
     * A transmitted array of parameters with that name is ignored. Use {@link Input::hasArray()} to test for an array
     * of parameters.
     *
     * @param  string $name - parameter name
     *
     * @return bool
     */
    public static function has($name) {
        $input = static::current();
        return $input->has($name);
    }


    /**
     * Whether an array of raw input parameter with the specified name has been transmitted with the current HTTP request.
     * A transmitted single parameter with that name is ignored. Use {@link Input::has()} to test for a single parameter.
     *
     * @param  string $name - parameter name
     *
     * @return bool
     */
    public static function hasArray($name) {
        $input = static::current();
        return $input->hasArray($name);
    }


    /**
     * Return the {@link ActionInput} instance assigned to the current HTTP request.
     *
     * @return ActionInput
     */
    public static function current() {
        return RequestProxy::input();
    }


    /**
     * If the current request is a result of an HTTP redirect return the {@link ActionInput} instance assigned to the
     * previous one. If the current request is not a result of an HTTP redirect return an empty instance.
     *
     * @return ActionInput
     */
    public static function old() {
        $input = RequestProxy::getAttribute(ACTION_INPUT_KEY.'.old');
        if ($input) return $input;

        $class = new \ReflectionClass(ActionInput::class);
        return $class->newInstanceWithoutConstructor();
    }
}
