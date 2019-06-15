<?php
namespace rosasurfer\core\facade;

use rosasurfer\core\proxy\Request as RequestProxy;
use rosasurfer\ministruts\Input as InputInstance;
use rosasurfer\ministruts\Request;

use const rosasurfer\ministruts\ACTION_FORM_KEY;


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
     * @return \rosasurfer\ministruts\Input
     */
    public static function current() {
        return RequestProxy::getAttribute(ACTION_FORM_KEY);
    }


    /**
     * If the current request is a result of an HTTP redirect return the wrapper around the previous HTTP request parameters.
     * Otherwise return an empty input wrapper.
     *
     * @return \rosasurfer\ministruts\Input
     */
    public static function old() {
        return RequestProxy::getAttribute(ACTION_FORM_KEY.'.old') ?: new InputInstance();
    }
}
