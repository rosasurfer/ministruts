<?php
namespace rosasurfer\core\lock;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\error\ErrorHandler;
use rosasurfer\core\exception\RuntimeException;


/**
 * A lock using files as the resource to synchronize access on.
 */
final class FileLock extends BaseLock {


    /** @var resource[] - all file handles currently used for locking */
    private static $hFiles = [];

    /** @var string - name of the file used by the current instance */
    private $filename;


    /**
     * Constructor
     *
     * Aquire an exclusive lock for the given file. If the file does not exist it is created. As there is no reliable and
     * cross-platform mechanism to release occupied file system resources a created file is not deleted. It is the
     * responsibility of the calling code to remove an obsolete or expired file.
     *
     * @param  string $filename
     */
    public function __construct($filename) {
        Assert::string($filename);
        if (\key_exists($filename, self::$hFiles)) throw new RuntimeException('Dead-lock: re-entry detected for lock file "'.$filename.'"');
        self::$hFiles[$filename] = null;                // pre-define the index and handle re-entry if the constructor crashes

        $this->filename = $filename;
        $hFile = fopen($filename, 'c');                 // 'c' will never fail

        if (!flock($hFile, LOCK_EX)) throw new RuntimeException('Can not aquire exclusive lock on file "'.$filename.'"');

        self::$hFiles[$filename] = $hFile;              // lock is aquired
    }


    /**
     * Whether the lock is aquired.
     *
     * @return bool
     */
    public function isAquired() {
        if (isset(self::$hFiles[$this->filename]))
            return is_resource(self::$hFiles[$this->filename]);
        return false;
    }


    /**
     * If called on an aquired lock the lock is released. If called on an already released lock the call does nothing.
     */
    public function release() {
        if ($this->isAquired()) {
            $hFile = self::$hFiles[$this->filename];
            if (!flock($hFile, LOCK_UN)) throw new RuntimeException('Can not release lock on file "'.$this->filename.'"');
            unset(self::$hFiles[$this->filename]);
            fclose($hFile);
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
    }
}
