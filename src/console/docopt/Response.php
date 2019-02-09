<?php
namespace rosasurfer\console\docopt;

use rosasurfer\core\Object;


/**
 * Response
 */
class Response extends Object implements \ArrayAccess, \IteratorAggregate {


    /** @var int */
    public $status;

    /** @var string */
    public $output;

    /** @var array */
    public $args;


    /**
     * @param  array  $args
     * @param  int    $status [optional]
     * @param  string $output [optional]
     */
    public function __construct(array $args, $status=0, $output='') {
        $this->args = $args;
        $this->status = $status;
        $this->output = $output;
    }


    /**
     * @return bool
     */
    public function success() {
        return ($this->status === 0);
    }


    /**
     * @param  mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->args[$offset]);
    }


    /**
     * @param  mixed $offset
     *
     * @return string
     */
    public function offsetGet($offset) {
        return $this->args[$offset];
    }


    /**
     * @param  mixed $offset
     * @param  mixed $value
     */
    public function offsetSet($offset, $value) {
        $this->args[$offset] = $value;
    }


    /**
     * @param  mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->args[$offset]);
    }


    /**
     * @return \ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->args);
    }
}
