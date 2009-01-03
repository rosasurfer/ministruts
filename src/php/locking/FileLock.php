<?
/**
 * FileLock
 *
 * A token representing a lock on a file.
 *
 * A file-lock object is created each time a lock is acquired on a file.
 *
 * A file-lock object is initially valid.  It remains valid until the lock is released by invoking the
 * release() method or by the termination of the current PHP process, whichever comes first. The validity
 * of a lock may be tested by invoking its isValid() method.  A file lock is either exclusive or shared.
 * A shared lock prevents other concurrently-running programs from acquiring an overlapping exclusive
 * lock, but does allow them to acquire overlapping shared locks.  An exclusive lock prevents other programs
 * from acquiring an overlapping lock of either type.  Once it is released, a lock has no further effect
 * on the locks that may be acquired by other programs.  Whether a lock is exclusive or shared may be
 * determined by invoking its isShared() method.  Some platforms do not support shared locks, in which
 * case a request for a shared lock is automatically converted into a request for an exclusive lock.
 */
final class FileLock extends Lock {


   private /*Resource*/ $fileHandle;
   private /*bool*/     $shared;


   /**
    * Constructor
    *
    * Erzeugt ein neues FileLock für die angegebene Datei.
    *
    * @param string  $file   - Datei, auf der das Lock gehalten werden soll (muß existieren)
    * @param boolean $shared - TRUE, um ein shared Lock oder FALSE, um ein exclusive Lock zu setzen
    *                          (default: FALSE = exklusives Lock)
    */
   public function __construct($file, $shared = false) {
      if (!is_string($file)) throw new IllegalTypeException('Illegal type of argument $file: '.getType($file));
      if (!is_bool($shared)) throw new IllegalTypeException('Illegal type of argument $shared: '.getType($shared));

      $this->fileHandle = fOpen($file, 'r');
      $this->shared     = $shared;

      $mode = $shared ? LOCK_SH : LOCK_EX;

      if (!fLock($this->fileHandle, $mode))
         throw new RuntimeException('Can not aquire '.($shared ? 'shared':'exclusive').' file lock for "'.$file.'"');
   }


   /**
    * Destructor
    *
    * Sorgt bei Zerstörung der Instanz dafür, daß ein evt. noch gehaltenes Lock freigegeben wird.
    */
   public function __destruct() {
      $this->release();
   }


   /**
    * Ob dieses Lock ein shared oder ein exclusive Lock ist.
    *
    * @return boolean
    */
   public function isShared() {
      return $this->shared;
   }


   /**
    * Ob dieses Lock gültig (valid) ist.
    *
    * @return boolean
    */
   public function isValid() {
      return is_resource($this->fileHandle);
   }


   /**
    * Wenn dieses Lock gültig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
    * markiert es als ungültig (invalid).  Wenn das Lock bereits ungültig (invalid) ist, hat der Aufruf
    * keinen Effekt.
    *
    * @see FileLock::isValid()
    */
   public function release() {
      if ($this->isValid()) {
         fClose($this->fileHandle);    // see docs: The lock is released also by fClose()...
      }
   }
}