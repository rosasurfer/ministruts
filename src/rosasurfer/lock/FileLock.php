<?php
namespace rosasurfer\lock;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;


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
final class FileLock extends BaseLock {

   private static /*resource[]*/ $hFiles;

   private /*string*/ $filename;
   private /*bool*/   $shared;


   /**
    * Constructor
    *
    * Erzeugt ein neues FileLock für die angegebene Datei.
    *
    * @param  string $filename - Name der Datei, auf der das Lock gehalten werden soll (muß existieren)
    * @param  bool   $shared   - TRUE, um ein shared Lock oder FALSE, um ein exclusive Lock zu setzen
    *                            (default: FALSE = exklusives Lock)
    */
   public function __construct($filename, $shared = false) {

      // TODO: Ein das Lock haltender Prozeß kann die Datei bis zum Aufruf von fLock() wieder gelöscht haben.

      // TODO: 2016-06-17: Win7/NTFS: Auf einer gesperrten Datei (Handle 1 ) funktionieren die Dateifunktionen
      //       mit einem anderen Handle (2) nicht mehr (unter Linux schon). Mit dem zum Sperren verwendeten Handle
      //       funktionieren sie.

      if (!is_string($filename))           throw new IllegalTypeException('Illegal type of parameter $filename: '.getType($filename));
      if (!is_bool($shared))               throw new IllegalTypeException('Illegal type of parameter $shared: '.getType($shared));

      if (isSet(self::$hFiles[$filename])) throw new RuntimeException('Dead-lock detected: already holding a lock for file "'.$filename.'"');
      self::$hFiles[$filename] = false;      // Schlägt der Constructor fehl, verhindert der gesetzte Eintrag ein
                                             // Dead-lock bei eventuellem Re-Entry.
      $this->filename = $filename;
      $this->shared   = $shared;

      self::$hFiles[$filename] = fOpen($filename, 'r');
      $mode = $shared ? LOCK_SH : LOCK_EX;

      if (!fLock(self::$hFiles[$filename], $mode)) throw new RuntimeException('Can not aquire '.($shared ? 'shared':'exclusive').' file lock for "'.$filename.'"');
   }


   /**
    * Destructor
    *
    * Sorgt bei Zerstörung der Instanz dafür, daß ein evt. noch gehaltenes Lock freigegeben wird.
    */
   public function __destruct() {
      // Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
      // @see http://php.net/manual/en/language.oop5.decon.php
      try {
         $this->release();
      }
      catch (\Exception $ex) {
         \System::handleDestructorException($ex);
         throw $ex;
      }
   }


   /**
    * Ob dieses Lock ein shared oder ein exclusive Lock ist.
    *
    * @return bool
    */
   public function isShared() {
      return $this->shared;
   }


   /**
    * Ob dieses Lock gültig (valid) ist.
    *
    * @return bool
    */
   public function isValid() {
      if (isSet(self::$hFiles[$this->filename]))
         return is_resource(self::$hFiles[$this->filename]);

      return false;
   }


   /**
    * Wenn dieses Lock gültig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
    * markiert es als ungültig (invalid).  Wenn das Lock bereits ungültig (invalid) ist, hat der Aufruf
    * keinen Effekt.
    *
    * @param  bool $deleteFile - ob das verwendete Lockfile beim Freigeben des Locks gelöscht werden soll (default: FALSE)
    */
   public function release($deleteFile = false) {
      if (!is_bool($deleteFile)) throw new IllegalTypeException('Illegal type of parameter $deleteFile: '.getType($deleteFile));

      if ($this->isValid()) {
         fClose(self::$hFiles[$this->filename]);      // see docs: The lock is released also by fClose()...
         if ($deleteFile)
            @unlink($this->filename);                 // @: theoretisch kann hier schon ein anderer Prozeß das Lock halten
         unset(self::$hFiles[$this->filename]);
      }
   }
}
