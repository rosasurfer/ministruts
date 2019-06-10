<?php
namespace rosasurfer\di\proxy;

use rosasurfer\core\exception\IllegalAccessException;
use rosasurfer\ministruts\ActionForm;
use rosasurfer\ministruts\ActionMapping;
use rosasurfer\ministruts\DispatchAction;

use const rosasurfer\ministruts\ACTION_FORM_KEY;


/**
 * Form
 *
 * A Proxy for the {@link ActionForm} configured for an {@link ActionMapping}. If no form is configured for a mapping the
 * proxy forwards calls to a default form implementation.
 *
 *
 * @method static string|null getActionKey() Return the dispatch action key (if the action is a {@link DispatchAction} and a key was submitted).
 */
class Form extends Proxy {


    /**
     * {inheritdoc}
     *
     * @return object
     */
    public static function instance() {
        $key = 'form';

        if (isset(static::$resolvedInstances[$key]))
            return static::$resolvedInstances[$key];

        return static::$resolvedInstances[$key] = Request::getAttribute(ACTION_FORM_KEY);
    }


    /**
     * Forward static method calls to the object instance behind the proxy.
     *
     * @param  string $method - method name
     * @param  array  $args   - arguments passed to the method call
     *
     * @return mixed
     */
    public static function __callStatic($method, array $args) {
        switch (strtolower($method)) {
            case 'initactionkey':
            case 'populate'     :
            case 'validate'     :
            case 'offsetexists' :
            case 'offsetget'    :
            case 'offsetset'    :
            case 'offsetunset'  :
                throw new IllegalAccessException('Cannot forward call to internal method '.get_class(static::instance()).'::'.$method.'()');
        }
        return parent::__callStatic($method, $args);
    }
}
