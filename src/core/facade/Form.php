<?php
namespace rosasurfer\core\facade;

use rosasurfer\core\proxy\Request;
use rosasurfer\ministruts\ActionForm;
use rosasurfer\ministruts\DefaultActionForm;

use const rosasurfer\ministruts\ACTION_FORM_KEY;


/**
 * Form
 *
 * A {@link Facade} for the properties of the {@link ActionForm} assigned to the current HTTP request, and the properties
 * of the {@link ActionForm} assigned to the previous HTTP request if the current request is a result of an HTTP redirect.
 */
class Form extends Facade {


    /**
     * Return the current HTTP request's {@link ActionForm} property with the specified name.
     *
     * @param  string $name - property name
     *
     * @return mixed
     */
    public static function get($name) {
        $form = static::target();
        return $form->get($name);
    }


    /**
     * Return the {@link ActionForm} instance assigned to the current HTTP request.
     *
     * @return ActionForm
     */
    public static function current() {
        return static::target();
    }


    /**
     * If the current request is a result of an HTTP redirect return the {@link ActionForm} instance assigned to the previous
     * HTTP request. Otherwise return an instance of {@link DefaultActionForm}.
     *
     * @return ActionForm
     */
    public static function old() {
        static $oldForm;                // @TODO: return the real instance
        !$oldForm && $oldForm = new DefaultActionForm(Request::instance());
        return $oldForm;
    }


    /**
     * Resolve the {@link ActionForm} instance responsible for handling the specified method call.
     *
     * @param  string $method [optional] - method name (default: ignored)
     *
     * @return ActionForm - always the instance assigned to the current HTTP request.
     */
    protected static function target($method = null) {
        return Request::getAttribute(ACTION_FORM_KEY);
    }
}
