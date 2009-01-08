<?
/**
 * SystemFiveLock
 *
 * A token representing a lock on a System-V shared memory segment.  Some platforms do not support
 * System-V shared memory (ie. Windows).
 */
final class SystemFiveLock extends BaseLock {


   private static /*Resource[]*/ $semIds;

   private /*string*/ $key;


   /**
    * Constructor
    *
    * Erzeugt für den angegebenen Schlüssel eine neue Lock-Instanz.  Um über Prozess-/Threadgrenzen
    * hinweg dieselbe Instanz ansprechen zu können, ist ein fest definierter, jedoch trotzdem eindeutiger
    * Schlüssel notwendig.  Es empfiehlt sich die Verwendung von Dateiname + Zeilen-Nr. der Code-Zeile,
    * an der das Lock erzeugt wird.
    *
    * Example:
    * --------
    *
    *  $lock = new SystemFiveLock(__FILE__.'#'.__LINE__);
    *
    *
    * @param  string $key - eindeutiger Schlüssel der Instanz
    *
    * @throws RuntimeException - wenn im aktuellen Prozess oder Thread bereits eine Lock-Instanz unter
    *                            demselben Schlüssel existiert
    */
   public function __construct($key) /*throws RuntimeException*/ {
      if (!is_string($key))           throw new IllegalTypeException('Illegal type of argument $key: '.getType($key));
      if (isSet(self::$semIds[$key])) throw new RuntimeException('Dead-lock detected: already holding a lock for key "'.$key.'"');

      $decId = $this->getKeyId($key);
      $hexId = decHex($decId);

      do {
         $semId = sem_get($decId, 1, 0666);
         try {
            // hier kann ein anderer (das Lock haltender) Prozeß den Semaphore schon wieder gelöscht haben
            sem_acquire($semId);
            break;
         }
         catch (PHPErrorException $ex) {
            if ($ex->getMessage() == 'sem_acquire(): failed to acquire key 0x'.$hexId.': Identifier removed')
               continue;
            throw $ex;
         }
      }
      while (true);

      $this->key = $key;
      self::$semIds[$key] = $semId;
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
      return isSet(self::$semIds[$this->key]);
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
         if (!sem_remove(self::$semIds[$this->key]))
            throw new RuntimeException('Cannot remove semaphore for key "'.$this->key.'"');

         unset(self::$semIds[$this->key]);
      }
   }
}
?>
