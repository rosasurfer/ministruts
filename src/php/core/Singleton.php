<?
/**
 * Vererbbare Singleton-Pattern-Implementierung.
 */
abstract class Singleton extends Object {


   // Pool mit allen existierenden Singletons
   private static $instances = array();


   // Konstruktor
   protected function __construct() {/* kann nicht von außen aufgerufen werden */}


   /**
    * Erzeugt die Singleton-Instanz der gewünschten Klasse.
    *
    * @param string $class - Klassennname
    *
    * @return Singleton
    */
   final public static function getInstance($class, $args = null /*, ... */) {
      if (isSet(self::$instances[$class]))
         return self::$instances[$class];

      // for Singleton::getInstance('class_name', $arg1, ...) calling
      if (func_num_args() > 2) {
         $args = func_get_args();
         array_shift($args);
      }
      $object = $args ? new $class($args) : new $class();

      if (!$object instanceof Singleton)
         throw new InvalidArgumentException('Class is not a Singleton subclass: '.$class);

      return self::$instances[$class] = $object;
   }


   final private function __clone() {/* do not clone me */}
}
?>
