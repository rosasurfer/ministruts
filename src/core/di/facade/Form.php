<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\facade;

use rosasurfer\ministruts\core\di\proxy\Request;
use rosasurfer\ministruts\struts\ActionForm;
use rosasurfer\ministruts\struts\EmptyActionForm;
use rosasurfer\ministruts\struts\Struts;

/**
 * Form
 *
 * A {@link Facade} for accessing the properties of the {@link ActionForm} assigned to the current HTTP request,
 * and for accessing the properties of the {@link ActionForm} assigned to the previous HTTP request if the current
 * request is a result of an HTTP redirect.
 *
 * The Form facade is used to access either one or the other form's properties. The {@link Forms} facade is used to access properties
 * of both forms together in a single API call.
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
    public static function get(string $name, $default = null) {
        $form = static::current();
        return $form->get($name, $default);
    }


    /**
     * Return the {@link ActionForm} instance assigned to the current HTTP request. Use the optional
     * parameter $type to distinguish between multiple {@link ActionForm}s in the same page.
     *
     * @param  ?string $type [optional] - ensure the returned instance matches the specified class name, or return an
     *                                    EmptyActionForm if this is not the case (default: no type restriction)
     * @return ActionForm
     */
    public static function current(?string $type = null): ActionForm {
        $form = Request::getAttribute(Struts::ACTION_FORM_KEY);

        if ($form && (!isset($type) || $form instanceof $type)) {
            return $form;
        }
        return new EmptyActionForm(Request::instance());
    }


    /**
     * If the current request is a result of an HTTP redirect return the {@link ActionForm} instance assigned
     * to the previous HTTP request. Otherwise return an {@link EmptyActionForm}. Use the optional parameter $type
     * to distinguish between multiple different {@link ActionForm}s in the same page.
     *
     * @param  ?string $type [optional] - ensure the returned instance matches the specified class name, or return an
     *                                    EmptyActionForm if this is not the case (default: no type restriction)
     * @return ActionForm
     */
    public static function old(?string $type = null): ActionForm {
        $form = Request::getAttribute(Struts::ACTION_FORM_KEY.'.old');

        if ($form && (!isset($type) || $form instanceof $type)) {
            return $form;
        }
        return new EmptyActionForm(Request::instance());
    }
}
