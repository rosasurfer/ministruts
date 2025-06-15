<?php
namespace rosasurfer\core;

use rosasurfer\core\exception\ClassNotFoundException;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\RuntimeException;

use function rosasurfer\is_class;


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
     * @param  string   $class  - class name
     * @param  array ...$params - variable list of constructor parameters
     *
     * @return Singleton
     */
    final protected static function getInstance($class, ...$params) {
        if (isset(self::$instances[$class]))
            return self::$instances[$class];

        // set a marker to detect recursive method invocations
        static $currentCreations;
        if (isset($currentCreations[$class])) throw new RuntimeException('Detected recursive call of '.__METHOD__.'('.$class.')');
        $currentCreations[$class] = true;

        // check validity of the passed class (omitting this check can cause an uncatchable fatal error)
        if (!is_a($class, __CLASS__, true)) {
            if (!is_class($class)) throw new ClassNotFoundException('Class not found: '.$class );
            throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);
        }

        // instantiate the class with the passed parameters
        self::$instances[$class] = new $class(...$params);

        // reset the marker for detecting recursive method invocations
        unset($currentCreations[$class]);

        return self::$instances[$class];
    }


    /**
     * Prevent cloning of {@link Singleton} instances.
     */
    final protected function __clone() {
        // protected because since PHP8.0 private functions can't be final anymore
    }
}
