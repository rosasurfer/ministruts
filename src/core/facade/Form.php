<?php
namespace rosasurfer\core\facade;

use rosasurfer\core\proxy\Request;
use rosasurfer\ministruts\ActionForm;
use rosasurfer\ministruts\EmptyActionForm;

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
     * @param  string $name               - property name
     * @param  mixed  $default [optional] - default value to return if the specified property was not found (default: none)
     *
     * @return mixed
     */
    public static function get($name, $default = null) {
        $form = static::current();
        return $form->get($name, $default);
    }


    /**
     * Return the {@link ActionForm} instance assigned to the current HTTP request. Use the optional $type parameter to
     * distinguish between multiple different {@link ActionForm}s in the same page.
     *
     * @param  string $type [optional] - ensure the returned instance matches the specified class name, or return an
     *                                   EmptyActionForm if this is not the case
     *                                   (default: no type restriction)
     * @return ActionForm
     */
    public static function current($type = null) {
        $form = Request::getAttribute(ACTION_FORM_KEY);

        if ($form && (!isset($type) || $form instanceof $type))
            return $form;
        return new EmptyActionForm(Request::instance());
    }


    /**
     * If the current request is a result of an HTTP redirect return the {@link ActionForm} instance assigned to the previous
     * HTTP request. Otherwise return an instance of {@link EmptyActionForm}. Use the optional $type parameter to distinguish
     * between multiple different {@link ActionForm}s in the same page.
     *
     * @param  string $type [optional] - ensure the returned instance matches the specified class name, or return an
     *                                   EmptyActionForm if this is not the case
     *                                   (default: no type restriction)
     * @return ActionForm
     */
    public static function old($type = null) {
        $form = new EmptyActionForm(Request::instance());           // @TODO: resolve the real instance

        if ($form && (!isset($type) || $form instanceof $type))
            return $form;
        return new EmptyActionForm(Request::instance());
    }
}
