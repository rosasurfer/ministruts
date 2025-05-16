<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\IllegalAccessException;


/**
 * ActionInput
 *
 * An object providing access to the current HTTP request's raw user input.
 * Use {@link ActionForm} to access the request's validated and interpreted input.
 *
 * @implements \ArrayAccess<string, string|array<string>>
 */
class ActionInput extends CObject implements \ArrayAccess {


    /** @var array<string, string|array<string|array<string>>> */
    protected array $parameters;


    /**
     * Constructor
     *
     * @param  array<string, string|array<string|array<string>>> $parameters
     */
    public function __construct(array $parameters) {
        $this->parameters = $parameters;
    }


    /**
     * Return all raw input parameters.
     *
     * @return array<string, string|array<string|array<string>>>
     */
    public function all(): array {
        return $this->parameters;
    }


    /**
     * Return a single raw input parameter with the specified name, or the passed default value if no such input parameter
     * was transmitted. If multiple parameters with that name have been transmitted the last one is returned. If an array
     * of parameters with that name has been transmitted it is ignored. Use {@link ActionInput::getArray()} to access an
     * array of raw input parameters.
     *
     * @param  string  $name               - parameter name
     * @param  ?string $default [optional] - value to return if the specified parameter was not transmitted (default: NULL)
     *
     * @return         ?string
     * @phpstan-return ($default is null ? ?string : string)
     */
    public function get(string $name, ?string $default = null): ?string {
        if (\key_exists($name, $this->parameters)) {
            if (!is_array($this->parameters[$name])) {
                return $this->parameters[$name];
            }
        }
        return $default;
    }


    /**
     * Return an array of raw input parameter with the specified name, or the passed default values if no such input
     * parameter array was transmitted. If a single parameter with that name has been transmitted it is ignored.
     * Use {@link ActionInput::get()} to access a single raw input parameter.
     *
     * @param  string   $name               - parameter name
     * @param  string[] $default [optional] - values to return if the specified parameter array was not transmitted (default: empty array)
     *
     * @return array<string|string[]>
     */
    public function getArray(string $name, array $default = []): array {
        if (\key_exists($name, $this->parameters)) {
            if (is_array($this->parameters[$name])) {
                return $this->parameters[$name];
            }
        }
        return $default;
    }


    /**
     * Whether a single raw input parameter with the specified name has been transmitted. A transmitted array of parameters
     * with that name is ignored. Use {@link ActionInput::hasArray()} to test for an array of parameters.
     *
     * @param  string $name - parameter name
     *
     * @return bool
     */
    public function has(string $name): bool {
        if (\key_exists($name, $this->parameters)) {
            return !is_array($this->parameters[$name]);
        }
        return false;
    }


    /**
     * Whether an array of raw input parameter with the specified name has been transmitted. A transmitted single parameter
     * with that name is ignored. Use {@link ActionInput::has()} to test for a single parameter.
     *
     * @param  string $name - parameter name
     *
     * @return bool
     */
    public function hasArray(string $name): bool {
        if (\key_exists($name, $this->parameters)) {
            return is_array($this->parameters[$name]);
        }
        return false;
    }


    /**
     * Whether a single or an array parameter with the specified name exists.
     *
     * {@inheritDoc}
     */
    public function offsetExists($name): bool {
        return \key_exists($name, $this->parameters);
    }


    /**
     * Return the single or array input parameter with the specified name.
     *
     * @param  mixed $name
     *
     * @return string|array<string|string[]>|null - parameter or NULL if no such input parameter exists
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($name) {
        if (\key_exists($name, $this->parameters)) {
            return $this->parameters[$name];
        }
        return null;
    }


    /**
     * Prevent modification of input parameters.
     *
     * {@inheritDoc}
     */
    final public function offsetSet($name, $value): void {
        throw new IllegalAccessException('Cannot modify ActionInput parameters');
    }


    /**
     * Prevent modification of input parameters.
     *
     * {@inheritDoc}
     */
    final public function offsetUnset($name): void {
        throw new IllegalAccessException('Cannot modify ActionInput parameters');
    }
}
