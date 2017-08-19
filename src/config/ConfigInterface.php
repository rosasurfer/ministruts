<?php
namespace rosasurfer\config;

use rosasurfer\exception\RuntimeException;


/**
 * Interface to be implemented by concrete configurations.
 */
interface ConfigInterface {


    /**
     * Return the config setting with the specified key or the specified default value if no such setting is found.
     *
     * @param  string $key                - key (case-insensitive)
     * @param  mixed  $default [optional] - default value
     *
     * @return mixed - config setting
     *
     * @throws RuntimeException if the setting is not found and no default value was specified
     */
    public function get($key, $default = null);


    /**
     * Set/modify the config setting with the specified key.
     *
     * @param  string $key   - key (case-insensitive)
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
