<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\facade;


/**
 * Inputs
 *
 * A {@link Facade} for accessing the merged input parameters of the {@link \rosasurfer\ministruts\struts\ActionInput} assigned to the
 * current HTTP request, and the ActionInput assigned to the previous HTTP request if the current
 * request is a result of an HTTP redirect.
 *
 * The {@link Input} facade is used to access either one or the other input parameters. The {@link Inputs} facade is used
 * to access both input parameters together in a single API call.
 */
class Inputs extends Facade {


    /**
     * Return all raw input parameters from the current and the previous HTTP request.
     *
     * @return array<string, string|array<string|array<string>>>
     */
    public static function all(): array {
        return Input::current()->all() + Input::old()->all();
    }


    /**
     * Return a single raw input parameter with the specified name from any of the current or the previous HTTP request,
     * or the passed default value if no such input parameter was transmitted. If multiple parameters with that name have
     * been transmitted the last one is returned. If the parameter exists in both requests the current value is returned.
     * If an array of parameters with that name has been transmitted it is ignored. Use {@link Inputs::getArray()} to access
     * an array of raw input parameters.
     *
     * @param  string  $name               - parameter name
     * @param  ?string $default [optional] - value to return if the specified parameter was not transmitted (default: none)
     *
     * @return ?string
     */
    public static function get($name, $default = null) {
        $value = Input::current()->get($name);
        if (!isset($value))
            $value = Input::old()->get($name, $default);
        return $value;
    }


    /**
     * Return an array of raw input parameter with the specified name from any of the current or the previous HTTP request,
     * or the passed default values if no such input parameter array was transmitted. If the array parameter exists in both
     * requests the current value is returned. If a single parameter with that name has been transmitted it is ignored.
     * Use {@link Inputs::get()} to access a single raw parameter.
     *
     * @param  string   $name               - parameter name
     * @param  string[] $default [optional] - values to return if the specified parameter array was not transmitted
     *                                        (default: empty array)
     * @return array<string|string[]>
     */
    public static function getArray($name, array $default = []) {
        $values = Input::current()->getArray($name);
        if (!$values)
            $values = Input::old()->getArray($name, $default);
        return $values;
    }


    /**
     * Whether a single raw input parameter with the specified name has been transmitted in any of the current or the
     * previous HTTP request. A transmitted array of parameters with that name is ignored. Use {@link Inputs::hasArray()}
     * to test for an array of parameters.
     *
     * @param  string $name - parameter name
     *
     * @return bool
     */
    public static function has($name) {
        $result = Input::current()->has($name);
        if (!$result)
            $result = Input::old()->has($name);
        return $result;
    }


    /**
     * Whether an array of raw input parameter with the specified name has been transmitted in any of the current or the
     * previous HTTP request. A transmitted single parameter with that name is ignored. Use {@link Inputs::has()} to test
     * for a single parameter.
     *
     * @param  string $name - parameter name
     *
     * @return bool
     */
    public static function hasArray($name) {
        $result = Input::current()->hasArray($name);
        if (!$result)
            $result = Input::old()->hasArray($name);
        return $result;
    }
}
