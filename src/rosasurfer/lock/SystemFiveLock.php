<?php
namespace rosasurfer\lock;

use rosasurfer\debug\ErrorHandler;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\PHPError;
use rosasurfer\exception\RuntimeException;

use rosasurfer\log\Logger;

use rosasurfer\util\System;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;


/**
 * SystemFiveLock
 *
 * A token representing a lock on a System-V shared memory segment.  Some platforms do not support
 * System-V shared memory (ie. Windows).
 */
class SystemFiveLock extends BaseLock {


   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice;

   private static /*Resource[]*/ $hSemaphores;     // Semaphore-Handles

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
      if (!is_string($key))                throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (isSet(self::$hSemaphores[$key])) throw new RuntimeException('Dead-lock detected: already holding a lock for key "'.$key.'"');
      self::$hSemaphores[$key] = false;

      $loglevel        = Logger::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      $integer = $this->keyToId($key);

      $i        = 0;
      $trials   = 5;    // max. Anzahl akzeptabler Fehler, eine weitere Exception wird weitergereicht
      $messages = null;
      do {
         try {
            // TODO: bei hoher Last können sem_get() oder sem_acquire() scheitern
            $hSemaphore = sem_get($integer, 1, 0666);   // Semaphore-Handle holen
            sem_acquire($hSemaphore);
            break;
         }
         catch (PHPError $ex) {
            // TODO: Quellcode umschreiben (ext/sysvsem/sysvsem.c) und Fehler lokalisieren (vermutlich wird ein File-Limit überschritten)
            $message = $ex->getMessage();
            $hexId   = decHex($integer);
            if (++$i < $trials && ($message == 'sem_get(): failed for key 0x'.$hexId.': Invalid argument'
                                || $message == 'sem_get(): failed for key 0x'.$hexId.': Identifier removed'
                                || $message == 'sem_get(): failed acquiring SYSVSEM_SETVAL for key 0x'.$hexId.': Invalid argument'
                                || $message == 'sem_get(): failed acquiring SYSVSEM_SETVAL for key 0x'.$hexId.': Identifier removed'
                                || $message == 'sem_get(): failed releasing SYSVSEM_SETVAL for key 0x'.$hexId.': Invalid argument'
                                || $message == 'sem_get(): failed releasing SYSVSEM_SETVAL for key 0x'.$hexId.': Identifier removed'
                                || $message == 'sem_acquire(): failed to acquire key 0x'.$hexId.': Invalid argument'
                                || $message == 'sem_acquire(): failed to acquire key 0x'.$hexId.': Identifier removed')) {
               self::$logDebug && Logger::log($message.', trying again ... ('.($i+1).')', null, L_DEBUG, __CLASS__);
               $messages[] = $message;
               uSleep(200000); // 200 msec. warten
               continue;
            }
            // Endlosschleife verhindern
            throw new RuntimeException("Giving up to get lock for key \"$key\" after $i trials".($messages ? ", former errors:\n".join("\n", $messages):null), null, $ex);
         }
      }
      while (true);

      $this->key               = $key;
      self::$hSemaphores[$key] = $hSemaphore;
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
         ErrorHandler::handleDestructorException($ex);
         throw $ex;
      }
   }


   /**
    * Ob dieses Lock gültig (valid) ist.
    *
    * @return bool
    */
   public function isValid() {
      if (isSet(self::$hSemaphores[$this->key]))
         return is_resource(self::$hSemaphores[$this->key]);

      return false;
   }


   /**
    * Wenn dieses Lock gültig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
    * markiert es als ungültig (invalid).  Wenn das Lock bereits ungültig (invalid) ist, hat der Aufruf
    * keinen Effekt.
    */
   public function release() {
      if ($this->isValid()) {
         if (!sem_remove(self::$hSemaphores[$this->key])) throw new RuntimeException('Cannot remove semaphore for key "'.$this->key.'"');
         unset(self::$hSemaphores[$this->key]);
      }
   }
}
