<?php
/**
 * Interface to implement by custom configuration classes.
 */
interface ConfigInterface {


   /**
    * Return the config setting with the specified key or the specified alternative value if no such is found.
    *
    * @param  string $key        - key
    * @param  mixed  $onNotFound - alternative value
    *
    * @return mixed - config setting
    *
    * @throws RuntimeException - if no such setting is found and no alternative value was specified
    */
   public function get($key, $onNotFound=null);


   /**
    * Set the config setting with the specified key.
    *
    * @param  string $key   - key
    * @param  string $value - new value
    */
   public function set($key, $value);
}
