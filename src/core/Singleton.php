<?php
namespace rosasurfer\core;

use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;
use function rosasurfer\is_class;
use rosasurfer\exception\ClassNotFoundException;


/**
 * Singleton
 *
 * Factory and base class for implementation of the Singleton pattern.
 */
abstract class Singleton extends Object {


    /** @var Singleton[] - the currently existing singletons */
    private static $instances = [];


    /**
     * Non-public constructor.
     *
     * Prevents instantiation from outside and forces the use of getInstance().
     */
    protected function __construct() {}


    /**
     * Factory method for a Singleton instance of the specified class.
     *
     * @param  string $class - class name
     * @param  ...           - variable number of arguments passed to class constructor
     *
     * @return self
     */
    final public static function getInstance($class/*, ...*/) {
        if (isSet(self::$instances[$class]))
            return self::$instances[$class];

        // set a marker to prevent recursive method invocation for the same class name
        static $currentCreations;
        if (isSet($currentCreations[$class])) throw new RuntimeException('Recursive call to '.__METHOD__.'('.$class.') detected');
        $currentCreations[$class] = true;

        // check the class (omitting this check can cause an uncatchable fatal error)
        if (!is_class($class)) throw new ClassNotFoundException('Class not found: '.$class );

        // get constructor arguments (if any)
        $args = func_get_args();
        array_shift($args);

        // unpack the arguments into the constructor
        $instance = new $class(...$args);
        if (!$instance instanceof self) throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);
        self::$instances[$class] = $instance;

        // reset the marker preventing recursive method invocation
        unset($currentCreations[$class]);

        return $instance;
    }


    /**
     * Prevent cloning of Singleton instances.
     */
    final private function __clone() {}
}
