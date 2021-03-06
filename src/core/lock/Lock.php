<?php
namespace rosasurfer\core\lock;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\ErrorHandler;
use rosasurfer\core\exception\RuntimeException;


/**
 * Lock
 *
 * Delegate auf eine konkrete Lock-Implementierung.  Aus Sicht des User-Codes ist nur die Funktionalitaet
 * interessant, nicht wie das Lock konkret implementiert wird.
 */
class Lock extends BaseLock {


    /** @var string[] - Schluessel der im Moment gehaltenen Locks */
    private static $lockedKeys;

    /** @var BaseLock - aktuelle Implementierung der Instanz */
    private $impl;

    /** @var string - aktueller Schluessel der Instanz */
    private $key;


    /**
     * Constructor
     *
     * @param  string $key - Schluessel, auf dem ein Lock gehalten werden soll
     */
    public function __construct($key) {
        Assert::string($key);
        if (isset(self::$lockedKeys[$key])) throw new RuntimeException('Dead-lock detected: already holding a lock for key "'.$key.'"');

        self::$lockedKeys[$key] = $this->key = $key;

        // vorzugsweise SysVLock verwenden...
        if (false && extension_loaded('sysvsem')) {
            $this->impl = new SystemFiveLock($key);
        }
        else {
            /** @var ConfigInterface $config */
            $config = $this->di('config');

            // alternativ FileLock verwenden...
            $directory = $config->get('app.dir.tmp', null);
            !$directory && $directory = ini_get('sys_temp_dir');
            !$directory && $directory = sys_get_temp_dir();

            $this->impl = new FileLock($directory.'/lock_'.md5($key));
        }
    }


    /**
     * Whether the lock is aquired.
     *
     * @return bool
     */
    public function isAquired() {
        if ($this->impl)
            return $this->impl->isAquired();
        return false;
    }


    /**
     * If called on an aquired lock the lock is released. If called on an already released lock the call does nothing.
     */
    public function release() {
        if ($this->impl) {
            $this->impl->release();
            unset(self::$lockedKeys[$this->key]);
        }
    }


    /**
     * Destructor
     *
     * Release the resources occupied by the instance.
     */
    public function __destruct() {
        try {
            $this->release();
        }
        catch (\Throwable $ex) { throw ErrorHandler::handleDestructorException($ex); }
        catch (\Exception $ex) { throw ErrorHandler::handleDestructorException($ex); }
    }
}
