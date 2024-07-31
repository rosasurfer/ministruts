<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\loader;

use rosasurfer\ministruts\core\CObject;

use const rosasurfer\ministruts\ROOT_DIR;


/**
 * ClassLoader
 */
class ClassLoader extends CObject {


    /** @var ?string[] - class map */
    protected $classMap = null;

    /** @var bool - whether this instance is registered */
    protected $registered;


    /**
     * Register this instance.
     *
     * @return $this
     */
    public function register() {
        if (!$this->registered) {
            $this->registered = spl_autoload_register([$this, 'autoLoad'], true, false);
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
     *
     * @return void
     */
    public function autoLoad($class) {
        if (!isset($this->classMap)) {
            // initialize a case-insensitive class map
            $classMap = require(ROOT_DIR.'/vendor/composer/autoload_classmap.php');
            $this->classMap = \array_change_key_case($classMap, CASE_LOWER);
        }

        $lowerClass = strtolower($class);

        if (isset($this->classMap[$lowerClass])) {
            include($this->classMap[$lowerClass]);
        }
    }
}
