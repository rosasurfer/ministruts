<?php
namespace rosasurfer\core\facade;

use rosasurfer\core\di\proxy\Request as RequestProxy;
use rosasurfer\ministruts\ActionInput;
use rosasurfer\ministruts\Request;

use const rosasurfer\ministruts\ACTION_INPUT_KEY;


/**
 * Input
 *
 * A {@link Facade} for accessing the raw parameters of the current or the previous web {@link Request}.
 */
class Input extends Facade {


    /**
     * Return the current HTTP request's raw parameter with the specified name.
     *
     * @param  string $name               - parameter name
     * @param  mixed  $default [optional] - default value to return if the specified parameter was not transmitted
     *                                      (default: none)
     * @return mixed
     */
    public static function get($name, $default = null) {
        $input = static::current();
        return $input->get($name, $default);
    }


    /**
     * Return the {@link ActionInput} instance assigned to the current HTTP request. The instance represents the request's
     * raw input parameters.
     *
     * @return ActionInput
     */
    public static function current() {
        return null;
        //return RequestProxy::getInput();
    }


    /**
     * If the current request is a result of an HTTP redirect return the {@link ActionInput} instance assigned to the
     * previous HTTP request. The instance represents the previous request's raw input parameters. If the current request
     * is not a result of an HTTP redirect return an empty instance.
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
