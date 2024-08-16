<?php
namespace rosasurfer\core\lock;

use Exception;
use SysvSemaphore;
use Throwable;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\ErrorHandler;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\log\Logger;

use function rosasurfer\strStartsWith;

use const rosasurfer\L_DEBUG;
use const rosasurfer\NL;


/**
 * System-V lock
 *
 * A lock using a System-V shared memory segment as a mutex (not supported on Windows).
 */
class SystemFiveLock extends BaseLock {

    /** @var bool */
    private static $logDebug;

    /**
     * @var array<resource|SysvSemaphore|null> - semaphore handles
     * @phpstan-var array<SysvSemaphoreId|null>
     */
    private static array $semaphores = [];

    /** @var string */
    private $key;


    /**
     * Constructor
     *
     * Creates a new lock for the specified key (mutex). To be able to address the same instance across process boundaries,
     * it's recommended to use file name + code line where the lock is created as key (a fixed, but still unique value).
     *
     * @param  string $key - unique key (mutex) of the instance
     *
     * @throws RuntimeException if the current process already holds a lock for the specified key
     *
     * @example
     * <pre>
     *  &lt;?php
     *  $lock = new SystemFiveLock(__FILE__.'#'.__LINE__);
     * </pre>
     */
    public function __construct($key) {
        Assert::string($key);
        if (\key_exists($key, self::$semaphores)) throw new RuntimeException('Dead-lock detected: already holding a lock for key "'.$key.'"');
        self::$semaphores[$key] = null;

        $loglevel = Logger::getLogLevel(__CLASS__);
        self::$logDebug = ($loglevel <= L_DEBUG );

        // TODO: use fTok() instead
        $integer = $this->keyToId($key);

        $i = 0;
        $trials = 5;                                        // max. amount of errors before an exception is re-thrown
        $messages = [];

        while (true) {
            $ex = null;
            try {
                $semaphoreId = sem_get($integer, 1, 0666);  // get semaphore handle
                if (!$semaphoreId)              throw new RuntimeException('cannot get semaphore handle for key '.$integer);
                if (!sem_acquire($semaphoreId)) throw new RuntimeException("cannot aquire semaphore for key $integer");

                $this->key = $key;                          // TODO: sem_get() and sem_acquire() may fail under load
                self::$semaphores[$key] = $semaphoreId;
            }
            catch (Throwable $ex) {}
            catch (Exception $ex) {}                        // @phpstan-ignore catch.alreadyCaught (PHP5 compatibility)

            if ($ex) {
                // TODO: find bug and rewrite (most probably a file limit is hit), @see ext/sysvsem/sysvsem.c
                $message = $ex->getMessage();
                $hexId = dechex($integer);
                $prefixes = [
                    "sem_get(): failed for key 0x$hexId: Invalid argument",
                    "sem_get(): failed for key 0x$hexId: Identifier removed",
                    "sem_get(): failed acquiring SYSVSEM_SETVAL for key 0x$hexId: Invalid argument",
                    "sem_get(): failed acquiring SYSVSEM_SETVAL for key 0x$hexId: Identifier removed",
                    "sem_get(): failed releasing SYSVSEM_SETVAL for key 0x$hexId: Invalid argument",
                    "sem_get(): failed releasing SYSVSEM_SETVAL for key 0x$hexId: Identifier removed",
                    "sem_acquire(): failed to acquire key 0x$hexId: Invalid argument",
                    "sem_acquire(): failed to acquire key 0x$hexId: Identifier removed",
                ];
                if (++$i < $trials && strStartsWith($message, $prefixes)) {
                    self::$logDebug && Logger::log("$message, trying again...", L_DEBUG);
                    $messages[] = $message;
                    usleep(200000);                         // wait 200 msec
                    continue;
                }
                // prevent infinite loop
                if (!$ex instanceof IRosasurferException) {
                    $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
                }
                throw $ex->addMessage("Giving up to get lock for key \"$key\" after $i trials".($messages ? ', previous errors:'.NL.join(NL, $messages) : ''));
            }
            break;
        }
    }


    /**
     * Destructor
     *
     * Release any lock still held.
     *
     * @return void
     */
    public function __destruct() {
        $ex = null;
        try {
            $this->release();
        }
        catch (Throwable $ex) {}
        catch (Exception $ex) {}                // @phpstan-ignore catch.alreadyCaught (PHP5 compatibility)
        if ($ex) throw ErrorHandler::handleDestructorException($ex);
    }


    /**
     * Whether the lock is currently aquired.
     *
     * @return bool
     */
    public function isAquired() {
        if (isset(self::$semaphores[$this->key])) {
            return is_resource(self::$semaphores[$this->key]);
        }
        return false;
    }


    /**
     * If called on an aquired lock the lock is released.
     * If called on an already released lock the call does nothing.
     */
    public function release() {
        if ($this->isAquired()) {
            if (!sem_remove(self::$semaphores[$this->key])) throw new RuntimeException('Cannot remove semaphore for key "'.$this->key.'"');
            unset(self::$semaphores[$this->key]);
        }
    }


    /**
     * Convert a key (string) to a unique numerical value (int).
     *
     * @param  string $key
     *
     * @return int - numerical value
     *
     * @todo   replace with fTok()
     */
    private function keyToId($key) {
        return (int) hexdec(substr(md5($key), 0, 7)) + strlen($key);
    }                                         // 7: strlen(dechex(PHP_INT_MAX)) - 1 (x86)
}
