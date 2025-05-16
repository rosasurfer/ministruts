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
     * Return the config setting with the specified key as a boolean. Accepted strict boolean representations are "1" and "0",
     * "true" and "false", "on" and "off", "yes" and "no" (case-insensitive).
     *
     * @param  string $key                - case-insensitive key
     * @param  bool   $strict  [optional] - whether to apply strict interpretation rules:
     *                                      FALSE - returns TRUE only for "1", "true", "on" and "yes", and FALSE otherwise (default)
     *                                      TRUE  - as above but FALSE is returned only for "0", "false", "off" and "no",
     *                                              otherwise an exception is thrown
     * @param  bool   $default [optional] - value to return if the config setting does not exist (default: exception)
     *
     * @return bool - config setting or the specified default value
     *
     * @throws RuntimeException if the setting is not found or is not strict boolean
     */
    public function getBool(string $key, bool $strict=false, bool $default=false): bool;


    /**
     * Return the config setting with the specified key as a string. Scalar setting values are casted to string. Not existing and
     * non-scalar settings trigger an exception.
     *
     * @param  string $key                - case-insensitive key
     * @param  string $default [optional] - value to return if the config setting does not exist (default: exception)
     *
     * @return string - config setting or the specified default value
     *
     * @throws RuntimeException if the setting is not found or is non-scalar
     */
    public function getString(string $key, string $default = ''): string;

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
