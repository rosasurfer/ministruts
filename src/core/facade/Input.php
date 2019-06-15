<?php
namespace rosasurfer\core\facade;

use rosasurfer\core\di\proxy\Request as RequestProxy;
use rosasurfer\core\io\WebInput;
use rosasurfer\ministruts\Request;


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
     * Return the wrapper around the current HTTP request parameters.
     *
     * @return WebInput
     */
    public static function current() {
        return RequestProxy::getAttribute('input');
    }


    /**
     * If the current request is a result of an HTTP redirect return the input wrapper around the previous HTTP request
     * parameters. Otherwise return an empty input wrapper.
     *
     * @return WebInput
     */
    public static function old() {
        $input = RequestProxy::getAttribute('input.old');
        if ($input) return $input;

        $class = new \ReflectionClass(WebInput::class);
        return $class->newInstanceWithoutConstructor();
    }
}
