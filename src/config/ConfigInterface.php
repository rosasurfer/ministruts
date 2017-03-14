<?php
namespace rosasurfer\config;

use rosasurfer\exception\RuntimeException;


/**
 * Interface to be implemented by concrete configurations.
 */
interface ConfigInterface {


    /**
     * Return the config setting with the specified key or the specified alternative value if no such setting is found.
     *
     * @param  string $key        - key
     * @param  mixed  $onNotFound - alternative value
     *
     * @return string|array - config setting
     *
     * @throws RuntimeException - if the setting is not found and no alternative value was specified
     */
    public function get($key, $onNotFound = null);


    /**
     * Set/modify the config setting with the specified key.
     *
     * @param  string $key   - key
     * @param  mixed  $value - new value
     *
     * @return self
     */
    public function set($key, $value);


    /**
     * Get the instance's configuration directory.
     *
     * @return string
     */
    public function getDirectory();


    /**
     * Return an informative text describing the instance.
     *
     * @return string
     */
    public function info();
}
