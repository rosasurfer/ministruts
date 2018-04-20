<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;


/**
 * An ActionForm encapsulates and represents the user input. Provides an interface for Actions and business layer to access
 * and validate this input.
 */
abstract class ActionForm extends Object {


    /** @var Request [transient] - the request the form belongs to */
    protected $request;

    /** @var string [transient] - dispatch action key; populated if the Action handling the request is a DispatchAction */
    protected $actionKey;


    /**
     * Constructor
     *
     * Create a new form instance for the current {@link Request}.
     *
     * @param  Request $request
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }


    /**
     * Read and store a submitted {@link DispatchAction} key.
     *
     * @param  Request $request
     *
     * @example
     *
     * The framework expects the action key nested in an array. Include it in your HTML like this:
     *
     * <pre>
     * &lt;img type="submit" name="submit[action]" value="..." src=... /&gt;
     * </pre>
     */
    public function initActionKey(Request $request) {
        /** @var ActionMapping $mapping */
        $mapping = $request->getAttribute(ACTION_MAPPING_KEY);
        $actionClassName = $mapping->getActionClassName();

        if (is_subclass_of($actionClassName, DispatchAction::class)) {
            if (isSet($_REQUEST['submit']['action']))
                $this->actionKey = $_REQUEST['submit']['action'];

            // PHP silently converts dots "." and spaces " " in top-level parameter names to underscores.
            //
            // - Workaround for user-defined keys => wrap the key in a top-level array
            //   $_POST = Array (
            //       [action_x] => update
            //       [application_name] => foobar
            //       [top_level_with_dots] => Array (
            //           [nested.level.with.dots] => custom-value
            //       )
            //   )
            //
            // - Workaround for browser-modified keys, i.e. <img type="submit"... => select the key value wisely:
            //   <img type="submit" name="submit[action]" ...>
            //   The browser will send "submit[action].x=123&submit[action].y=456" and PHP will discard the coordinates.
        }
    }


    /**
     * Return the dispatch action key (if the action is a {@link DispatchAction} and a key was submitted).
     *
     * @return string|null - action key or NULL if no action key was submitted
     *
     * @see    java.struts.DispatchAction
     */
    public function getActionKey() {
        return $this->actionKey;
    }


    /**
     * Populate the form object with the request parameters.
     *
     * @param  Request $request
     *
     * @return void
     */
    abstract public function populate(Request $request);


    /**
     * Validate the form parameters syntactically.
     *
     * @return bool - whether or not the submitted parameters are valid
     */
    abstract public function validate();


    /**
     * Prevent serialization of transient properties.                   // access level encoding
     *                                                                  // ---------------------
     * @return string[] - array of property names to serialize          // private:   "\0{className}\0{propertyName}"
     */                                                                 // protected: "\0*\0{propertyName}"
    public function __sleep() {                                         // public:    "{propertyName}"
        $array = (array) $this;

        unset($array["\0*\0request"  ]);
        unset($array["\0*\0actionKey"]);

        foreach ($array as $name => $property) {
            if (is_object($property))
                unset($array[$name]);                                   // drop all remaining object references
        }
        return array_keys($array);
    }


    /**
     * Re-initialize the instance after deserialization.
     */
    public function __wakeUp() {
        $this->__construct(Request::me());
    }
}
