<?php
namespace rosasurfer\lock;

use rosasurfer\debug\ErrorHandler;

use rosasurfer\exception\FileNotFoundException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;


/**
 * Lock
 *
 * Delegate auf eine konkrete Lock-Implementierung.  Aus Sicht des User-Codes ist nur die Funktionalitaet
 * interessant, nicht wie das Lock konkret implementiert wird.
 *
 * Ein Lock stellt die Sperre eines oder mehrerer Code-Abschnitte gegen gleichzeitigen Zugriff durch
 * konkurrierende PHP-Threads oder -Prozesse dar.
 *
 * Eine Lock-Instanz ist nach Erzeugung gesperrt und die Sperre immer gueltig.  Besitzt zum Zeitpunkt
 * des Aufrufs ein anderer Prozess die gewuenschte Sperre des jeweiligen Code-Abschnitts, blockiert der
 * aufrufende Prozess, bis er die Sperre erlangt.  Die Sperre bleibt gueltig, bis sie durch Aufruf der
 * release()-Methode, durch Zerstoerung der Lock-Instanz oder durch Beendigung des Scriptes (je nachdem
 * welches der Ereignisse zuerst eintritt) freigegeben wird.  Die Gueltigkeit einer Sperre kann durch
 * Aufruf der isValid()-Methode ueberprueft werden.  Nach Freigabe der Sperre hat die Lock-Instanz keinerlei
 * weitere Funktionen mehr.
 *
 * Im Lebenszyklus einer Lock-Instanz kann sich nur die Gueltigkeit der Sperre aendern, alle anderen Aspekte
 * der Instanz sind unveraenderlich.
 */
final class Lock extends BaseLock {


   // alle Schluessel der im Moment gehaltenen Locks
   private static /*string[]*/ $lockedKeys;

   private /*Lock*/   $impl;  // aktuelle Implementierung der Instanz
   private /*string*/ $key;   // aktueller Schluessel der Instanz


   /**
    * Constructor
    *
    * @param  string $key - Schluessel, auf dem ein Lock gehalten werden soll
    */
   public function __construct($key = null) {
      if (func_num_args()) {
         if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      }
      else {
         // kein Schluessel angegeben, __FILE__ + __LINE__ des aufrufenden Codes verwenden
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
    * Sorgt bei Zerstoerung der Instanz dafuer, dass eine evt. erzeugte Lockdatei wieder geloescht wird.
    */
   public function __destruct() {
      // Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
      // @see http://php.net/manual/en/language.oop5.decon.php
      try {
         $this->release();
      }
      catch (\Exception $ex) {
         throw ErrorHandler::handleDestructorException($ex);
      }
   }


   /**
    * Ob dieses Lock gueltig (valid) ist.
    *
    * @return bool
    */
   public function isValid() {
      if ($this->impl)
         return $this->impl->isValid();

      return false;
   }


   /**
    * Wenn dieses Lock gueltig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
    * markiert es als ungueltig (invalid).  Wenn das Lock bereits ungueltig (invalid) ist, hat der Aufruf
    * keinen Effekt.
    *
    * @see Lock::isValid()
    */
   public function release() {
      if ($this->impl) {
         $this->impl->release(true);            // true: Lockfile eines evt. FileLocks loeschen lassen
         unset(self::$lockedKeys[$this->key]);
      }
   }
}
