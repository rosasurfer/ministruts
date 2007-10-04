<?
/**
 * Singleton
 *
 * Vererbbare Implementierung des Singleton-Patterns.
 */
abstract class Singleton extends Object implements Instantiatable {


   // Pool mit allen momentan existierenden Singletons
   private static $instances = array();


   /**
    * Nicht-öffentlicher Konstruktor
    */
   protected function __construct() { /* you can't call me from outside with new ... */ }


   /**
    * Erzeugt die Singleton-Instanz der gewünschten Klasse.
    *
    * @param string $class - Klassennname
    *
    * @return Singleton
    */
   final protected static function getInstance($class, $args = null /*, ... */) {
      if (isSet(self::$instances[$class]))
         return self::$instances[$class];

      // for Singleton::getInstance('class_name', $arg1, ...) calling
      if (func_num_args() > 2) {
         $args = func_get_args();
         array_shift($args);
      }
      $object = $args ? new $class($args) : new $class();

      if (!$object instanceof Singleton)
         throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);

      return self::$instances[$class] = $object;
   }


   /**
    * Verhindert das Clonen von Singleton-Instanzen.
    */
   final private function __clone() {/* do not clone me */}
}
?>
