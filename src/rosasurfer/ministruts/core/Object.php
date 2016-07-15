<?php
namespace rosasurfer\ministruts\core;

use rosasurfer\ministruts\exception\RuntimeException;

use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;


/**
 * Super class for all rosasurfer classes.
 */
class Object {


   /**
    * Magic method. Catches other-wise fatal errors triggered by calls to non-existing methods.
    *
    * @param  string $method - name of the non-existing method
    * @param  array  $args   - arguments passed to the method call
    *
    * @throws RuntimeException
    */
   public function __call($method, array $args) {
      $trace = debug_backTrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $class = '{unknown_class}';

      foreach ($trace as $frame) {
         if (strToLower($frame['function']) !== '__call') {
            $class     = $frame['class'];
            $namespace = strLeftTo   ($class, '\\', -1, true, ''     );
            $name      = strRightFrom($class, '\\', -1, false, $class);
            $class     = strToLower($namespace).$name;
            break;
         }
      }
      throw new RuntimeException('Call to undefined method '.$class.'::'.$method.'()');
   }


   /**
    * Magic method. Catches the other-wise unnoticed setting of undefined class properties.
    *
    * @param  string $property - name of the undefined property
    * @param  mixed  $value    - passed value for the undefined property
    *
    * @throws RuntimeException
    */
   public function __set($property, $value) {
      $trace = debug_backTrace(DEBUG_BACKTRACE_IGNORE_ARGS|DEBUG_BACKTRACE_PROVIDE_OBJECT);
      $class = get_class($trace[0]['object']);
      throw new RuntimeException('Undefined class variable '.$class.'::'.$property);
   }


   /**
    * Returns a human-readable version of this instance.
    *
    * @return string
    */
   public function __toString() {
      return print_r($this, true);
   }
}
