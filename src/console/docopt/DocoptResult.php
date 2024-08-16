<?php
namespace rosasurfer\console\docopt;

use rosasurfer\core\CObject;
use rosasurfer\core\exception\IllegalAccessException;


/**
 * DocoptResult
 *
 * Represents the parsing result of a {@link DocoptParser::parse()} call.
 */
class DocoptResult extends CObject implements \ArrayAccess, \IteratorAggregate {


    /** @var array<bool|int|string[]|null> */
    protected $args;

    /** @var int */
    protected $error;

    /** @var string */
    protected $errorMessage;


    /**
     * Constructor
     *
     * Create a new Docopt parser result.
     *
     * @param  array  $args                    - parsed arguments
     * @param  int    $error        [optional] - the result's error status  (default: 0)
     * @param  string $errorMessage [optional] - the result's error message (default: none)
     */
    public function __construct(array $args, $error=0, $errorMessage='') {
        $this->args         = $args;
        $this->error        = $error;
        $this->errorMessage = $errorMessage;
    }


    /**
     * Return the parsed CLI arguments.
     *
     * @return array<bool|int|string[]|null>
     */
    public function getArgs() {
        return $this->args;
    }


    /**
     * Return the parse result's error status. If parsing of the CLI arguments didn't cause a user-land syntax error the
     * error status will be 0 (zero).
     *
     * @return int
     */
    public function getError() {
        return $this->error;
    }


    /**
     * Return the parse result's error message.
     *
     * @return string
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }


    /**
     * Whether parsing of the CLI arguments caused a user-land syntax error.
     *
     * @return bool
     */
    public function isSuccess() {
        return (int)$this->error == 0;
    }


    /**
     * @param  mixed $offset
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset) {
        return isset($this->args[$offset]);
    }


    /**
     * @param  mixed $offset
     *
     * @return bool|int|string[]|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $this->args[$offset];
    }


    /**
     * @param  mixed $offset
     * @param  mixed $value
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) {
        throw new IllegalAccessException('Modification of CLI parse results denied');
    }


    /**
     * @param  mixed $offset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset) {
        throw new IllegalAccessException('Modification of CLI parse results denied');
    }


    /**
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator() {
        return new \ArrayIterator($this->args);
    }
}
