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
     * @param  array  $options [optional] - associative array of options of <tt>filter_var($var, FILTER_VALIDATE_BOOLEAN)</tt> <br>
     *                'flags'   => FILTER_NULL_ON_FAILURE: return NULL instead of FALSE on failure <br>
     *                'default' => bool:                   default value to return if the setting is not found <br>
     *
     * @return bool|null - boolean value or NULL if the flag FILTER_NULL_ON_FAILURE is set and the setting does not represent
     *                     a boolean value
     *
     * @throws RuntimeException if the setting is not found and $options['default'] was not specified
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
     * Return the names of the monitored configuration files. The resulting array will contain names of existing and (still)
     * non-existing files.
     *
     * @return bool[] - array with elements "file-name" => (bool)status or an empty array if the configuration
     *                  is not based on files
     */
    public function getMonitoredFiles();


    /**
     * Return a dump with the preferences of the instance.
     *
     * @param  string $leftPad [optional] - string to use for left-padding the dump (default: empty string)
     *
     * @return string
     */
    public function dump($leftPad = '');
}
