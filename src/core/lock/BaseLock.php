<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\lock;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\IllegalStateException;

/**
 * BaseLock
 */
abstract class BaseLock extends CObject {

    /**
     * Whether the lock is aquired.
     *
     * @return bool
     */
    abstract public function isAquired(): bool;


    /**
     * If called on an aquired lock the lock is released. If called on an already released lock the call does nothing.
     *
     * @return void
     */
    abstract public function release(): void;


    /**
     * Prevent serialization of lock instances.
     *
     * @return string[]
     */
    final public function __sleep(): array {
        throw new IllegalStateException('You cannot serialize me: '.static::class);
    }


    /**
     * Prevent de-serialization of lock instances.
     *
     * @return void
     */
    final public function __wakeUp(): void {
        throw new IllegalStateException('You cannot deserialize me: '.static::class);
    }
}
