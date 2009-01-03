<?
/**
 * SystemFiveLock
 *
 * A token representing a lock on a System-V shared memory segment.  Some platforms do not support
 * System-V shared memory (ie. Windows).
 */
final class SystemFiveLock extends Lock {


   public static /*array*/  $locks;

   private        /*string*/ $key;
   private        /*int*/    $keyId;


   /**
    * Constructor
    *
    * Erzeugt für den angegebenen Schlüssel eine neue Lock-Instanz.  Um über Prozess-/Threadgrenzen
    * hinweg dieselbe Instanz ansprechen zu können, ist eine fest definierter, jedoch trotzdem eindeutiger
    * Schlüssel notwendig.  Es empfiehlt sich die Verwendung von Dateiname+Zeilen-Nr. der Code-Zeile,
    * an der das Lock erzeugt wird.
    *
    * @param  string $key - eindeutiger Schlüssel der Instanz
    *
    * @throws RuntimeException - wenn im aktuellen Prozess oder Thread bereits eine Lock-Instanz unter
    *                            demselben Schlüssel existiert
    */
   public function __construct($key) /*throws RuntimeException*/ {
      if (!is_string($key))          throw new IllegalTypeException('Illegal type of argument $key: '.getType($key));
      if (isSet(self::$locks[$key])) throw new RuntimeException('Dead-lock detected for non-unique key "'.$key.'"');

      $this->key   = $key;
      $this->keyId = $this->getKeyId($key);

      self::$locks[$key] = sem_get($this->keyId);
      sem_acquire(self::$locks[$key]);

      // Obacht geben, daß Lock auch nach abgebrochenem Script entfernt wird

      self::$locks = self::$locks[$key];
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
    * Ob dieses Lock gültig (valid) ist.
    *
    * @return boolean
    */
   public function isValid() {
      //return is_resource($this->fileHandle);
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
         //fClose($this->fileHandle);
      }
   }


}
?>
