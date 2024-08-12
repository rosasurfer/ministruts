<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\config;

use ArrayAccess;
use rosasurfer\ministruts\core\exception\RuntimeException;


/**
 * Interface to be implemented by concrete configurations.
 *
 * @extends ArrayAccess<string, mixed>
 */
interface ConfigInterface extends \ArrayAccess, \Countable {


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
     * Return the config setting with the specified key as a boolean. Accepted strict boolean value representations are "1" and "0",
     * "true" and "false", "on" and "off", "yes" and "no" (case-insensitive).
     *
     * @param  string  $key                - case-insensitive key
     * @param  ?bool   $default [optional] - value to return if the config setting does not exist (default: exception)
     * @param  bool    $strict  [optional] - whether to apply strict interpretation rules:
     *                                       FALSE - returns TRUE only for "1", "true", "on" and "yes", and FALSE otherwise (default)
     *                                       TRUE  - as above but FALSE is returned only for "0", "false", "off" and "no", and NULL
     *                                               is returned for all other values
     *
     * @return ?bool - boolean value or NULL if the found setting does not represent a requested strict boolean value
     *
     * @throws RuntimeException if the setting is not found and no default value was specified
     */
    public function getBool(string $key, ?bool $default=false, bool $strict=false): ?bool;


    /**
     * Set/modify the config setting with the specified key.
     *
     * @param  string $key   - case-insensitive key
     * @param  mixed  $value - new value
     *
     * @return $this
     */
    public function set(string $key, $value): self;


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
}
