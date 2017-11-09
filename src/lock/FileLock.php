<?php
namespace rosasurfer\lock;

use rosasurfer\debug\ErrorHandler;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;


/**
 * An object representing an aquired lock on a file.
 *
 * After aquiring the lock the initial lock status is valid. It remains valid until the lock is released by invoking the
 * release() method or by the termination of the current PHP process, whichever comes first. The validity of the lock can
 * be tested by invoking its isValid() method.
 *
 * A file lock is either exclusive or shared. A shared lock prevents other concurrently-running processes from acquiring an
 * overlapping exclusive lock, but does allow them to acquire overlapping shared locks. An exclusive lock prevents other
 * processes from acquiring an overlapping lock of either type. Once a lock is released, it has no further effect on the
 * locks that may be acquired by other processes.
 *
 * Whether a lock is exclusive or shared can be determined by invoking its isShared() method. Some platforms do not support
 * shared locks, in which case a request for a shared lock is automatically converted into a request for an exclusive lock.
 */
final class FileLock extends BaseLock {


    /** @var resource[] */
    private static $hFiles = [];

    /** @var string */
    private $filename;

    /** @var bool */
    private $shared;


    /**
     * Constructor
     *
     * Erzeugt ein neues FileLock fuer die angegebene Datei.
     *
     * @param  string $filename          - Name der Datei, auf der das Lock gehalten werden soll (muss existieren)
     * @param  bool   $shared [optional] - TRUE, um ein shared Lock oder FALSE, um ein exclusive Lock zu setzen
     *                                     (default: FALSE = exklusives Lock)
     */
    public function __construct($filename, $shared = false) {

        // TODO: Ein das Lock haltender Prozess kann die Datei bis zum Aufruf von fLock() wieder geloescht haben.

        // TODO: 2016-06-17: Win7/NTFS: Auf einer gesperrten Datei (Handle 1 ) funktionieren die Dateifunktionen
        //       mit einem anderen Handle (2) nicht mehr (unter Linux schon). Mit dem zum Sperren verwendeten Handle
        //       funktionieren sie.

        if (!is_string($filename))                throw new IllegalTypeException('Illegal type of parameter $filename: '.getType($filename));
        if (!is_bool($shared))                    throw new IllegalTypeException('Illegal type of parameter $shared: '.getType($shared));

        if (key_exists($filename, self::$hFiles)) throw new RuntimeException('Dead-lock detected: already holding a lock for file "'.$filename.'"');
        self::$hFiles[$filename] = null;            // Schlaegt der Constructor fehl, verhindert der gesetzte Eintrag ein
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
     * Ob dieses Lock ein shared oder ein exclusive Lock ist.
     *
     * @return bool
     */
    public function isShared() {
        return $this->shared;
    }


    /**
     * Ob dieses Lock gueltig (valid) ist.
     *
     * @return bool
     */
    public function isValid() {
        if (isSet(self::$hFiles[$this->filename]))
            return is_resource(self::$hFiles[$this->filename]);
        return false;
    }


    /**
     * Wenn dieses Lock gueltig (valid) ist, gibt der Aufruf dieser Methode das gehaltene Lock frei und
     * markiert es als ungueltig (invalid).  Wenn das Lock bereits ungueltig (invalid) ist, hat der Aufruf
     * keinen Effekt.
     */
    public function release($deleteFile = false) {
        if ($this->isValid()) {
            fClose(self::$hFiles[$this->filename]);     // see docs: The lock is released also by fClose()...
            unset(self::$hFiles[$this->filename]);
        }
    }
}
