<?
/**
 * Config
 *
 * Helferklasse zur Anwendungskonfiguration. Einstellungen werden in der Datei "config.properties" abgelegt.
 * Bei Webanwendungen wird nach dieser Datei im WEB-INF-Verzeichnis, bei Konsolenanwendungen im aktuellen
 * Verzeichnis gesucht. Existiert eine Datei "config-custom.properties", wird auch diese eingelesen, sie
 * überschreibt gleichlautende Einstellungen in "config.properties". Dadurch können parallel eine globale
 * und eine lokale Konfiguration vorgehalten werden.
 *
 * Dateiformat:
 * ------------
 * Einstellungen werden als "name = wert" abgelegt. Kommentare werden mit einem Hash "#" eingeleitet.
 * Leerzeilen und führende oder abschließende Leerzeichen werden ignoriert. Einstellungen können gruppiert
 * werden, eine solche Gruppe kann einzeln (Rückgabewert ist ein String ) oder komplett (Rückgabewert ist
 * ein assoziatives Array) abgefragt werden.
 *
 * Beispiel:
 * ---------
 * <pre>
 * app-name                 = myapp
 *
 * db.host                  = localhost:3306
 * db.username              = myapp_user
 * db.password              = plainpassword
 * db.database              = db_test
 *
 * # ein Komentar in einer eigenen Zeile
 *
 * logger.php.struts        = info
 * logger.php.struts.Action = warn              # ein weiterer Kommentar innerhalb der Zeile
 * </pre>
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
      // versuchen, die Instanz aus dem Cache zu laden
      $instance = Cache ::get(__CLASS__);

      if (!$instance) { // Cache-Miss, neue Instanz erzeugen ...
         $instance = parent:: getInstance(__CLASS__);

         // ... und cachen (wenn auf Production-Server)
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
    * unterschiedlichen Einstellungen verwendet werden können. Lokale Einstellungen überschreiben globale
    * Einstellungen.
    */
   protected function __construct() {
      // Konfigurationen suchen, bei Webapplikation in WEB-INF, an der Konsole im aktuellen Verzeichnis
      $path = getCwd().(Request ::me() ? '/WEB-INF/' : '/');

      $files = array();
      if (is_file($file = $path.'config.properties'       )) $files[] = $file;
      if (is_file($file = $path.'config-custom.properties')) $files[] = $file;

      // gefundene Dateien laden
      foreach ($files as $file) {
         $this->loadFile($file);
      }
   }


   /**
    * Lädt eine Konfigurationsdatei. Schon vorhandene Einstellungen werden durch folgende Einstellungen
    * überschrieben.
    *
    * @param string $filename - Dateiname
    */
   private function loadFile($filename) {
      $lines = file($filename, FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);

      foreach ($lines as $line) {
         // Kommentare entfernen
         $parts = explode('#', $line, 2);
         $line = trim($parts[0]);
         if ($line == '')
            continue;

         // Key und Value trennen
         $parts = explode('=', $line, 2);
         if (sizeOf($parts) < 2)
            throw new RuntimeException('Syntax error in "'.$filename.'" at line: "'.$line.'"');

         // Eigenschaft setzen
         $this->setProperty(trim($parts[0]), trim($parts[1]), false); // Cache nicht aktualisieren
      }
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
    * Setzt oder überschreibt die Einstellung mit dem angegebenen Schlüssel. Wert muß ein String sein.
    * Diese Methode kann aus der Anwendung heraus aufgerufen werden, um zusätzliche Laufzeiteinstellungen
    * zu speichern. Obwohl diese Einstellungen nicht in der "config.properties" auftauchen, gehen sie
    * nach dem Ende des Requests nicht verloren. Solange kein Serverneustart erfolgt, kann sich die
    * Anwendung auf dese Weise während der Laufzeit selbständig an sich ändernde Bedingungen anpassen und
    * sich selbst steuern.
    *
    * @param string $key   - Schlüssel
    * @param string $value - Einstellung
    */
   public static function set($key, $value) {
      if (!is_string($key))   throw new IllegalTypeException('Illegal type of argument $key: '.getType($key));
      if (!is_string($value)) throw new IllegalTypeException('Illegal type of argument $value: '.getType($value));
      return self ::me()->setProperty($key, $value);
   }


   /**
    * @param string $key
    *
    * @return mixed
    *
    * @see Config::get()
    */
   private function getProperty($key) {
      $properties =& $this->properties;

      if (strPos($key, '.') === false) {
         if (isSet($properties[$key]))
            return $properties[$key];
      }
      else {
         $parts = explode('.', $key, 2);
         if (isSet($properties[$parts[0]]) && isSet($properties[$parts[0]][$parts[1]]))
            return $properties[$parts[0]][$parts[1]];
      }

      return null;
   }


   /**
    * @param string  $key
    * @param string  $value
    * @param boolean $updateCache
    *
    * @see Config::set()
    */
   private function setProperty($key, $value, $updateCache = true) {
      $properties =& $this->properties;

      // einfacher Schlüssel: 'setting = ???'
      if (strPos($key, '.') === false) {
         if ($key == '')
            throw new InvalidArgumentException('Invalid argument $key: '.$key);

         if (isSet($properties[$key]) && is_array($properties[$key]))
            $properties[$key][''] = $value;
         else
            $properties[$key] = $value;
      }
      // Schlüssel, der auf eine Gruppe zeigt: 'group.setting = ???'
      else {
         $parts = explode('.', $key, 2);
         if ($parts[0]=='' || $parts[1]=='')
            throw new InvalidArgumentException('Invalid argument $key: '.$key);

         if (isSet($properties[$parts[0]])) {
            if (is_string($properties[$parts[0]]))
               $properties[$parts[0]] = array('' => $properties[$parts[0]]);  // weitere Ebene mit altem Wert
         }
         else {
            $properties[$parts[0]] = array();         // weitere Ebene
         }
         $properties[$parts[0]][$parts[1]] = $value;  // Wert
      }

      // Cache aktualisieren, wenn Instanz dort gespeichert ist
      if ($updateCache && Cache ::isCached(__CLASS__))
         Cache ::set(__CLASS__, $this);
   }
}
?>
