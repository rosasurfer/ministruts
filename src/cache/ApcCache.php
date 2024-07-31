<?php
namespace rosasurfer\cache;

use rosasurfer\cache\monitor\Dependency;
use rosasurfer\core\assert\Assert;


/**
 * ApcCache
 *
 * Userland cache for regular PHP objects.
 */
class ApcCache extends CachePeer {


    /**
     * Constructor.
     *
     * @param  ?string $label   [optional] - cache identifier (namespace, default: none)
     * @param  array   $options [optional] - additional instantiation options (default: none)
     */
    public function __construct($label=null, array $options=[]) {
        $this->label     = $label;
        $this->namespace = $label ?: md5($this->di('config')['app.dir.root']);
        $this->options   = $options;
    }


    /**
     *
     */
    public function isCached($key) {
        // The actual working horse. This method does not only check the key's existence, it also retrieves the value and
        // stores it in the local reference pool. Thus following cache queries can use the local reference.

        // check local reference pool
        if ($this->getReferencePool()->isCached($key))
            return true;

        // query APC cache
        $data = apc_fetch($this->namespace.'::'.$key);
        if (!$data) return false;                       // cache miss

        // cache hit
        /** @var int $created */
        $created = $data[0];                            // data: [created, expires, serialized([$value, $dependency])]
        /** @var int $expires */
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
                    // reset creation time by writing back to the cache (resets $minValid period)
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
     *
     */
    public function get($key, $default = null) {
        if ($this->isCached($key))
            return $this->getReferencePool()->get($key);
        return $default;
    }


    /**
     *
     */
    public function drop($key) {
        $this->getReferencePool()->drop($key);
        return apc_delete($this->namespace.'::'.$key);
    }


    /**
     *
     */
    public function set($key, &$value, $expires=Cache::EXPIRES_NEVER, Dependency $dependency=null) {
        Assert::string($key,  '$key');
        Assert::int($expires, '$expires');

        // stored data: [created, expires, serialized([value, dependency])]
        $fullKey = $this->namespace.'::'.$key;
        $created = time();
        $data    = [$value, $dependency];

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

            if (!apc_add($fullKey, [$created, $expires, serialize($data)])) {
                //Logger::log('apc_add() unexpectedly returned FALSE for $key "'.$fullKey.'" '.($isKey ? '(did exist and was deleted)':'(did not exist)'), L_WARN);
                return false;
            }
        }
        elseif (!apc_store($fullKey, [$created, $expires, serialize($data)])) {
            //Logger::log('apc_store() unexpectedly returned FALSE for $key "'.$fullKey.'" '.($isKey ? '(did exist and was deleted)':'(did not exist)'), L_WARN);
            return false;
        }

        $this->getReferencePool()->set($key, $value, $expires, $dependency);
        return true;
    }
}
