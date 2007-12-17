<?
/**
 * PageContext
 *
 * Container, in dem für den Renderprozeß benötigte Objekte oder Variablen abgelegt werden. Beim
 * Rendern kann auf diese Daten aus dem HTML zugegriffen werden.  Innerhalb eines Seitenfragments
 * können auch Daten im Container gespeichert werden, jedoch nur, wenn dabei keine vorhandenen
 * Daten überschrieben werden.
 *
 * Beispiel:
 * ---------
 *    $PAGE->title = 'HTML-Title';
 *
 * Speichert die Variable "title" mit dem Wert 'HTML-Title' im PageContext
 *
 *    $var = $PAGE->title;
 *
 * Gibt die gespeicherte Eigenschaft mit dem Namen "title" zurück.
 *
 * TODO: Properties aus dem Tiles-Context müssen auch im PageContext erreichbar sein
 */
class PageContext extends Singleton {


   /**
    * Property-Pool
    */
   protected $properties = array();


   /**
    * Gibt die einzige Klasseninstanz zurück.
    *
    * @return PageContext
    */
   public static function me() {
      return Singleton ::getInstance(__CLASS__);
   }


   /**
    * Gibt einen Wert aus dem PageContext zurück.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return mixed - der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert
    */
   public static function get($key) {
      return self::me()->__get($key);
   }


   /**
    * Speichert einen Wert im PageContext.
    *
    * @param string $key   - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value - der zu speichernde Wert
    */
   public static function set($key, $value) {
      return self::me()->__set($key, $value);
   }


   /**
    * Magische PHP-Methode, die die Eigenschaft mit dem angegebenen Namen zurückgibt. Wird automatisch
    * aufgerufen und ermöglicht den Zugriff auf Eigenschaften mit dynamischen Namen.
    *
    * @param  string $name - Name der Eigenschaft
    * @return mixed        - Wert oder NULL, wenn die Eigenschaft nicht existiert
    */
   private function __get($name) {
      return isSet($this->properties[$name]) ? $this->properties[$name] : null;
   }


   /**
    * Magische Methode, die die Eigenschaft mit dem angegebenen Namen setzt.  Wird automatisch
    * aufgerufen und ermöglicht den Zugriff auf Eigenschaften mit dynamischen Namen.
    *
    * @param string $name  - Name der Eigenschaft
    * @param mixed  $value - Wert
    */
   private function __set($name, $value) {
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));

      if ($value !== null) {
         $this->properties[$name] = $value;
      }
      else {
         unset($this->properties[$name]);
      }
   }
}
?>
