<?
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
    * Nicht-öffentlicher Konstruktor
    */
   protected function __construct() { /* you can't call me from outside ... */ }


   /**
    * Gibt die Singleton-Instanz der gewünschten Klasse zurück.
    *
    * @param string $class - Klassennname
    *
    * @return Singleton
    */
   final public static function getInstance($class, $args = null /*, ... */) {
      if (isSet(self::$instances[$class]))
         return self::$instances[$class];

      // for Singleton::getInstance($class, $arg1, ...) calling
      if (func_num_args() > 2) {
         $args = func_get_args();
         array_shift($args);
      }
      $object = $args ? new $class($args) : new $class();
      if (!$object instanceof self)
         throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);

      return self::$instances[$class] = $object;
   }


   /**
    * Verhindert das Clonen von Singleton-Instanzen.
    */
   final private function __clone() {/* do not clone me */}
}
?>
