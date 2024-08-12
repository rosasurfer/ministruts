<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\IllegalAccessException;

use const rosasurfer\ministruts\NL;


/**
 * DocoptResult
 *
 * Represents the parsing result of a {@link DocoptParser::parse()} call.
 *
 * @implements \ArrayAccess<string, bool|int|string[]|null>
 * @implements \IteratorAggregate<string, bool|int|string[]|null>
 */
class DocoptResult extends CObject implements \ArrayAccess, \IteratorAggregate {


    /** @var array<string, bool|int|string[]|null> */
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
     * @param  array<string, bool|int|string[]|null> $args                    - parsed arguments
     * @param  string                                $usage                   - parsed usage block of the Docopt definition
     * @param  int                                   $error        [optional] - the parsing result's error status (default: 0)
     * @param  string                                $errorMessage [optional] - the parsing result's error message (default: none)
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
     * {@inheritdoc}
     *
     * @param  mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->args[$offset]);
    }


    /**
     * {@inheritdoc}
     *
     * @param  mixed $offset
     *
     * @return bool|int|string[]|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $this->args[$offset];
    }


    /**
     * {@inheritdoc}
     *
     * @param  mixed $offset
     * @param  mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value): void {
        throw new IllegalAccessException('Modification of CLI parse results denied');
    }


    /**
     * {@inheritdoc}
     *
     * @param  mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset): void {
        throw new IllegalAccessException('Modification of CLI parse results denied');
    }


    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<string, bool|int|string[]|null>
     */
    public function getIterator() {
        return new \ArrayIterator($this->args);
    }
}
