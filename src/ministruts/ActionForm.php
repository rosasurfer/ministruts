<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\CObject;
use rosasurfer\di\proxy\Request as RequestProxy;


/**
 * An ActionForm encapsulates and represents the user input. It provides an interface for {@link Action}s and business layer
 * to access and validate this input.
 */
abstract class ActionForm extends CObject {


    /** @var Request [transient] - the request the form belongs to */
    protected $request;

    /** @var string [transient] - dispatch action key; populated if the Action handling the request is a DispatchAction */
    protected $actionKey;

    /** @var string[] [transient] */
    protected static $fileUploadErrors = [
        UPLOAD_ERR_OK         => 'Success (UPLOAD_ERR_OK)',
        UPLOAD_ERR_INI_SIZE   => 'Upload error, file too big (UPLOAD_ERR_INI_SIZE)',
        UPLOAD_ERR_FORM_SIZE  => 'Upload error, file too big (UPLOAD_ERR_FORM_SIZE)',
        UPLOAD_ERR_PARTIAL    => 'Partial file upload error, try again (UPLOAD_ERR_PARTIAL)',
        UPLOAD_ERR_NO_FILE    => 'Error while uploading the file (UPLOAD_ERR_NO_FILE)',
        UPLOAD_ERR_NO_TMP_DIR => 'Read/write error while uploading the file (UPLOAD_ERR_NO_TMP_DIR)',
        UPLOAD_ERR_CANT_WRITE => 'Read/write error while uploading the file (UPLOAD_ERR_CANT_WRITE)',
        UPLOAD_ERR_EXTENSION  => 'Error while uploading the file (UPLOAD_ERR_EXTENSION)',
    ];


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
     * Read a submitted {@link DispatchAction} key.
     *
     * @param  Request $request
     *
     * @example
     *
     * MiniStruts expects the action key nested in an array named "submit". Write your HTML like so:
     * <pre>
     *  &lt;img type="submit" name="submit[action]" value="{action-key}" src=... /&gt;
     * </pre>
     */
    public function initActionKey(Request $request) {
        //
        // PHP silently converts dots "." and spaces " " in top-level parameter names to underscores. This breaks a submit
        // tag with an action key if the tag is an image tag as the HTML standard appends the clicked image coordinates.
        //
        // - Workaround for browser-modified names, i.e. <img type="submit"... => select the name attribute as below:
        //   <img type="submit" name="submit[action]" ...>
        //   The browser will send "submit[action].x=123&submit[action].y=456" and PHP will discard the coordinates.
        //
        // - Workaround for all other parameter names with dots => wrap the name in a top-level array:
        //   $_POST = array(
        //       [action_x] => update                               // <img type="submit" name="action"... broken by PHP
        //       [application_name] => foobar                       // regular custom parameters broken by PHP
        //       [top_level_with_dots] => Array (                   // custom top-level parameters broken by PHP
        //           [nested.level.with.dots] => custom-value       // custom wrapped parameters not broken by PHP
        //       )
        //   )
        //
        if (isset($_REQUEST['submit']['action']))
            $this->actionKey = $_REQUEST['submit']['action'];
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
     * @return bool - whether the submitted parameters are valid
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
            if (is_object($property)) {
                unset($array[$name]);                                   // drop all remaining object references
            }
        }
        return \array_keys($array);
    }


    /**
     * Re-initialize the instance after deserialization.
     */
    public function __wakeUp() {
        $this->__construct(RequestProxy::instance());
    }
}
