<?php
namespace rosasurfer\lock;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalStateException;


/**
 * BaseLock
 */
abstract class BaseLock extends Object {


   /**
    * Ob dieses Lock gültig (valid) ist.
    *
    * @return bool
    */
   abstract public function isValid();


   /**
    * Wenn dieses Lock gültig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
    * markiert es als ungültig (invalid).  Wenn das Lock bereits ungültig (invalid) ist, hat der Aufruf
    * keinen Effekt.
    *
    * @see Lock::isValid()
    */
   abstract public function release();


   /**
    * Transformiert einen Schlüssel (String) in einen eindeutigen numerischen Wert (Integer).
    *
    * @param  string $key - Schlüssel
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
   public final function __sleep() {
      throw new IllegalStateException('You cannot serialize me: '.get_class($this));
   }


   /**
    * Verhindert das Deserialisieren von Lock-Instanzen.
    */
   public final function __wakeUp() {
      throw new IllegalStateException('You cannot unserialize me: '.get_class($this));
   }
}
