<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt;

use ArrayIterator;

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
    protected array $args;

    /** @var string */
    protected string $usage;

    /** @var int */
    protected int $error;

    /** @var string */
    protected string $errorMessage;


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
     * @return array<string, bool|int|string[]|null>
     */
    public function getArgs(): array {
        return $this->args;
    }


    /**
     * Return the parsed usage block of the Docopt definition.
     *
     * @return string
     */
    public function getUsage(): string {
        return $this->usage;
    }


    /**
     * Return the parsing result's error status. If parsing of the CLI arguments didn't cause a user-land syntax error the
     * error status will be 0 (zero).
     *
     * @return int
     */
    public function getError(): int {
        return $this->error;
    }


    /**
     * Return the parsing result's error message.
     *
     * @return string
     */
    public function getErrorMessage(): string {
        return $this->errorMessage;
    }


    /**
     * Whether parsing of the CLI arguments caused a user-land syntax error.
     *
     * @return bool
     */
    public function isSuccess(): bool {
        return (int)$this->error == 0;
    }


    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool {
        return key_exists($offset, $this->args);
    }


    /**
     * {@inheritDoc}
     *
     * @return bool|int|string[]|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $this->args[$offset];
    }


    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void {
        throw new IllegalAccessException('Modification of CLI parse results denied');
    }


    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void {
        throw new IllegalAccessException('Modification of CLI parse results denied');
    }


    /**
     * {@inheritDoc}
     *
     * @return ArrayIterator<string, bool|int|string[]|null>
     */
    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->args);                  // @phpstan-ignore return.type (false positive in PHPStan2+ levels 3-7)
    }                                                           // Template type TValue on class ArrayIterator is not covariant.
}
