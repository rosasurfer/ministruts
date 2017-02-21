<?php
namespace rosasurfer\lock;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalStateException;


/**
 * BaseLock
 */
abstract class BaseLock extends Object {


    /**
     * Ob dieses Lock gueltig (valid) ist.
     *
     * @return bool
     */
    abstract public function isValid();


    /**
     * Wenn dieses Lock gueltig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
     * markiert es als ungueltig (invalid).  Wenn das Lock bereits ungueltig (invalid) ist, hat der Aufruf
     * keinen Effekt.
     *
     * @see Lock::isValid()
     */
    abstract public function release();


    /**
     * Transformiert einen Schluessel (String) in einen eindeutigen numerischen Wert (Integer).
     *
     * @param  string $key - Schluessel
     *
     * @return int
     */
    protected function keyToId($key) {
        return (int) hexDec(subStr(md5($key), 0, 7)) + strLen($key);
                                            // 7: strLen(decHex(PHP_INT_MAX)) - 1   (x86)
    }


    /**
     * Verhindert das Serialisieren von Lock-Instanzen.
     */
    final public function __sleep() {
        throw new IllegalStateException('You cannot serialize me: '.get_class($this));
    }


    /**
     * Verhindert das Deserialisieren von Lock-Instanzen.
     */
    final public function __wakeUp() {
        throw new IllegalStateException('You cannot unserialize me: '.get_class($this));
    }
}
