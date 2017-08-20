<?php
namespace rosasurfer\config;

use rosasurfer\exception\RuntimeException;


/**
 * Interface to be implemented by concrete configurations.
 */
interface ConfigInterface extends \ArrayAccess {


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
    public function get($key, $default = null);


    /**
     * Return the config setting with the specified key as a boolean. Accepted boolean value representations are "1" and "0",
     * "true" and "false", "on" and "off", "yes" and "no" (case-insensitive).
     *
     * @param  string $key                - case-insensitive key
     * @param  array  $options [optional] - array with any of the following options:
     *                   'null-on-error' => bool: whether or not to return NULL for non-boolean representations (default: no)
     *                   'default'       => bool: default value to return if the setting is not found
     *
     * @return bool|null - boolean value or NULL if the $options['null-on-error'] is TRUE and the setting does not represent
     *                     a boolean value
     *
     * @throws RuntimeException if the setting is not found and $options['default'] value was specified
     */
    public function getBool($key, array $options = []);


    /**
     * Set/modify the config setting with the specified key.
     *
     * @param  string $key   - case-insensitive key
     * @param  mixed  $value - new value
     *
     * @return $this
     */
    public function set($key, $value);


    /**
     * Return the directory of the most recently loaded configuration file.
     *
     * @return string|null - directory name or NULL if the configuration is not based on files
     */
    public function getDirectory();


    /**
     * Return an informative text describing the instance.
     *
     * @return string
     */
    public function info();
}
