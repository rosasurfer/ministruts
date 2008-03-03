<?
/**
 * Config
 *
 * Klasse zur Anwendungskonfiguration.  Einstellungen werden in Dateien "config.properties" und
 * "config-custom.properties" abgelegt, die in folgender Reihenfolge gesucht und verarbeitet werden:
 *
 *
 * Webanwendungen:
 * ---------------
 *    - "config-custom.properties" im WEB-INF-Verzeichnis der Anwendung
 *    - "config.properties"        im WEB-INF-Verzeichnis der Anwendung
 *
 * Für jeden einzelnen Pfad des PHP-Include-Pfades (ini_get("include_path")):
 *    - "config-custom.properties" in diesem Pfad
 *    - "config.properties"        in diesem Pfad
 *
 *
 * Konsolenanwendungen:
 * --------------------
 *    - "config-custom.properties" im Scriptverzeichnis
 *    - "config.properties"        im Scriptverzeichnis
 *
 * Für jeden einzelnen Pfad des PHP-Include-Pfades (ini_get("include_path")):
 *    - "config-custom.properties" in diesem Pfad
 *    - "config.properties"        in diesem Pfad
 *
 *
 * Durch diese Reihenfolge können mehrere Dateien gleichen Namens geladen werden, z.B. eine für die
 * Konfiguration einer Bibliothek (liegt im Include-Path der Bibliothek) und eine weitere für die
 * Konfiguration eines einzelnen Projektes (liegt im WEB-INF-Verzeichnis des Projektes).  Dabei haben
 * die zuerst eingelesenen Einstellungen eine höhere Priorität als die später eingelesenen.  Allgemeine
 * Einstellungen in einer Bibliothek können so durch eigene Einstellungen im Projektverzeichnis
 * überschrieben werden.  Da in einem Verzeichnis zuerst nach "config-custom.properties" und danach
 * nach "config.properties" gesucht wird, hat eine "config-custom.properties" eine höhere Priorität
 * als eine "config-custom.properties" im selben Verzeichnis.
 *
 * Die Datei "config.properties" enthält jeweils allgemeingültige Einstellungen für den Produktivbetrieb.
 * Diese Datei wird im CVS versioniert.  Die Datei "config-custom.properties" dagegen enthält arbeitsplatz-
 * spezifische Einstellungen.  Sie ist für den Entwicklungsbetrieb gedacht und wird nicht im CVS gespeichert.
 * Dadurch eignet sie sich für persönliche Einstellungen des Entwicklers (lokale Datenbankzugangsdaten,
 * E-Mailadressen, Loglevel etc.).
 *
 * Werden in "config.properties" Produktiveinstellungen und in "config-custom.properties" Entwicklungs-
 * einstellungen gespeichert, kann durch einfaches Umbenennen von "config-custom.properties" zwischen
 * beiden Umgebungen umgeschaltet werden.
 *
 *
 * Dateiformat:
 * ------------
 * Einstellungen werden als "name = wert" abgelegt. Kommentare werden mit einem Hash "#" eingeleitet.
 * Leerzeilen, führende und abschließende Leerzeichen werden ignoriert.  Durch Gruppierung können
 * Strukturen definiert werden, eine solche Struktur kann im ganzen (Rückgabewert: Array) oder als
 * einzelner Wert (Rückgabewert: String) abgefragt werden.
 *
 *
 * Beispiel:
 * ---------
 * db.connector = mysql
 * db.host      = localhost:3306
 * db.username  = myapp_user
 * db.password  = plainpassword
 * db.database  = db_test
 *
 * # Kommentar in eigener Zeile
 *
 * logger.php.struts        = info
 * logger.php.struts.Action = warn          # Kommentar innerhalb einer Zeile
 */
final class Config extends Singleton {


   // Property-Pool
   private $properties = array();


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück.
    *
    * @return Singleton
    */
   public static function me() {
      // gibt es eine Instanz im Cache ?
      $instance = Cache ::get(__CLASS__);

      if (!$instance) {       // nein, neue Instanz erzeugen ...
         $instance = Singleton ::getInstance(__CLASS__);

         // ... und auf Production-Server cachen
         if (isSet($_SERVER['REQUEST_METHOD']) && $_SERVER['REMOTE_ADDR']!='127.0.0.1')
            Cache ::set(__CLASS__, $instance);
      }
      return $instance;
   }


   /**
    * Konstruktor
    *
    * Lädt die Konfiguration aus der Datei "config.properties", wenn sie existiert.  Existiert eine
    * weitere Datei "config-custom.properties", wird auch diese geladen. Diese zusätzliche Datei darf
    * nicht im Repository gespeichert werden, sodaß parallel eine globale und eine lokale Konfiguration
    * mit unterschiedlichen Einstellungen verwendet werden können. Lokale Einstellungen überschreiben
    * globale Einstellungen.
    */
   protected function __construct() {
      $files = array();

      // Ausgangsverzeichnis ermitteln (bei Webapplikation "WEB-INF", bei Shellscripten das Scriptverzeichnis)
      $path = realPath(dirName($_SERVER['SCRIPT_FILENAME']));
      if (isSet($_SERVER['REQUEST_METHOD']))
         $path .= DIRECTORY_SEPARATOR.'WEB-INF';


      // Include-Pfad in einzelne Pfade zerlegen
      $paths = explode(PATH_SEPARATOR, $path.PATH_SEPARATOR.ini_get('include_path'));


      // Config-Dateien suchen
      foreach ($paths as $key => $path) {
         $path = realPath($path);
         if ($path) {
            if (is_file($file = $path.DIRECTORY_SEPARATOR.'config-custom.properties')) $files[$file] = $file; // assoz. Array verhindert doppelte Einträge
            if (is_file($file = $path.DIRECTORY_SEPARATOR.'config.properties'))        $files[$file] = $file;
         }
      }


      // wir laden die Dateien von der Wurzel aus und überschreiben alle vorhandenen Werte
      $files = array_reverse($files);


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
            throw new RuntimeException('Syntax error in configuration file "'.$filename.'" at line: "'.$line.'"');

         // Eigenschaft setzen
         $this->setProperty(trim($parts[0]), trim($parts[1]), false); // Cache nicht aktualisieren
      }
   }


   /**
    * Gibt die unter dem angegebenen Schlüssel gespeicherte Einstellung zurück.  Existiert die Einstellung
    * nicht, wird der angegebene Defaultwert zurückgegeben.
    *
    * @param string $key     - Schlüssel
    * @param mixed  $default - Defaultwert (kann auch NULL sein, um z.B. eine Exception zu verhindern)
    *
    * @return mixed - Konfigurationseinstellung
    *
    * @throws RuntimeException - wenn unter dem angegebenen Schlüssel keine Einstellung existiert und
    *                            kein Defaultwert angegeben wurde
    */
   public static function get($key, $default = null) {
      if (!is_string($key)) throw new IllegalTypeException('Illegal type of argument $key: '.getType($key));

      $value = self ::me()->getProperty($key);

      if ($value === null) {
         if (func_num_args() == 1)
            throw new RuntimeException('Missing configuration setting for key "'.$key.'"');

         return $default;
      }

      return $value;
   }


   /**
    * Setzt oder überschreibt die Einstellung mit dem angegebenen Schlüssel. Wert muß ein String sein.
    * Diese Methode kann aus der Anwendung heraus aufgerufen werden, um zusätzliche Laufzeiteinstellungen
    * zu speichern. Zusätzliche Einstellungen werden nicht in "config.properties" gespeichert, gehen
    * aber nach Ende des Requests nicht verloren, solange der Server nicht neu gestartet wird.  Auf
    * diese Weise kann sich die Anwendung während der Laufzeit selbständig anpassen und steuern.
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

      $parts = explode('.', $key);
      $size = sizeOf($parts);

      for ($i=0; $i<$size; ++$i) {
         $subkey = $parts[$i];

         if (!isSet($properties[$subkey]))
            break;

         if ($i+1 == $size)   // das letzte Element
            return $properties[$subkey];

         $properties =& $properties[$subkey];
      }
      return null;
   }


   /**
    * @param string  $key
    * @param string  $value
    * @param boolean $persist
    *
    * @see Config::set()
    */
   private function setProperty($key, $value, $persist = true) {
      $properties =& $this->properties;

      $parts = explode('.', $key);
      $size = sizeOf($parts);

      for ($i=0; $i<$size; ++$i) {
         $current = trim($parts[$i]);
         if ($current == '') throw new InvalidArgumentException('Invalid argument $key: '.$key);

         if ($i+1 < $size) {
            // noch nicht das letzte Element
            if (!isSet($properties[$current])) {
               $properties[$current] = array();                            // weitere Ebene
            }
            elseif (is_string($properties[$current])) {
               $properties[$current] = array('' => $properties[$current]); // weitere Ebene und alter Wert
            }
            $properties =& $properties[$current];
         }
         else {
            // das letzte Element
            if (!isSet($properties[$current]) || is_string($properties[$current])) {
               $properties[$current] = $value;           // überschreiben
            }
            else {
               $properties[$current][''] = $value;       // Wurzeleintrag eines Arrays
            }
         }
      }

      // Cache aktualisieren, falls die Config-Instanz dort gespeichert ist
      if ($persist && Cache ::isCached(__CLASS__))
         Cache ::set(__CLASS__, $this);
   }
}
?>
