<?
/**
 * Lock
 *
 * A token representing a lock.
 *
 * A lock object is initially valid.  It remains valid until the lock is released by invoking the
 * release() method or by the termination of the current PHP process, whichever comes first. The validity
 * of a lock may be tested by invoking its isValid() method.  Once it is released, a lock has no further
 * effect.
 *
 * Only the validity of a lock is subject to change over time; all other aspects of a lock's state are
 * immutable.
 */
abstract class Lock extends Object {


   /**
    * Constructor
    *
   public function __construct($path, $label = APPLICATION_NAME) {
      if (extension_loaded('sysvsem')) {
         $this->implementation = null;   // SystemFiveLock
      }
      else {
         $this->implementation = null;   // FileLock
      }
   }
   */


   /**
    * Ob dieses Lock gültig (valid) ist.
    *
    * @return boolean
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
    * Transformiert einen Schlüssel in eine numerische ID.
    *
    * @param  string $key - Schlüssel
    *
    * @return int - ID
    */
   protected function getKeyId($key) {
      return hexDec(subStr(md5($key), 0, 7)) + strLen($key);
                                      // 7: strLen(decHex(PHP_INT_MAX)) - 1   (x86)
   }


   /**
    * Verhindert das Serialisieren von Lock-Instanzen.
    */
   final public function __sleep() {
      $ex = new IllegalStateException('You cannot serialize me: '.__CLASS__);
      Logger ::log($ex, L_ERROR, __CLASS__);
      throw $ex;
   }


   /**
    * Verhindert das Deserialisieren von Lock-Instanzen.
    */
   final public function __wakeUp() {
      throw new IllegalStateException('You cannot unserialize me: '.__CLASS__);
   }
}