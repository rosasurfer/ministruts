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


   /** @var Singleton[] - Pool der momentan existierenden Singletons */
   private static $instances = [];


   /**
    * Nicht-öffentlicher Constructor
    */
   protected function __construct() { /* you can't call me from outside ... */ }


   /**
    * Gibt die Singleton-Instanz der gewünschten Klasse zurück.
    *
    * @param  string $class - Klassennname
    * @param  ...           - variable number of parameters
    *
    * @return self
    */
   final public static function getInstance($class/*, ...*/) {
      if (isSet(self::$instances[$class]))
         return self::$instances[$class];

      // rekursives Erzeugen derselben Singleton-Instanz abfangen
      static $currentCreations;
      if (isSet($currentCreations[$class]))
         throw new RuntimeException('Infinite loop: recursive call to '.__METHOD__."($class) detected");
      $currentCreations[$class] = true;

      // Parameter ermitteln
      $args = null;
      if (func_num_args() > 1) {
         $args = func_get_args();
         array_shift($args);
      }

      // argument unpacking
      $instance = $args ? new $class(...$args) : new $class();
      if (!$instance instanceof self) throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);
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