<?php
namespace rosasurfer\config;

use rosasurfer\exception\RuntimeException;


/**
 * Interface to be implemented by concrete configurations.
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
    public function get($key, $default = null);


    /**
     * Return the config setting with the specified key as a boolean. Accepted boolean value representations are "1" and "0",
     * "true" and "false", "on" and "off", "yes" and "no" (case-insensitive).
     *
     * @param  string         $key                - case-insensitive key
     * @param  bool|int|array $options [optional] - additional options as supported by <tt>filter_var($var, FILTER_VALIDATE_BOOLEAN)</tt>, <br>
     *                                              may be any of: <br>
     *                   bool $default            - default value to return if the setting is not found <br>
     *                   int  $flags              - flags as supported by <tt>filter_var($var, FILTER_VALIDATE_BOOLEAN)</tt>: <br>
     *                                              FILTER_NULL_ON_FAILURE - return NULL instead of FALSE on failure <br>
     *                  array $options            - multiple options are passed as elements of an array: <br>
     *                                              <tt>$options[              <br>
     *                                                  'default' => $default, <br>
     *                                                  'flags'   => $flags    <br>
     *                                              ]</tt>                     <br>
     * @return bool|null - boolean value or NULL if the flag FILTER_NULL_ON_FAILURE is set and the setting does not represent
     *                     a boolean value
     *
     * @throws RuntimeException if the setting is not found and $default was not specified
     */
    public function getBool($key, $options = null);


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
     * Return a plain text dump of the instance's preferences.
     *
     * @param  array $options [optional] - array with dump options: <br>
     *                                     'sort'     => SORT_ASC|SORT_DESC (default: unsorted) <br>
     *                                     'pad-left' => string             (default: no padding) <br>
     * @return string
     */
    public function dump(array $options = null);


    /**
     * Return an array with "key-value" pairs of the config settings.
     *
     * @param  array $options [optional] - array with export options: <br>
     *                                     'sort' => SORT_ASC|SORT_DESC (default: unsorted) <br>
     * @return string[]
     */
    public function export(array $options = null);
}
