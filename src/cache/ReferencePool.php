<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\cache;

use rosasurfer\ministruts\cache\monitor\Dependency;
use rosasurfer\ministruts\core\assert\Assert;


/**
 * ReferencePool
 *
 * The most simple cache implementation. This cache stores values in the memory of the current process and all data is lost
 * at script termination. However this mechanism provides a simple identity manager, so multiple cache lookups for the same
 * key always yield the same data instance. Every cache implementation uses a ReferencePool instance as it's identy manager.
 */
final class ReferencePool extends CachePeer {


    /** @var array<string, mixed> */
    private $pool;


    /**
     * Constructor.
     *
     * @param  ?string               $label   [optional] - cache identifier (namespace, ignored for in-memory instances)
     * @param  array<string, scalar> $options [optional] - additional instantiation options (default: none)
     */
    public function __construct($label=null, array $options=[]) {
        $this->label   = $label;
        $this->options = $options;
    }


    /**
     * Return the {@link ReferencePool} instance of the cache (the identity manager).
     *
     * @return $this
     */
    protected function getReferencePool() {
        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function isCached($key) {
        if (!isset($this->pool[$key]))
            return false;

        // @todo: As long as we don't have the $created value from the real cache, we can't check $expires or $minValidity.
        //$dependency = $this->pool[$key][3];
        //
        //if ($dependency && !$dependency->getMinValidity() && !$dependency->isValid()) {
        //    unSet($this->pool[$key]);
        //    return false;
        //}
        return true;
    }


    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null) {
        if ($this->isCached($key))
            return $this->pool[$key][1];
        return $default;
    }


    /**
     * {@inheritdoc}
     */
    public function drop($key) {
        if (isset($this->pool[$key])) {
            unSet($this->pool[$key]);
            return true;
        }
        return false;
    }


    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
        Assert::string($key, '$key');
        Assert::int($expires, '$expires');

        // stored data: [created, value, expires, dependency]
        $this->pool[$key] = [null, $value, null, $dependency];
        return true;
    }
}
