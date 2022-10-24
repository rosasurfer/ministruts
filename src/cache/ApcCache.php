<?php
namespace rosasurfer\cache;

use rosasurfer\config\Config;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\monitor\Dependency;


/**
 * ApcCache
 *
 * Userland cache for regular PHP objects.
 */
class ApcCache extends CachePeer {


    /**
     * Constructor.
     *
     * @param  string $label   [optional] - cache identifier used for namespacing
     * @param  array  $options [optional] - additional options
     */
    public function __construct($label=null, array $options=[]) {
        $this->label     = $label;
        $this->namespace = $label ?: md5(Config::getDefault()->get('app.dir.root'));
        $this->options   = $options;
    }


    /**
     * Whether or not a value with the specified key exists in the cache.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function isCached($key) {
        // The actual working horse. The method does not only check existence of the key. It retrieves the value and stores
        // it in the local reference pool, so following cache queries can use the local reference.

        // check local reference pool
        if ($this->getReferencePool()->isCached($key))
            return true;

        // query APC cache
        $data = apc_fetch($this->namespace.'::'.$key);
        if (!$data)                                     // cache miss
            return false;

        // cache hit
        $created = $data[0];                            // data: [created, expires, serialized([$value, $dependency])]
        $expires = $data[1];

        // check expiration
        if ($expires && $created+$expires < time()) {
            $this->drop($key);
            return false;
        }

        // unpack serialized value
        $data[2]    = unserialize($data[2]);
        $value      = $data[2][0];
        $dependency = $data[2][1];

        // check dependency
        if ($dependency) {
            $minValid = $dependency->getMinValidity();

            if ($minValid) {
                if (time() > $created+$minValid) {
                    if (!$dependency->isValid()) {
                        $this->drop($key);
                        return false;
                    }
                    // reset the creation time by writing back to the cache (resets $minValid period)
                    return $this->set($key, $value, $expires, $dependency);
                }
            }
            elseif (!$dependency->isValid()) {
                $this->drop($key);
                return false;
            }
        }

        // store the validated value in the local reference pool
        $this->getReferencePool()->set($key, $value, Cache::EXPIRES_NEVER, $dependency);
        return true;
    }


    /**
     * Return the cached value with the specified key or the default value if no such value exists in the cache.
     *
     * @param  string $key                - key
     * @param  mixed  $default [optional] - default value
     *
     * @return mixed - cached value (may be NULL) or NULL if no value with the specified key exists in the cache
     */
    public function get($key, $default = null) {
        if ($this->isCached($key))
            return $this->getReferencePool()->get($key);
        return $default;
    }


    /**
     * Delete the value with the specified key from the cache.
     *
     * @param  string $key - key
     *
     * @return bool - TRUE on successful deletion;
     *                FALSE if no such value exists in the cache
     */
    public function drop($key) {
        $this->getReferencePool()->drop($key);

        return apc_delete($this->namespace.'::'.$key);
    }


    /**
     * Store a value under the specified key in the cache. An existing value already stored under the key is overwritten.
     * If expiration time or dependency are provided and those conditions are met the value is automatically invalidated but
     * not automatically removed from the cache.
     *
     * @param  string     $key                   - key
     * @param  mixed      $value                 - value
     * @param  int        $expires    [optional] - time in seconds after which the value becomes invalid (default: never)
     * @param  Dependency $dependency [optional] - a dependency object monitoring validity of the value (default: none)
     *
     * @return bool - TRUE on successful storage; FALSE otherwise
     */
    public function set($key, &$value, $expires=Cache::EXPIRES_NEVER, Dependency $dependency=null) {
        if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.gettype($key));
        if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.gettype($expires));

        // data format in the cache: [created, expires, serialized([value, dependency])]
        $fullKey = $this->namespace.'::'.$key;
        $created = time();
        $data    = array($value, $dependency);

        /**
         * PHP 5.3.3/APC 3.1.3
         * -------------------
         * Bug: warning "Potential cache slam averted for key '...'"
         *      - apc_add() and apc_store() return FALSE if called multiple times for the same key
         *
         * @see http://bugs.php.net/bug.php?id=58832
         * @see http://stackoverflow.com/questions/4983370/php-apc-potential-cache-slam-averted-for-key
         *
         * - solution for APC >= 3.1.7: re-introduced setting apc.slam_defense=0
         * - no solution yet for APC 3.1.3-3.1.6
         *
         * @see http://serverfault.com/questions/342295/apc-keeps-crashing
         * @see http://stackoverflow.com/questions/1670034/why-would-apc-store-return-false
         * @see http://notmysock.org/blog/php/user-cache-timebomb.html
         */


        // store value:
        // - If possible use apc_add() which causes less memory fragmentation and minimizes lock waits.
        // - Don't use APC-TTL as the real TTL is checked in self::isCached(). APC-TTL causes various APC bugs.
        if (function_exists('apc_add')) {                                               // APC >= 3.0.13
            if (function_exists('apc_exists')) $isKey =        apc_exists($fullKey);    // APC >= 3.1.4
            else                               $isKey = (bool) apc_fetch ($fullKey);
            if ($isKey)
                apc_delete($fullKey);      // apc_delete()+apc_add() result in less memory fragmentation than apc_store()

            if (!apc_add($fullKey, array($created, $expires, serialize($data)))) {
                //Logger::log('apc_add() unexpectedly returned FALSE for $key "'.$fullKey.'" '.($isKey ? '(did exist and was deleted)':'(did not exist)'), L_WARN);
                return false;
            }
        }
        elseif (!apc_store($fullKey, array($created, $expires, serialize($data)))) {
            //Logger::log('apc_store() unexpectedly returned FALSE for $key "'.$fullKey.'" '.($isKey ? '(did exist and was deleted)':'(did not exist)'), L_WARN);
            return false;
        }

        $this->getReferencePool()->set($key, $value, $expires, $dependency);
        return true;
    }
}
