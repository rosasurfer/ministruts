<?
/**
 * Lock
 *
 * Delegate auf eine konkrete Lock-Implementierung.  Aus Sicht des User-Codes ist nur die Funktionalität
 * interessant, nicht jedoch, wie das Lock konkret implementiert wird.
 *
 * Ein Lock stellt die Sperre eines oder mehrerer Code-Abschnitte gegen gleichzeitigen Zugriff durch
 * konkurrierende PHP-Threads oder -Prozesse dar.
 *
 * Eine Lock-Instanz ist nach Erzeugung gesperrt und die Sperre immer gültig.  Besitzt zum Zeitpunkt
 * des Aufrufs ein anderer Prozeß die gewünschte Sperre des jeweiligen Code-Abschnitts, blockiert
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


   private /*Lock*/   $impl;
   private /*string*/ $lockFile;


   /**
    * Constructor
    *
    * @param string $mutex - Schlüssel, auf dem ein Lock gehalten werden soll
    */
   public function __construct($mutex = null) {
      if (func_num_args()) {
         if (!is_string($mutex)) throw new IllegalTypeException('Illegal type of argument $mutex: '.getType($mutex));
      }
      else {
         // kein Mutex angegeben, __FILE__ & __LINE__ des aufrufenden Codes verwenden
         $trace = debug_backtrace();
         $mutex = $trace[0]['file'].'#'.$trace[0]['line'];
      }


      // 1.) vorzugsweise SystemFiveLock verwenden
      if (false && extension_loaded('sysvsem')) {
         $this->impl = new SystemFiveLock($mutex);
      }

      // 2.) alternativ FileLock verwenden
      else {
         // Namen der Lock-Datei ermitteln
         $name = md5($mutex);
         $file = ini_get('session.save_path').DIRECTORY_SEPARATOR.'lock_'.$name;

         // Lock-Datei ggf. erzeugen
         if (!is_file($file) && !touch($file))
            throw new RuntimeException('Cannot create lock file "'.$file.'"');

         $this->impl = new FileLock($this->lockFile = $file);
      }
   }


   /**
    * Destructor
    *
    * Sorgt bei Zerstörung der Instanz dafür, daß eine evt. erzeugte Lockdatei wieder gelöscht wird.
    */
   public function __destruct() {
      $this->impl = null;

      if ($this->lockFile) {
         if (!unlink($this->lockFile))
            throw new RuntimeException('Cannot delete lock file "'.$this->lockFile.'"');
         $this->lockFile = null;
      }
   }


   /**
    * Ob dieses Lock gültig (valid) ist.
    *
    * @return boolean
    */
   public function isValid() {
      return $this->impl->isValid();
   }


   /**
    * Wenn dieses Lock gültig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
    * markiert es als ungültig (invalid).  Wenn das Lock bereits ungültig (invalid) ist, hat der Aufruf
    * keinen Effekt.
    *
    * @see Lock::isValid()
    */
   public function release() {
      return $this->impl->release();
   }
}
