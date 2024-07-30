<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core;

use rosasurfer\ministruts\core\exception\ClassNotFoundException;
use rosasurfer\ministruts\core\exception\InvalidTypeException;
use rosasurfer\ministruts\core\exception\RuntimeException;

use function rosasurfer\ministruts\is_class;


/**
 * Singleton
 *
 * Factory and base class for implementation of the Singleton pattern.
 */
abstract class Singleton extends CObject {


    /** @var Singleton[] - the currently existing singletons */
    private static $instances = [];


    /**
     * Non-public constructor.
     *
     * Prevents instantiation from outside and forces the use of {@link Singleton::getInstance()}.
     */
    protected function __construct() {}


    /**
     * Factory method for a {@link Singleton} instance of the specified class.
     *
     * @param  string   $class - class name
     * @param  mixed ...$args  - variable list of constructor arguments
     *
     * @return Singleton
     */
    final protected static function getInstance($class, ...$args) {
        if (isset(self::$instances[$class])) {
            return self::$instances[$class];
        }

        // set a marker to detect recursive method invocations
        static $currentCreations;
        if (isset($currentCreations[$class])) throw new RuntimeException('Detected recursive call of '.__METHOD__.'('.$class.')');
        $currentCreations[$class] = true;

        // check validity of the passed class (omitting this check can cause an uncatchable fatal error)
        if (!is_a($class, __CLASS__, true)) {
            if (!is_class($class)) throw new ClassNotFoundException('Class not found: '.$class );
            throw new InvalidTypeException('Not a '.__CLASS__.' subclass: '.$class);
        }

        // instantiate the class with the passed parameters
        self::$instances[$class] = new $class(...$args);

        // reset the marker for detecting recursive method invocations
        unset($currentCreations[$class]);

        return self::$instances[$class];
    }


    /**
     * Prevent cloning of {@link Singleton} instances.
     */
    final private function __clone() {}
}
