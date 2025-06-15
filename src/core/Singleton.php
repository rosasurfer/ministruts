<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core;

use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\core\exception\IllegalStateException;

/**
 * Singleton
 *
 * Factory and base class for implementation of the Singleton pattern.
 */
abstract class Singleton extends CObject {

    /** @var self[] - existing instances */
    private static array $instances = [];


    /**
     * Non-public constructor.
     *
     * Enforces the use of {@link self::getInstance()} to create instances.
     */
    protected function __construct() {
        // block public access
    }


    /**
     * Creates a Singleton instance of the specified class.
     *
     * @param  class-string<self> $class - class name
     * @param  mixed           ...$args  - constructor arguments
     *
     * @return Singleton
     */
    final protected static function getInstance(string $class, ...$args): self {
        if (isset(self::$instances[$class])) {
            return self::$instances[$class];
        }

        // prevent recursive calls
        static $recursion = [];
        if (isset($recursion[$class])) throw new RuntimeException('Recursive call: '.__METHOD__."($class)");
        $recursion[$class] = true;

        if (!is_subclass_of($class, __CLASS__)) {   // @phpstan-ignore function.alreadyNarrowedType ("class-string" is not a native type)
            throw new InvalidValueException("Invalid parameter \$class: $class (not a subclass of ".__CLASS__.')');
        }
        self::$instances[$class] = new $class(...$args);

        unset($recursion[$class]);
        return self::$instances[$class];
    }


    /**
     * Prevent cloning of {@link Singleton} instances.
     *
     * @return never
     */
    final protected function __clone() {
        // protected because since PHP8.0 private functions can't be final anymore
        throw new IllegalStateException('You cannot clone me: '.static::class);
    }
}
