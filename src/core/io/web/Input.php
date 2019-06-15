<?php
namespace rosasurfer\core\io\web;

use rosasurfer\core\CObject;
use rosasurfer\ministruts\Request;


/**
 * Input
 *
 * An object providing access to raw HTTP request parameters.
 */
class Input extends CObject {


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
     *
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
