<?
/**
 * Object
 */
class Object {


   /**
    * Magische Methode. Fängt durch unbekannte Methodenaufrufe ausgelöste, fatale PHP-Fehler ab.
    *
    * @param string $methode - Name der aufgerufenen Methode
    * @param array  $params  - Array mit den der Methode übergebenen Parametern
    *
    * @throws RuntimeException
    */
   private static function __call($method, array $params) {
      $trace = debug_backTrace();

      for ($i=0; $i < sizeOf($trace); $i++) {
         if (strToLower($trace[$i]['function']) !== '__call')
            break;
      }
      throw new RuntimeException('Call to undefined method '.$trace[$i]['class']."::$methode()");
   }


   /**
    * Magische Methode. Fängt das Setzen undefinierter Klassenvariablen ab.
    *
    * @param string $property - Name der undefinierten Variable
    * @param mixed  $value    - Wert, auf den die Variable gesetzt werden sollte
    *
    * @throws RuntimeException
    */
   private function __set($property, $value) {
      $trace = debug_backTrace();
      $class = get_class($trace[0]['object']);
      throw new RuntimeException("Undefined class variable $class::$property");
   }


   /**
    * Gibt eine lesbare Version der Instanz zurück.
    *
    * @return string
    */
   public function __toString() {
      return print_r($this, true);
   }
}
?>
