<?php
namespace rosasurfer\core\loader;

use rosasurfer\core\CObject;

use const rosasurfer\MINISTRUTS_ROOT;


/**
 * ClassLoader
 */
class ClassLoader extends CObject {


    /** @var string[] - class map */
    private $classMap;

    /** @var bool - whether this instance is registered */
    private $registered;


    /**
     * Constructor
     */
    public function __construct() {
        // initialize a case-insensitive class map
        $classMap = require(MINISTRUTS_ROOT.'/vendor/composer/autoload_classmap.php');
        $this->classMap = \array_change_key_case($classMap, CASE_LOWER);
    }


    /**
     * Register this instance.
     *
     * @return $this
     */
    public function register() {
        if (!$this->registered) {
            spl_autoload_register([$this, 'autoLoad'], $throw=true, $prepend=false);
            $this->registered = true;
        }
        return $this;
    }


    /**
     * Unregister this instance.
     *
     * @return $this
     */
    public function unregister() {
        if ($this->registered) {
            spl_autoload_unregister([$this, 'autoLoad']);
            $this->registered = false;
        }
        return $this;
    }


    /**
     * Load the specified class.
     *
     * @param  string $class
     */
    public function autoLoad($class) {
        $lowerClass = strtolower($class);

        if (isset($this->classMap[$lowerClass])) {
            include($this->classMap[$lowerClass]);
        }
    }
}
