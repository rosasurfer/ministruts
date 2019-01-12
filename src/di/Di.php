<?php
namespace rosasurfer\di;

use rosasurfer\core\Object;
use rosasurfer\di\def\DefaultCliDi;
use rosasurfer\di\def\DefaultDi;

use const rosasurfer\CLI;


/**
 * Di
 *
 * A class that implements standard dependency injection/location of services and is itself a container for them.
 *
 * <pre>
 *  $di = new Di();
 *
 *  // using a string definition
 *  $di->set('request', 'rosasurfer\\ministruts\\Request');
 *
 *  // using an anonymous function
 *  $di->set('request', function() {
 *      return new \rosasurfer\ministruts\Request();
 *  });
 *
 *  $request = $di->getRequest();
 * </pre>
 */
class Di extends Object implements DiInterface {


    /** @var DiInterface - the latest instance registered as the default DI */
    protected static $default;


    /**
     * Constructor
     */
    public function __construct() {
    }


    /**
     * {@inheritdoc}
     */
    public static function getDefault() {
        if (!self::$default) {
            if (CLI) self::$default = new DefaultCliDi();
            else     self::$default = new DefaultDi();
        }
        return self::$default;
    }


    /**
     * {@inheritdoc}
     */
    public static function setDefault(DiInterface $di) {
        self::$default = $di;
    }


    /**
     * {@inheritdoc}
     */
    public static function reset() {
        self::$default = null;
    }
}
