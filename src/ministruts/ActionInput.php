<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\CObject;


/**
 * ActionInput
 *
 * An object providing access to the current HTTP request's raw user input parameters. Use the {@link ActionForm} to access
 * the request's validated and interpreted input parameters.
 */
class ActionInput extends CObject {


    /** @var Request [transient] - the request the instance belongs to */
    protected $request;


    /**
     * Constructor
     *
     * @param  Request $request
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }


    /**
     * @param  string $name
     * @param  mixed  $default [optional]
     *
     * @return mixed
     */
    public function get($name, $default = null) {
        return null;
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
