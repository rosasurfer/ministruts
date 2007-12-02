<?
/**
 * PageContext
 *
 * Container, in dem für den Renderprozeß benötigte Objekte oder Variablen abgelegt werden. Beim Rendern
 * kann auf diese Daten aus dem HTML wieder zugegriffen werden.  Innerhalb eines Seitenfragments können
 * auch neue Daten im Container gespeichert werden, jedoch nur, wenn diese Daten neu sind, überschreiben
 * vorhandener Daten aus dem Seitenfragment ist nicht möglich.
 *
 * Beispiel:
 * ---------
 *    $PAGE->title = 'HTML-Title';
 *
 * Speichert die Varibale "title" mit dem Wert 'HTML-Title' im PageContext
 *
 *    $var = $PAGE->title;
 *
 * Gibt die hinterlegte Eigenschaft mit dem Namen "title" zurück.
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
    * Magische Methode, die die Eigenschaft mit dem angegebenen Namen zurückgibt. Wird automatisch
    * aufgerufen und ermöglicht den Zugriff auf Eigenschaften mit dynamischen Namen.
    *
    * @param  string $name - Name der Eigenschaft
    * @return mixed
    * @throws RuntimeException - wenn keine Eigenschaft mit diesem Namen existiert
    */
   private function __get($name) {
      if (isSet($this->properties[$name]))
         return $this->properties[$name];

      throw new RuntimeException('Unknown property: '.__CLASS__.'::$'.$name);
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

      if (isSet($this->properties[$name])) {
         // TODO: überschreiben vorhandener Eigenschaften validieren
         $this->properties[$name] = $value;
      }
      else {
         $this->properties[$name] = $value;
      }
   }
}
?>
