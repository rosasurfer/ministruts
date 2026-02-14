<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\config;

use ArrayAccess;
use Countable;

use rosasurfer\ministruts\core\exception\RuntimeException;

/**
 * Interface to be implemented by concrete configurations.
 *
 * @extends ArrayAccess<string, mixed>
 */
interface Config extends ArrayAccess, Countable {

    /**
     * Return the config setting with the specified key or the default value if no such setting is found.
     *
     * @param  string $key                - case-insensitive key
     * @param  mixed  $default [optional] - default value
     *
     * @return mixed - config setting
     *
     * @throws RuntimeException if the setting is not found and no default value was specified
     */
    public function get(string $key, $default = null);


    /**
     * Return the config setting with the specified key interpreted as an integer. Accepted integer representations are integer values
     * and strings matching integer notation (e.g. "0", "42", "-7"). Not existing settings and non-integer values trigger an exception.
     *
     * @param  string $key                - case-insensitive key
     * @param  int    $default [optional] - value to return if the config setting does not exist (default: exception)
     *
     * @return int - interpreted config setting or the specified default value
     *
     * @throws RuntimeException if the setting is not found or cannot be interpreted as an integer
     */
    public function int(string $key, int $default = 0): int;


    /**
     * Return the config setting with the specified key interpreted as a floating point number. Accepted float representations are
     * integer/float values and strings in numeric notation (e.g. "1", "3.14", "-2.5e3"). Not existing settings and non-integer values
     * trigger an exception.
     *
     * @param  string $key                - case-insensitive key
     * @param  float  $default [optional] - value to return if the config setting does not exist (default: exception)
     *
     * @return float - interpreted config setting or the specified default value
     *
     * @throws RuntimeException if the setting is not found or cannot be interpreted as a float
     */
    public function float(string $key, float $default = 0.0): float;


    /**
     * Return the config setting with the specified key interpreted as a boolean. Accepted strict boolean representations are "1" and "0",
     * "true" and "false", "on" and "off", "yes" and "no" (case-insensitive), as well as native booleans. Not existing settings and
     * non-boolean values trigger an exception.
     *
     * @param  string $key                - case-insensitive key
     * @param  bool   $strict  [optional] - whether to apply strict interpretation rules:
     *                                      FALSE - returns TRUE for "1", "true", "on" and "yes", otherwise FALSE (default)
     *                                      TRUE  - as above but FALSE is returned only for "0", "false", "off" and "no", otherwise exception
     * @param  bool   $default [optional] - value to return if the config setting does not exist (default: exception)
     *
     * @return bool - interpreted config setting or the specified default value
     *
     * @throws RuntimeException if the setting is not found or cannot be interpreted as a boolean
     */
    public function bool(string $key, bool $strict = false, bool $default = false): bool;


    /**
     * Return the config setting with the specified key interpreted as a string. Scalar values are casted to string. Not existing settings
     * and non-scalar values trigger an exception.
     *
     * @param  string $key                - case-insensitive key
     * @param  string $default [optional] - value to return if the config setting does not exist (default: exception)
     *
     * @return string - interpreted config setting or the specified default value
     *
     * @throws RuntimeException if the setting is not found or is non-scalar
     */
    public function string(string $key, string $default = ''): string;


    /**
     * Set/modify the config setting with the specified key.
     *
     * @param  int|string $key   - case-insensitive key
     * @param  mixed      $value - new value
     *
     * @return $this
     */
    public function set($key, $value): self;


    /**
     * Return a plain text dump of the instance's preferences.
     *
     * @param  array<string, int|string> $options [optional] - array with dump options, may be any of:                   <br>
     *                                                         'sort'     => int: SORT_ASC|SORT_DESC (default: unsorted) <br>
     *                                                         'pad-left' => string (default: no padding)                <br>
     * @return string
     */
    public function dump(array $options = []): string;


    /**
     * Return an array with "key-value" pairs of the config settings.
     *
     * @param  array<string, int> $options [optional] - array with export options, may be:                    <br>
     *                                                  'sort' => int: SORT_ASC|SORT_DESC (default: unsorted) <br>
     * @return array<string, string>
     */
    public function export(array $options = []): array;


    /**
     * Return the names of originating configuration files. The returned array can contain
     * names of existing and non-existing files, together with their status (found/not found).
     *
     * @return array<string, bool>
     */
    public function getConfigFiles(): array;
}
