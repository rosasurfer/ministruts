<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\lock;

use SysvSemaphore;
use Throwable;

use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\log\Logger;

use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\L_DEBUG;
use const rosasurfer\ministruts\L_INFO;
use const rosasurfer\ministruts\L_NOTICE;
use const rosasurfer\ministruts\NL;


/**
 * System-V lock
 *
 * A lock using a System-V shared memory segment as a mutex (not supported on Windows).
 */
class SystemFiveLock extends BaseLock {

    /** @var array<resource|SysvSemaphore|null> - semaphore handles */
    private static array $semaphores = [];

    /** @var string */
    private string $key;


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
     * $lock = new SystemFiveLock(__FILE__.'#'.__LINE__);
     * </pre>
     */
    public function __construct(string $key) {
        if (\key_exists($key, self::$semaphores)) throw new RuntimeException("Dead-lock detected: already holding a lock for key \"$key\"");
        self::$semaphores[$key] = null;

        // TODO: use fTok() instead
        $integer = $this->keyToId($key);

        $i = 0;
        $trials = 5;                                        // max. amount of errors before an exception is re-thrown
        $messages = [];

        while (true) {
            try {
                $hSemaphore = sem_get($integer, 1, 0666);   // get semaphore handle
                if (!$hSemaphore)              throw new RuntimeException("cannot get semaphore handle for key $integer");
                if (!sem_acquire($hSemaphore)) throw new RuntimeException("cannot aquire semaphore for key $integer");

                $this->key = $key;                          // TODO: sem_get() and sem_acquire() may fail under load
                self::$semaphores[$key] = $hSemaphore;
            }
            catch (Throwable $ex) {
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
                    Logger::log("$message, trying again...", L_DEBUG);
                    $messages[] = $message;
                    usleep(200000);                         // wait 200 msec
                    continue;
                }

                // prevent infinite loop
                if (!$ex instanceof IRosasurferException) {
                    $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
                }
                throw $ex->appendMessage("Giving up to get lock for key \"$key\" after $i trials".($messages ? ', previous errors:'.NL.join(NL, $messages) : ''));
            }
            break;
        }
    }


    /**
     * Destructor
     *
     * Releases any lock still held.
     *
     * @return void
     */
    public function __destruct(): void {
        try {
            $this->release();
        }
        catch (Throwable $ex) {
            throw ErrorHandler::handleDestructorException($ex);
        }
    }


    /**
     * Whether the lock is currently aquired.
     *
     * @return bool
     */
    public function isAquired(): bool {
        return isset(self::$semaphores[$this->key]);
    }


    /**
     * If called on an aquired lock the lock is released.
     * If called on an already released lock the call does nothing.
     *
     * @return void
     */
    public function release(): void {
        if ($this->isAquired()) {
            $semaphore = self::$semaphores[$this->key];
            // @phpstan-ignore argument.type (incompatible types PHP7 vs PHP8)
            if (!sem_remove($semaphore)) throw new RuntimeException("Cannot remove semaphore for key \"$this->key\"");
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
    private function keyToId(string $key): int {
        return (int) hexdec(substr(md5($key), 0, 7)) + strlen($key);
    }                                         // 7: strlen(dechex(PHP_INT_MAX)) - 1 (x86)
}
