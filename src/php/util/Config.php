<?
/**
 * Config
 *
 * Klasse zur Anwendungskonfiguration.
 */
final class Config extends Singleton {


   // Property-Pool
   private $properties = array();


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück.
    *
    * @return Config
    */
   public static function me() {
      // Versuch, die Instanz aus dem Cache zu laden
      $instance = Cache ::get(__CLASS__);

      if (!$instance) { // Cache-Miss, neue Instanz erzeugen ...
         $instance = parent:: getInstance(__CLASS__);

         // ... und (nur auf dem Production-Server) cachen
         if (isSet($_SERVER['REQUEST_METHOD']) && $_SERVER['REMOTE_ADDR']!='127.0.0.1')
            Cache ::set(__CLASS__, $instance);
      }
      return $instance;
   }


   /**
    * Konstruktor
    *
    * Lädt die Konfiguration aus der Datei "config.properties", wenn sie existiert.  Existiert eine weitere
    * Datei "config-custom.properties", wird auch diese geladen. Diese zusätzliche Datei darf nicht im
    * Repository gespeichert werden, sodaß parallel eine globale und eine lokale Konfiguration mit
    * unterschiedlichen Einstellungen verwendet werden kann. Lokale Einstellungen überschreiben globale
    * Einstellungen.
    */
   protected function __construct() {
      // Konfigurationsdateien suchen, bei Webapplikationen in WEB-INF, bei Konsolenapplikationen im aktuellen Verzeichnis suchen
      $path = getCwd().(Request ::me() ? '/WEB-INF/' : '/');

      $files = array();
      if (is_file($file = $path.'config.properties'       )) $files[] = $file;
      if (is_file($file = $path.'config-custom.properties')) $files[] = $file;

      // gefundene Dateien laden
      foreach ($files as $file)
         $this->loadFile($file);
   }


   /**
    * Lädt eine einzelne Konfigurationsdatei.  Schon vorhandene Einstellungen werden durch folgende Einstellungen überschrieben.
    *
    * @param string $filename - Dateiname
    */
   private function loadFile($filename) {
      $lines = file($filename, FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);

      foreach ($lines as $line) {
         $properties =& $this->properties;

         // Key und Value trennen
         $parts = explode('=', $line, 2);
         if (sizeOf($parts) < 2)
            throw new RuntimeException('Syntax error in "'.$filename.'" at line: "'.$line.'"');
         $key   = trim($parts[0]);
         $value = trim($parts[1]);

         // Namespaces im Key in Arrays transformieren
         $names = explode('.', $key);
         $size = sizeOf($names);

         for ($i=0; $i<$size;) {
            $name = trim($names[$i]);
            if ($name == '')
               throw new RuntimeException('Syntax error in "'.$filename.'" at line: "'.$line.'"');

            if (++$i < $size) {     // nicht der letzte Schlüsselteil
               if (isSet($properties[$name]) && is_string($properties[$name]))
                  throw new RuntimeException('Syntax error in "'.$filename.'" at line: "'.$line.'"'."\nCan not overwrite string value with array value.");
               if (!isSet($properties[$name]))
                  $properties[$name] = array();    // weitere Ebene
               $properties =& $properties[$name];
            }
            else {                  // der letzte Schlüsselteil
               if (isSet($properties[$name]) && is_array($properties[$name]))
                  throw new RuntimeException('Syntax error in "'.$filename.'" at line: "'.$line.'"'."\nCan not overwrite array value with string value.");
               $properties[$name] = $value;        // Wert
            }
         }
      }
   }


   /**
    */
   private function getProperty($key) {
      if (isSet($this->properties[$key]))
         return $this->properties[$key];
      return null;
   }


   /**
    */
   private function setProperty($key, $value) {
      $this->properties[$key] = $value;

      // Cache aktualisieren, wenn Instanz dort gespeichert ist
      if (Cache ::isCached(__CLASS__))
         Cache ::set(__CLASS__, $this);
   }


   /**
    * Gibt die unter dem angegebenen Schlüssel gespeicherte Konfigurationseinstellung zurück oder NULL,
    * wenn unter diesem Schlüssel keine Einstellung existiert.
    *
    * @param string $key - Schlüssel
    *
    * @return mixed - String oder Array mit Konfigurationseinstellung
    */
   public static function get($key) {
      return self ::me()->getProperty($key);
   }


   /**
    * Setzt oder überschreibt die Einstellung mit dem angegebenen Schlüssel. Ist der Wert kein String,
    * wird er in einen String konvertiert.
    *
    * @param string $key   - Schlüssel
    * @param mixed  $value - Einstellung
    */
   public static function set($key, $value) {
      return self ::me()->setProperty($key, (string) $value);
   }
}
?>
