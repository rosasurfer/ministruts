<?php
namespace rosasurfer\cache;

use rosasurfer\cache\monitor\Dependency;
use rosasurfer\core\CObject;


/**
 * CachePeer
 *
 * Abstract base class for actual cache implementations.
 *
 * @example
 * <pre>
 *  &lt;?php
 *  CachePeer::set($key, $value, $expires);         // store a value in the cache
 *  CachePeer::add($key, $value, $expires);         // store a value only if it doesn't yet exist in the cache
 *  CachePeer::replace($key, $value, $expires);     // store a value only if it already exists in the cache
 *  $value = CachePeer::get($key);                  // retrieve a value from the cache
 *  CachePeer::drop($key);                          // delete a cached value
 * </pre>
 *
 * @see ApcCache
 * @see FileSystemCache
 * @see ReferencePool
 */
abstract class CachePeer extends CObject {


    /** @var string */
    protected $label;

    /** @var string */
    protected $namespace;

    /** @var array */
    protected $options;

    /** @var ?ReferencePool */
    private $referencePool = null;


    /**
     * Return the {@link ReferencePool} instance of the cache (the identity manager).
     *
     * @return ReferencePool
     */
    protected function getReferencePool() {
        if (!$this->referencePool) {
            $this->referencePool = new ReferencePool($this->label);
        }
        return $this->referencePool;
    }


    /**
     * Whether a value exists in the cache under the specified key.
     *
     * @param  string $key - identifier
     *
     * @return bool
     */
    abstract public function isCached($key);


    /**
     * Retrieve a value from the cache or the specified default value if no such value exists in the cache.
     *
     * @param  string $key                - identifier of the stored value
     * @param  mixed  $default [optional] - default value
     *
     * @return mixed - stored value (may be NULL itself) or the specified default value
     */
    abstract public function get($key, $default = null);


    /**
     * Delete the value with the specified key from the cache.
     *
     * @param  string $key - key
     *
     * @return bool - success status or FALSE if no such value exists in the cache
     */
    abstract public function drop($key);


    /**
     * Store a value under the specified key in the cache and overwrite an existing value. The stored value is automatically
     * invalidated after expiration of the specified time interval or on status change of the specified {@link Dependency}.
     *
     * @param  string     $key                   - key
     * @param  mixed      $value                 - value
     * @param  int        $expires    [optional] - time interval in seconds for automatic invalidation (default: never)
     * @param  Dependency $dependency [optional] - dependency object monitoring validity of the value (default: none)
     *
     * @return bool - success status
     */
    abstract public function set($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null);


    /**
     * Store a value in the cache only if there's no other value yet stored under the same key. The stored value is
     * automatically invalidated after expiration of the specified time interval or on status change of the specified
     * {@link Dependency}.
     *
     * @param  string     $key                   - key
     * @param  mixed      $value                 - value
     * @param  int        $expires    [optional] - time interval in seconds for automatic invalidation (default: never)
     * @param  Dependency $dependency [optional] - dependency object monitoring validity of the value (default: none)
     *
     * @return bool - success status
     */
    final public function add($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
        if ($this->isCached($key))
            return false;
        return $this->set($key, $value, $expires, $dependency);
    }


    /**
     * Store a value in the cache only if there's already another value stored under the same key. Overwrites the existing
     * value. The stored value is automatically invalidated after expiration of the specified time interval or on status
     * change of the specified {@link Dependency}.
     *
     * @param  string     $key                   - key
     * @param  mixed      $value                 - value
     * @param  int        $expires    [optional] - time interval in seconds for automatic invalidation (default: never)
     * @param  Dependency $dependency [optional] - dependency object monitoring validity of the value (default: none)
     *
     * @return bool - success status
     */
    final public function replace($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
        if (!$this->isCached($key))
            return false;
        return $this->set($key, $value, $expires, $dependency);
    }
}
