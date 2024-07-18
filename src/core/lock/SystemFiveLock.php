<?php
namespace rosasurfer\core\lock;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\error\ErrorHandler;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\log\Logger;

use function rosasurfer\strStartsWith;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\NL;


/**
 * System-V lock
 *
 * A lock using a System-V shared memory segment as a mutex (not supported on Windows).
 */
class SystemFiveLock extends BaseLock {

    /** @var bool */
    private static $logDebug;

    /** @var bool */
    private static $logInfo;

    /** @var bool */
    private static $logNotice;

    /** @var resource[] - semaphore handles */
    private static $hSemaphores = [];

    /** @var string */
    private $key;


    /**
     * Constructor
     *
     * Erzeugt fuer den angegebenen Schluessel eine neue Lock-Instanz.  Um ueber Prozess-/Threadgrenzen
     * hinweg dieselbe Instanz ansprechen zu koennen, ist ein fest definierter, jedoch trotzdem eindeutiger
     * Schluessel notwendig.  Es empfiehlt sich die Verwendung von Dateiname + Zeilen-Nr. der Code-Zeile,
     * an der das Lock erzeugt wird.
     *
     * Example:
     * --------
     *  $lock = new SystemFiveLock(__FILE__.'#'.__LINE__);
     *
     * @param  string $key - eindeutiger Schluessel der Instanz
     *
     * @throws RuntimeException wenn im aktuellen Prozess oder Thread bereits eine Lock-Instanz unter demselben Schluessel
     *                          existiert
     */
    public function __construct($key) {
        Assert::string($key);
        if (\key_exists($key, self::$hSemaphores)) throw new RuntimeException('Dead-lock detected: already holding a lock for key "'.$key.'"');
        self::$hSemaphores[$key] = null;

        $loglevel        = Logger::getLogLevel(__CLASS__);
        self::$logDebug  = ($loglevel <= L_DEBUG );
        self::$logInfo   = ($loglevel <= L_INFO  );
        self::$logNotice = ($loglevel <= L_NOTICE);

        // use fTok() instead
        $integer = $this->keyToId($key);

        $i      = 0;
        $trials = 5;        // max. Anzahl akzeptabler Fehler, eine weitere Exception wird weitergereicht
        $hSemaphore = $messages = null;
        do {
            $ex = null;
            try {
                $hSemaphore = sem_get($integer, 1, 0666);       // Semaphore-Handle holen
                if (!is_resource($hSemaphore)) throw new RuntimeException('cannot get semaphore handle for key '.$integer);
                sem_acquire($hSemaphore);
                break;                                          // TODO: bei Last koennen sem_get() oder sem_acquire() scheitern
            }
            catch (IRosasurferException $ex) {}
            catch (\Throwable           $ex) { $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex); }

            if ($ex) {
                // TODO: Quellcode umschreiben (ext/sysvsem/sysvsem.c) und Fehler lokalisieren (vermutlich wird ein File-Limit ueberschritten)
                $message  = $ex->getMessage();
                $hexId    = dechex($integer);
                $prefixes = [
                    'sem_get(): failed for key 0x'.$hexId.': Invalid argument',
                    'sem_get(): failed for key 0x'.$hexId.': Identifier removed',
                    'sem_get(): failed acquiring SYSVSEM_SETVAL for key 0x'.$hexId.': Invalid argument',
                    'sem_get(): failed acquiring SYSVSEM_SETVAL for key 0x'.$hexId.': Identifier removed',
                    'sem_get(): failed releasing SYSVSEM_SETVAL for key 0x'.$hexId.': Invalid argument',
                    'sem_get(): failed releasing SYSVSEM_SETVAL for key 0x'.$hexId.': Identifier removed',
                    'sem_acquire(): failed to acquire key 0x'.$hexId.': Invalid argument',
                    'sem_acquire(): failed to acquire key 0x'.$hexId.': Identifier removed',
                ];
                if (++$i < $trials && strStartsWith($message, $prefixes)) {
                    self::$logDebug && Logger::log($message.', trying again ... ('.($i+1).')', L_DEBUG);
                    $messages[] = $message;
                    usleep(200000); // 200 msec. warten
                    continue;
                }
                // Endlosschleife verhindern
                throw $ex->appendMessage('Giving up to get lock for key "'.$key.'" after '.$i.' trials'.($messages ? ', former errors:'.NL.join(NL, $messages) : ''));
            }
        }
        while (true);

        $this->key               = $key;
        self::$hSemaphores[$key] = $hSemaphore;
    }


    /**
     * Destructor
     *
     * Sorgt bei Zerstoerung der Instanz dafuer, dass ein evt. noch gehaltenes Lock freigegeben wird.
     */
    public function __destruct() {
        try {
            $this->release();
        }
        catch (\Throwable $ex) { throw ErrorHandler::handleDestructorException($ex); }
    }


    /**
     * Whether the lock is aquired.
     *
     * @return bool
     */
    public function isAquired() {
        if (isset(self::$hSemaphores[$this->key]))
            return is_resource(self::$hSemaphores[$this->key]);
        return false;
    }


    /**
     * If called on an aquired lock the lock is released. If called on an already released lock the call does nothing.
     */
    public function release() {
        if ($this->isAquired()) {
            if (!sem_remove(self::$hSemaphores[$this->key])) throw new RuntimeException('Cannot remove semaphore for key "'.$this->key.'"');
            unset(self::$hSemaphores[$this->key]);
        }
    }


    /**
     * TODO: Replace with fTok()
     *
     * Convert a key (string) to a unique numerical value (int).
     *
     * @param  string $key
     *
     * @return int - numerical value
     */
    private function keyToId($key) {
        return (int) hexdec(substr(md5($key), 0, 7)) + strlen($key);
    }                                         // 7: strlen(dechex(PHP_INT_MAX)) - 1 (x86)
}
