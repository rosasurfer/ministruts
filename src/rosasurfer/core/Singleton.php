<?php
namespace rosasurfer\core;

use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;


/**
 * Singleton
 *
 * Abstraktes Grundgerüst für Implementierungen des Singleton-Patterns.
 */
abstract class Singleton extends Object {


   /**
    * Pool der momentan existierenden Singletons
    */
   private static $instances = array();


   /**
    * Nicht-öffentlicher Constructor
    */
   protected function __construct() { /* you can't call me from outside ... */ }


   /**
    * Gibt die Singleton-Instanz der gewünschten Klasse zurück.
    *
    * @param  string $class - Klassennname
    *
    * @return Singleton
    */
   final public static function getInstance($class, $args = null /*, ... */) {
      if (isSet(self::$instances[$class]))
         return self::$instances[$class];

      // rekursives Erzeugen derselben Singleton-Instanz abfangen
      static $currentCreations;
      if (isSet($currentCreations[$class]))
         throw new RuntimeException('Infinite loop: recursive call to '.__METHOD__."($class) detected");
      $currentCreations[$class] = true;

      // Parameter ermitteln
      if (func_num_args() > 2) {
         $args = func_get_args();
         array_shift($args);
      }

      // TODO: Was, wenn $args = false ist
      $instance = $args ? new $class($args) : new $class();
      if (!$instance instanceof self)
         throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);
      self::$instances[$class] = $instance;

      // Marker für rekursiven Aufruf zurücksetzen
      unset($currentCreations[$class]);

      return $instance;
   }


   /**
    * Verhindert das Clonen von Singleton-Instanzen.
    */
   final private function __clone() {/* do not clone me */}
}
