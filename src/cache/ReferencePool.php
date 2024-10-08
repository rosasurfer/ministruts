<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\cache;

use rosasurfer\ministruts\cache\monitor\Dependency;


/**
 * ReferencePool
 *
 * The most simple cache implementation. This cache stores values in the memory of the current process and all data is lost
 * at script termination. However this mechanism provides a simple identity manager, so multiple cache lookups for the same
 * key always yield the same data instance. Every cache implementation uses a ReferencePool instance as it's identy manager.
 */
final class ReferencePool extends CachePeer {


    /** @var array<string, mixed> */
    private array $pool;


    /**
     * Constructor.
     *
     * @param  ?string $label   [optional] - cache identifier (namespace, ignored for in-memory instances)
     * @param  mixed[] $options [optional] - additional instantiation options (default: none)
     */
    public function __construct(?string $label=null, array $options=[]) {
        $this->label   = $label;
        $this->options = $options;
    }


    /**
     * Return the {@link ReferencePool} instance of the cache (the identity manager).
     *
     * @return $this
     */
    protected function getReferencePool(): self {
        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $key
     *
     * @return bool
     */
    public function isCached($key): bool {
        if (!isset($this->pool[$key])) {
            return false;
        }

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
     *
     * @param  string $key
     * @param  mixed  $default [optional]
     *
     * @return mixed
     */
    public function get(string $key, $default = null) {
        if ($this->isCached($key)) {
            return $this->pool[$key][1];
        }
        return $default;
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $key
     *
     * @return bool
     */
    public function drop(string $key): bool {
        if (isset($this->pool[$key])) {
            unSet($this->pool[$key]);
            return true;
        }
        return false;
    }


    /**
     * {@inheritdoc}
     *
     * @param  string      $key
     * @param  mixed       $value
     * @param  int         $expires    [optional]
     * @param  ?Dependency $dependency [optional]
     *
     * @return bool
     */
    public function set(string $key, $value, int $expires = Cache::EXPIRES_NEVER, ?Dependency $dependency = null): bool {
        // stored data: [created, value, expires, dependency]
        $this->pool[$key] = [null, $value, null, $dependency];
        return true;
    }
}
