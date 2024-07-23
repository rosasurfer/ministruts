<?php
namespace rosasurfer\console\docopt;

use rosasurfer\core\CObject;
use rosasurfer\core\exception\IllegalAccessException;

use const rosasurfer\NL;


/**
 * DocoptResult
 *
 * Represents the parsing result of a {@link DocoptParser::parse()} call.
 */
class DocoptResult extends CObject implements \ArrayAccess, \IteratorAggregate {


    /** @var array<bool|int|string[]|null> */
    protected $args;

    /** @var string */
    protected $usage;

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
     * @param  string $usage                   - parsed usage block of the Docopt definition
     * @param  int    $error        [optional] - the parsing result's error status (default: 0)
     * @param  string $errorMessage [optional] - the parsing result's error message (default: none)
     */
    public function __construct(array $args, $usage, $error=0, $errorMessage='') {
        $this->args         = $args;
        $this->usage        = trim($usage).NL;
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
     * Return the parsed usage block of the Docopt definition.
     *
     * @return string
     */
    public function getUsage() {
        return $this->usage;
    }


    /**
     * Return the parsing result's error status. If parsing of the CLI arguments didn't cause a user-land syntax error the
     * error status will be 0 (zero).
     *
     * @return int
     */
    public function getError() {
        return $this->error;
    }


    /**
     * Return the parsing result's error message.
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
    public function offsetExists($offset) {
        return isset($this->args[$offset]);
    }


    /**
     * @param  mixed $offset
     *
     * @return bool|int|string[]|null
     */
    public function offsetGet($offset) {
        return $this->args[$offset];
    }


    /**
     * @param  mixed $offset
     * @param  mixed $value
     */
    public function offsetSet($offset, $value) {
        throw new IllegalAccessException('Modification of CLI parse results denied');
    }


    /**
     * @param  mixed $offset
     */
    public function offsetUnset($offset) {
        throw new IllegalAccessException('Modification of CLI parse results denied');
    }


    /**
     * @return \ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->args);
    }
}
