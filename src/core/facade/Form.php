<?php
namespace rosasurfer\core\facade;

use rosasurfer\core\exception\IllegalAccessException;
use rosasurfer\core\proxy\Request;
use rosasurfer\ministruts\ActionForm;
use rosasurfer\ministruts\ActionMapping;
use rosasurfer\ministruts\DispatchAction;

use const rosasurfer\ministruts\ACTION_FORM_KEY;


/**
 * Form
 *
 * A {@link Facade} for the current {@link ActionForm} configured for an {@link ActionMapping}, and the {@link ActionForm}
 * used in the previous HTTP request if the current request is a result of an HTTP redirect. If no form is configured for a
 * mapping the framework instantiates and assigns a {@link Defau}default form implementation.
 *
 *
 * @method static string|null getActionKey() Return the dispatch action key (if the action is a {@link DispatchAction} and a key was submitted).
 */
class Form extends Facade {


    /**
     * Resolve the target {@link ActionForm} instance for a static method call.
     *
     * @param  string $method - method name
     *
     * @return ActionForm|null
     */
    protected static function target($method) {
        $key = 'form';
        return Request::getAttribute(ACTION_FORM_KEY);
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $method - method name
     * @param  array  $args   - arguments passed to the method call
     *
     * @return mixed
     */
    public static function __callStatic($method, array $args) {
        if (substr($method, 0, 2) == '__')
            throw new IllegalAccessException('Cannot access internal method '.ActionForm::class.'::'.$method.'()');

        switch (strtolower($method)) {
            case 'initactionkey':
            case 'populate'     :
            case 'validate'     :
            case 'offsetexists' :
            case 'offsetget'    :
            case 'offsetset'    :
            case 'offsetunset'  :
                throw new IllegalAccessException('Cannot access internal method '.ActionForm::class.'::'.$method.'()');
        }

        if ($target = static::target($method))
            return $target->$method(...$args);
        return null;
    }
}
