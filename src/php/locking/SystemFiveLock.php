<?
/**
 * SystemFiveLock
 *
 * A token representing a lock on a System-V shared memory segment.  Some platforms do not support
 * System-V shared memory (ie. Windows).
 */
final class SystemFiveLock extends BaseLock {


   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice;

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
      if ($key !== (string)$key)      throw new IllegalTypeException('Illegal type of argument $key: '.getType($key));
      if (isSet(self::$semIds[$key])) throw new RuntimeException('Dead-lock detected: already holding a lock for key "'.$key.'"');
      self::$semIds[$key] = false;

      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      $decId = $this->getKeyId($key);
      $hexId = decHex($decId);

      $trials = 3;   // max. Anzahl akzeptabler Fehler, eine weitere Exception wird weitergereicht
      $i = 0;
      do {
         try {
            // TODO: bei hoher Last kann sem_get()/sem_acquire() scheitern
            $semId = sem_get($decId, 1, 0666);
            sem_acquire($semId);
            break;
         }
         catch (PHPErrorException $ex) {
            // TODO: Quellcode umschreiben (ext/sysvsem/sysvsem.c) und Fehler lokalisieren (vermutlich wird ein File-Limit überschritten)
            if (++$i < $trials && ($ex->getMessage()=='sem_get(): failed for key 0x'.$hexId.': Invalid argument'
                                || $ex->getMessage()=='sem_get(): failed for key 0x'.$hexId.': Identifier removed'
                                || $ex->getMessage()=='sem_get(): failed acquiring SYSVSEM_SETVAL for key 0x'.$hexId.': Invalid argument'
                                || $ex->getMessage()=='sem_get(): failed acquiring SYSVSEM_SETVAL for key 0x'.$hexId.': Identifier removed'
                                || $ex->getMessage()=='sem_get(): failed releasing SYSVSEM_SETVAL for key 0x'.$hexId.': Invalid argument'
                                || $ex->getMessage()=='sem_get(): failed releasing SYSVSEM_SETVAL for key 0x'.$hexId.': Identifier removed'
                                || $ex->getMessage()=='sem_acquire(): failed to acquire key 0x'.$hexId.': Invalid argument'
                                || $ex->getMessage()=='sem_acquire(): failed to acquire key 0x'.$hexId.': Identifier removed')) {
               self::$logDebug && Logger ::log($ex->getMessage().', trying again ... ('.($i+1).')', L_DEBUG, __CLASS__);
               uSleep(100000); // 100 msec. warten
               continue;
            }
            // Endlosschleife verhindern
            throw new RuntimeException("Giving up to get lock after $i trials", $ex);
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
      try {
         $this->release();
      }
      catch (Exception $ex) {
         Logger ::handleException($ex, true);
         throw $ex;
      }
   }


   /**
    * Ob dieses Lock gültig (valid) ist.
    *
    * @return boolean
    */
   public function isValid() {
      if (isSet(self::$semIds[$this->key]))
         return is_resource(self::$semIds[$this->key]);

      return false;
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
