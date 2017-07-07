<?php
namespace rosasurfer\lock;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalStateException;


/**
 * BaseLock
 */
abstract class BaseLock extends Object {


    /**
     * Whether or not the lock is aquired and valid.
     *
     * @return bool
     */
    abstract public function isValid();


    /**
     * If called on an aquired and valid lock the lock is released and marked as invalid.
     * If called on an already relesed lock the call does nothing.
     *
     * @return void
     */
    abstract public function release();


    /**
     * Convert a key (string) to a unique numerical value (int).
     *
     * @param  string $key
     *
     * @return int - numerical value
     */
    protected function keyToId($key) {
        return (int) hexDec(subStr(md5($key), 0, 7)) + strLen($key);
    }                                         // 7: strLen(decHex(PHP_INT_MAX)) - 1 (x86)


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
        throw new IllegalStateException('You cannot unserialize me: '.get_class($this));
    }
}
