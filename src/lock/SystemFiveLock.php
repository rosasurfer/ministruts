<?php
namespace rosasurfer\lock;

use rosasurfer\debug\ErrorHandler;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;
use rosasurfer\exception\error\PHPError;

use rosasurfer\log\Logger;

use function rosasurfer\strStartsWith;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\NL;


/**
 * SystemFiveLock
 *
 * A token representing a lock on a System-V shared memory segment.  Some platforms do not support
 * System-V shared memory (ie. Windows).
 */
class SystemFiveLock extends BaseLock {

    /** @var bool */
    private static $logDebug;

    /** @var bool */
    private static $logInfo;

    /** @var bool */
    private static $logNotice;

    /** @var resource[] - semaphore handles */
    private static $hSemaphores;

    private /*string*/ $key;


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
     *
     *  $lock = new SystemFiveLock(__FILE__.'#'.__LINE__);
     *
     *
     * @param  string $key - eindeutiger Schluessel der Instanz
     *
     * @throws RuntimeException wenn im aktuellen Prozess oder Thread bereits eine Lock-Instanz unter demselben Schluessel
     *                          existiert
     */
    public function __construct($key) {
        if (!is_string($key))                throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
        if (isSet(self::$hSemaphores[$key])) throw new RuntimeException('Dead-lock detected: already holding a lock for key "'.$key.'"');
        self::$hSemaphores[$key] = false;

        $loglevel        = Logger::getLogLevel(__CLASS__);
        self::$logDebug  = ($loglevel <= L_DEBUG );
        self::$logInfo   = ($loglevel <= L_INFO  );
        self::$logNotice = ($loglevel <= L_NOTICE);

        $integer = $this->keyToId($key);

        $i      = 0;
        $trials = 5;        // max. Anzahl akzeptabler Fehler, eine weitere Exception wird weitergereicht
        $hSemaphore = $messages = null;
        do {
            try {
                // TODO: bei hoher Last koennen sem_get() oder sem_acquire() scheitern
                $hSemaphore = sem_get($integer, 1, 0666);   // Semaphore-Handle holen
                sem_acquire($hSemaphore);
                break;
            }
            catch (PHPError $ex) {
                // TODO: Quellcode umschreiben (ext/sysvsem/sysvsem.c) und Fehler lokalisieren (vermutlich wird ein File-Limit ueberschritten)
                $message  = $ex->getMessage();
                $hexId    = decHex($integer);
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
                    uSleep(200000); // 200 msec. warten
                    continue;
                }
                // Endlosschleife verhindern
                throw $ex->addMessage('Giving up to get lock for key "$key" after '.$i.' trials'.($messages ? ', former errors:'.NL.join(NL, $messages) : ''));
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
        if (isSet(self::$hSemaphores[$this->key]))
            return is_resource(self::$hSemaphores[$this->key]);

        return false;
    }


    /**
     * Wenn dieses Lock gueltig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
     * markiert es als ungueltig (invalid).  Wenn das Lock bereits ungueltig (invalid) ist, hat der Aufruf
     * keinen Effekt.
     */
    public function release() {
        if ($this->isValid()) {
            if (!sem_remove(self::$hSemaphores[$this->key])) throw new RuntimeException('Cannot remove semaphore for key "'.$this->key.'"');
            unset(self::$hSemaphores[$this->key]);
        }
    }
}
