<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\CObject;
use rosasurfer\di\proxy\Request as RequestProxy;


/**
 * ActionForm
 *
 * An ActionForm encapsulates and represents interpreted user input. It provides an interface for {@link Action}s
 * and business layer to access and validate this input.
 */
abstract class ActionForm extends CObject {


    /** @var Request [transient] - the request the form belongs to */
    protected $request;

    /** @var string [transient] - dispatch action key; populated if the Action handling the request is a DispatchAction */
    protected $actionKey;

    /** @var string[] */
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
     * Create a new instance with data from the passed {@link Request}.
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
     * @return void
     *
     * The framework expects the action key nested in an array named "submit". Write your HTML as follows:
     *
     * @example
     * <pre>
     *  &lt;img type="submit" name="submit[action]" value="{action-key}" src=... /&gt;
     * </pre>
     */
    public function initActionKey(Request $request) {
        //
        // PHP breaks transmitted parameters by silently converting dots "." and spaces " " in parameter names to underscores.
        // This especially breaks submit image elements, as the HTML standard appends the clicked image coordinates to the submit
        // parameter.
        //
        // HTML example:
        //   <form action="/url">
        //      <input type="text" name="foo.bar" value="baz">
        //      <img type="submit" name="action" src="image.png">
        //   </form>
        //
        // Parameters sent by the browser:
        //   GET /url?foo.bar=baz&action.x=123&action.y=456 HTTP/1.0
        //
        // Parameters after PHP input processing:
        //   $_GET = array(
        //       [foo_bar]  => baz                              // broken parameter name
        //       [action_x] => 123                              // broken parameter name
        //       [action_y] => 456                              // broken parameter name
        //   )
        //
        // - Workaround for image submit elements (<img type="submit"...):
        //   Specify the element's "name" attribute as follows: <img type="submit" name="submit[action]" ...>
        //   The browser will send "?submit[action].x=123&submit[action].y=456". PHP will treat the parameter as an array
        //   and discard the image coordinates, and the submit parameter name will stay unmodified.
        //
        // - Workaround for other parameters with dots or spaces:
        //   Wrap the name in an array:
        //   $_REQUEST = array(
        //       [action_x]            => value                 // <img type="submit" name="action"...  => broken by PHP
        //       [application_name]    => value                 // regular custom parameter             => broken by PHP
        //       [top_level_with_dots] => Array (               // custom top-level parameter           => broken by PHP
        //           [nested.level.with.dots] => value          // custom parameters wrapped in array   => not broken by PHP
        //       )
        //   )
        //
        if (isset($_REQUEST['submit']['action'])) {
            $this->actionKey = $_REQUEST['submit']['action'];
        }
    }


    /**
     * Return the dispatch action key (if the action is a {@link DispatchAction} and a key was submitted).
     *
     * @return ?string - action key or NULL if no action key was submitted
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
     * Validate passed form parameters syntactically.
     *
     * @return bool - whether the submitted parameters are valid
     */
    abstract public function validate();


    /**
     * Prevent serialization of transient properties.
     *
     * @return string[] - array of property names to serialize
     */
    public function __sleep() {                                         // access level encoding
        $array = (array) $this;                                         // ---------------------
                                                                        // private:   "\0{className}\0{propertyName}"
        unset($array["\0*\0request"  ]);                                // protected: "\0*\0{propertyName}"
        unset($array["\0*\0actionKey"]);                                // public:    "{propertyName}"

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
