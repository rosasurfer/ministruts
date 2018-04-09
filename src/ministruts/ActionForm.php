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

    /** @var string [transient] - dispatch action key */
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

        // check for a dispatch action key
        if     (isSet($_REQUEST['action'  ])) $this->actionKey = $_REQUEST['action'  ];
        elseif (isSet($_REQUEST['action.x'])) $this->actionKey = $_REQUEST['action.x'];  // submit type="image"

        // read submitted parameters
        $this->populate($request);
    }


    /**
     * Populate the form object with the request parameters.
     *
     * @param  Request $request
     *
     * @return void
     */
    abstract protected function populate(Request $request);


    /**
     * Validate the form parameters syntactically.
     *
     * @return bool - whether or not the submitted parameters are valid
     */
    public function validate() {
        return true;
    }


    /**
     * Return the dispatch action key (if any).
     *
     * @return string|null - action key or NULL if no action key was submitted
     *
     * @see    java.struts.DispatchAction
     */
    public function getActionKey() {
        return $this->actionKey;
    }


    /**
     * Prevent serialization of transient properties.                    // access level encoding
     *                                                                   // ---------------------
     * @return string[] - array of property names to serialize           // private:   "\0{className}\0{propertyName}"
     */                                                                  // protected: "\0*\0{propertyName}"
    public function __sleep() {                                          // public:    "{propertyName}"
        $array = (array) $this;
        unset($array["\0*\0request"  ]);
        unset($array["\0*\0actionKey"]);
        return array_keys($array);
    }


    /**
     * Re-initialize the instance after deserialization.
     */
    public function __wakeUp() {
        $this->__construct(Request::me());
    }
}
