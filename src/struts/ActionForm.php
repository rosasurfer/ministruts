<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\IllegalAccessException;


/**
 * ActionForm
 *
 * An ActionForm encapsulates and represents interpreted user input. It provides an interface for {@link Action}s
 * and business layer to access and validate this input. Use {@link ActionInput} to access the raw input parameters.
 *
 * @implements \ArrayAccess<string, mixed>
 */
abstract class ActionForm extends CObject implements \ArrayAccess {


    /** @var Request [transient] - the request the form belongs to */
    protected $request;

    /** @var string - dispatch action key, populated if the Action handling the request is a DispatchAction */
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

        /** @var ActionMapping $mapping */
        $mapping = $request->getAttribute(Struts::ACTION_MAPPING_KEY);
        $actionClass = $mapping->getActionClass();

        // if a DispatchAction is used read the action key
        if ($actionClass && is_subclass_of($actionClass, DispatchAction::class)) {
            $this->initActionKey();
        }
        $this->populate();
    }


    /**
     * Read a submitted {@link DispatchAction} key.
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
    protected function initActionKey() {
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
        $params = $this->request->input()->all();
        if (isset($params['submit']['action'])) {
            $key = $params['submit']['action'];
            if (is_string($key)) {
                $this->actionKey = $key;
            }
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
     * @return void
     */
    abstract protected function populate();


    /**
     * Validate passed form parameters syntactically.
     *
     * @return bool - whether the submitted parameters are valid
     */
    abstract public function validate();


    /**
     * Return the form property with the specified name. If a getter for the property exists the getter is called.
     * Otherwise the property is returned.
     *
     * @param  string $name               - property name
     * @param  mixed  $default [optional] - default value to return if the specified property was not found (default: NULL)
     *
     * @return mixed
     */
    public function get(string $name, $default = null) {
        switch ($name) {
            case 'request':
            case 'fileUploadErrors':
                return $default;
        }
        if (method_exists($this, $method='get'.$name)) {
            return $this->$method();
        }
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return $default;
    }


    /**
     * Whether a form property with the specified name exists.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function offsetExists($name): bool {
        switch ($name) {
            case 'request':
            case 'fileUploadErrors':
                return false;
        }
        return property_exists($this, $name) || method_exists($this, 'get'.$name);
    }


    /**
     * Return the property with the specified name. If a getter for the property exists the getter is called.
     * Otherwise the property is returned.
     *
     * @param  string $name
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($name) {
        return $this->get($name);
    }


    /**
     * Prevent modification of form properties.
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * @return void
     *
     * @throws IllegalAccessException
     */
    final public function offsetSet($name, $value): void {
        throw new IllegalAccessException('Cannot set/modify ActionForm properties');
    }


    /**
     * Unsetting form properties is not allowed.
     *
     * @param  string $name
     *
     * @return void
     *
     * @throws IllegalAccessException
     */
    final public function offsetUnset($name): void {
        throw new IllegalAccessException('Cannot set/modify ActionForm properties');
    }


    /**
     * Prevent serialization of transient properties.
     *
     * @return string[] - array of property names to serialize
     */
    public function __sleep() {
        $array = (array)$this;
        foreach ($array as $name => $property) {
            if (is_object($property) || is_resource($property)) {
                unset($array[$name]);                               // drop object and resource references
            }
        }
        return \array_keys($array);
    }
}
