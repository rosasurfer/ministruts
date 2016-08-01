<?php
use rosasurfer\ministruts\exception\FileNotFoundException;
use rosasurfer\ministruts\exception\IllegalTypeException;
use rosasurfer\ministruts\exception\RuntimeException;


/**
 * Lock
 *
 * Delegate auf eine konkrete Lock-Implementierung.  Aus Sicht des User-Codes ist nur die Funktionalität
 * interessant, nicht wie das Lock konkret implementiert wird.
 *
 * Ein Lock stellt die Sperre eines oder mehrerer Code-Abschnitte gegen gleichzeitigen Zugriff durch
 * konkurrierende PHP-Threads oder -Prozesse dar.
 *
 * Eine Lock-Instanz ist nach Erzeugung gesperrt und die Sperre immer gültig.  Besitzt zum Zeitpunkt
 * des Aufrufs ein anderer Prozeß die gewünschte Sperre des jeweiligen Code-Abschnitts, blockiert der
 * aufrufende Prozeß, bis er die Sperre erlangt.  Die Sperre bleibt gültig, bis sie durch Aufruf der
 * release()-Methode, durch Zerstörung der Lock-Instanz oder durch Beendigung des Scriptes (je nachdem
 * welches der Ereignisse zuerst eintritt) freigegeben wird.  Die Gültigkeit einer Sperre kann durch
 * Aufruf der isValid()-Methode überprüft werden.  Nach Freigabe der Sperre hat die Lock-Instanz keinerlei
 * weitere Funktionen mehr.
 *
 * Im Lebenszyklus einer Lock-Instanz kann sich nur die Gültigkeit der Sperre ändern, alle anderen Aspekte
 * der Instanz sind unveränderlich.
 */
final class Lock extends BaseLock {


   // alle Schlüssel der im Moment gehaltenen Locks
   private static /*string[]*/ $lockedKeys;

   private /*Lock*/   $impl;  // aktuelle Implementierung der Instanz
   private /*string*/ $key;   // aktueller Schlüssel der Instanz


   /**
    * Constructor
    *
    * @param  string $key - Schlüssel, auf dem ein Lock gehalten werden soll
    */
   public function __construct($key = null) {
      if (func_num_args()) {
         if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      }
      else {
         // kein Schlüssel angegeben, __FILE__ + __LINE__ des aufrufenden Codes verwenden
         $trace = debug_backtrace();
         $key   = $trace[0]['file'].'#'.$trace[0]['line'];
      }
      if (isSet(self::$lockedKeys[$key])) throw new RuntimeException('Dead-lock detected: already holding a lock for key "'.$key.'"');
      self::$lockedKeys[$key] = true;

      $this->key = $key;

      // konkretes Lock erzeugen, vorzugsweise SystemFiveLock
      if (extension_loaded('sysvsem')) {
         $this->impl = new SystemFiveLock($key);
      }
      else {
         // alternativ FileLock verwenden ...
         $filename = ini_get('session.save_path').DIRECTORY_SEPARATOR.'lock_'.md5($key);
         if (!is_file($filename) && !touch($filename)) throw new RuntimeException('Cannot create lock file "'.$filename.'"');

         if (!is_file($filename)) throw new FileNotFoundException('Cannot find lock file "'.$filename.'"');

         $this->impl = new FileLock($filename);
      }
   }


   /**
    * Destructor
    *
    * Sorgt bei Zerstörung der Instanz dafür, daß eine evt. erzeugte Lockdatei wieder gelöscht wird.
    */
   public function __destruct() {
      // Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
      // @see http://php.net/manual/en/language.oop5.decon.php
      try {
         $this->release();
      }
      catch (\Exception $ex) {
         System::handleDestructorException($ex);
         throw $ex;
      }
   }


   /**
    * Ob dieses Lock gültig (valid) ist.
    *
    * @return bool
    */
   public function isValid() {
      if ($this->impl)
         return $this->impl->isValid();

      return false;
   }


   /**
    * Wenn dieses Lock gültig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
    * markiert es als ungültig (invalid).  Wenn das Lock bereits ungültig (invalid) ist, hat der Aufruf
    * keinen Effekt.
    *
    * @see Lock::isValid()
    */
   public function release() {
      if ($this->impl) {
         $this->impl->release(true);            // true: Lockfile eines evt. FileLocks löschen lassen
         unset(self::$lockedKeys[$this->key]);
      }
   }
}
