<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\lock;

use rosasurfer\ministruts\config\ConfigInterface;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\RuntimeException;


/**
 * Lock
 *
 * Delegate to a specific lock implementation.
 */
class Lock extends BaseLock {


    /** @var string[] - keys of all currently aquired locks */
    private static $lockedKeys;

    /** @var ?BaseLock - lock implementation */
    private $impl = null;

    /** @var string - key of the locking implementation */
    private $key;


    /**
     * Constructor
     *
     * @param  string $key - key a new lock should be aquired for
     */
    public function __construct($key) {
        Assert::string($key);
        if (isset(self::$lockedKeys[$key])) throw new RuntimeException('Dead-lock detected: already holding a lock for key "'.$key.'"');

        self::$lockedKeys[$key] = $this->key = $key;

        // prefer SysVLock...
        if (false && extension_loaded('sysvsem')) {     // @phpstan-ignore  booleanAnd.leftAlwaysFalse (keep for testing)
            $this->impl = new SystemFiveLock($key);
        }
        else {
            /** @var ConfigInterface $config */
            $config = $this->di('config');

            // fall-back to FileLock...
            $directory = $config->get('app.dir.tmp', null);
            if (!$directory) $directory = ini_get('sys_temp_dir');
            if (!$directory) $directory = sys_get_temp_dir();

            $this->impl = new FileLock($directory.'/lock_'.md5($key));
        }
    }


    /**
     * Whether the lock is aquired.
     *
     * @return bool
     */
    public function isAquired() {
        if ($this->impl) {
            return $this->impl->isAquired();
        }
        return false;
    }


    /**
     * If called on an aquired lock the lock is released.
     * If called on an already released lock the call does nothing.
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
     * Release occupied resources.
     */
    public function __destruct() {
        try {
            $this->release();
        }
        catch (\Throwable $ex) {
            throw ErrorHandler::handleDestructorException($ex);
        }
    }
}
