<?php
namespace rosasurfer\core\lock;

use rosasurfer\core\CObject;
use rosasurfer\core\exception\IllegalStateException;


/**
 * BaseLock
 */
abstract class BaseLock extends CObject {


    /**
     * Whether the lock is aquired.
     *
     * @return bool
     */
    abstract public function isAquired();


    /**
     * If called on an aquired lock the lock is released. If called on an already released lock the call does nothing.
     *
     * @return void
     */
    abstract public function release();


    /**
     * Prevent serialization of lock instances.
     */
    final public function __sleep() {
        throw new IllegalStateException('You cannot serialize me: '.get_class($this));
    }


    /**
     * Prevent de-serialization of lock instances.
     */
    final public function __wakeUp() {
        throw new IllegalStateException('You cannot deserialize me: '.get_class($this));
    }
}
