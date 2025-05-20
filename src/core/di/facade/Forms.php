<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\facade;

/**
 * Forms
 *
 * A {@link Facade} for accessing the merged properties of the {@link \rosasurfer\ministruts\struts\ActionForm} assigned to the current
 * HTTP request, and the ActionForm assigned to the previous HTTP request if the current request is a result of an HTTP redirect.
 *
 * The {@link Form} facade is used to access either one or the other form's properties. The {@link Forms} facade is used to
 * access properties of both forms together in a single API call.
 */
class Forms extends Facade {

    /**
     * Return the {@link \rosasurfer\ministruts\struts\ActionForm} property with the specified name from any of the current or the
     * previous ActionForm. If the property exists in both forms the current value is returned.
     *
     * @param  string $name               - property name
     * @param  mixed  $default [optional] - value to return if the specified property was not found
     *                                      (default: none)
     * @return mixed
     */
    public static function get(string $name, $default = null) {
        return Form::current()->get($name) ?? Form::old()->get($name, $default);
    }
}
