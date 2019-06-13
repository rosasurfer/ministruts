<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\IllegalAccessException;
use rosasurfer\core\proxy\Request as RequestProxy;


/**
 * An {@link ActionForm} encapsulates and represents the user input. It provides an interface for {@link Action}s and
 * business layer to access and validate this input.
 */
abstract class ActionForm extends CObject implements \ArrayAccess {


    /** @var Request [transient] - the request the form belongs to */
    protected $request;

    /** @var string [transient] - dispatch action key, populated if the Action handling the request is a DispatchAction */
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
        /**
         * PHP breaks transmitted parameters by silently converting dots "." and spaces " " in names to underscores. This
         * especially breaks submit image elements, as the HTML standard appends the clicked image coordinates to the submit
         * parameter.
         *
         * HTML example:
         *   <form action="/url">
         *      <input type="text" name="foo.bar" value="baz">
         *      <img type="submit" name="action" src="image.png">
         *   </form>
         *
         * Parameters sent by the browser:
         *   GET /url?foo.bar=baz&action.x=123&action.y=456 HTTP/1.0
         *
         * Parameters after PHP input processing:
         *   $_GET = array(
         *       [foo_bar]  => baz                              // broken name
         *       [action_x] => 123                              // broken name
         *       [action_y] => 456                              // broken name
         *   )
         *
         * - Workaround for image submit elements (<img type="submit"...):
         *   Note the element's name attribute as follows: <img type="submit" name="submit[action]" ...>
         *   The browser will send "?submit[action].x=123&submit[action].y=456". PHP will treat the parameter as an array
         *   and discard the image coordinates, and the submit parameter name will stay unmodified.
         *
         * - Workaround for other parameters with dots or spaces:
         *   Wrap the name in an array:
         *   $_REQUEST = array(
         *       [action_x]            => value                 // <img type="submit" name="action"...  => broken by PHP
         *       [application_name]    => value                 // regular custom parameter             => broken by PHP
         *       [top_level_with_dots] => Array (               // custom top-level parameter           => broken by PHP
         *           [nested.level.with.dots] => value          // custom parameters wrapped in array   => not broken by PHP
         *       )
         *   )
         */
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
     * Return the property with the specified name. If a getter for the property exists the getter is called. Otherwise
     * the property is returned.
     *
     * @param  string $name - property name
     *
     * @return mixed
     */
    public function get($name) {
        Assert::string($name);

        switch ($name) {
            case 'request':
            case 'fileUploadErrors':
                return null;
        }
        if (method_exists($this, $method='get'.$name)) {
            return $this->$method();
        }
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }


    /**
     * Whether a form property with the specified name exists.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function offsetExists($name) {
        Assert::string($name);

        switch ($name) {
            case 'request':
            case 'fileUploadErrors':
                return false;
        }
        return property_exists($this, $name) || method_exists($this, 'get'.$name);
    }


    /**
     * Return the property with the specified name. If a getter for the property exists the getter is called. Otherwise
     * the property is returned.
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function offsetGet($name) {
        return $this->get($name);
    }


    /**
     * Setting/modifying input parameters is not allowed.
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * $throws IllegalAccessException
     */
    final public function offsetSet($name, $value) {
        throw new IllegalAccessException('Cannot set/modify ActionForm properties');
    }


    /**
     * Unsetting input parameters is not allowed.
     *
     * @param  string $name
     *
     * $throws IllegalAccessException
     */
    final public function offsetUnset($name) {
        throw new IllegalAccessException('Cannot unset ActionForm properties');
    }


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
