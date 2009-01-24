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
 * Diese Datei wird in der Regel im CVS versioniert.  Die Datei "config-custom.properties" dagegen
 * enthält arbeitsplatzspezifische Einstellungen.  Sie ist für den Entwicklungsbetrieb gedacht und sollte
 * nicht im CVS gespeichert werden.  Dadurch eignet sie sich für persönliche Einstellungen des Entwicklers
 * (lokale Datenbankzugangsdaten, E-Mailadressen, Loglevel etc.).
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
final class Config extends Object {


   // die gefundenen Config-Files
   private /*string[]*/ $files = array();


   // Property-Pool
   private $properties = array();


   /**
    * Gibt die Instanz dieser Klasse zurück.  Obwohl Config nicht Singleton implementiert, gibt es im
    * User-Code nur eine einzige Instanz.
    *
    * @return Config
    */
   public static function me() {
      /*
      Die Konfiguration wird gecacht und der Cache wird mit Hilfe der Konfiguration initialisiert.
      Dadurch kommt es zu zirkulären Aufrufen zwischen Config::me() und Cache::me().  Bei solchen
      zirkulären Aufrufen (und nur dann) gibt Cache::me() NULL zurück.
      @see Cache::me()
      */

      static /*Config*/ $config       = null;   // emuliert Singleton (Config kann nicht Singleton sein)
      static /*bool*/   $configCached = false;

      $cache = null;

      if (!$config) {
         $cache = Cache ::me();                                                  // $cache kann NULL sein (siehe Kommentar)
         if (!$cache || !($configCached = ($config=$cache->get(__CLASS__)))) {   // gibt es bereits eine Config im Cache ?

            $lock = new Lock(APPLICATION_NAME.'|'.__FILE__.'#'.__LINE__);

            if (!$cache || !($configCached = ($config=$cache->get(__CLASS__)))) {
               $config = new self();                                             // nein, Config neu einlesen
            }
         }
      }
      elseif (!$configCached) {
         // Config ist noch nicht gecacht, nachschauen, ob ein Cache da ist
         $cache = Cache ::me();
      }


      // Hier haben wir immer eine Config, sie ist aber evt. noch nicht gecacht
      if ($cache && !$configCached) {
         // Cache ist da, nochmal nachschauen, ob dort bereits eine Config liegt
         if ($cached = $cache->get(__CLASS__)) {
            // JA
            $configCached = true;      // jetzt gibt es 2 Instanzen ($config + $cached, darum kann Config nicht Singleton sein)
            $config       = $cached;   // $config durch $cached Version ersetzen
         }
         else {
            // NEIN, Dependency erzeugen ...
            $dependency = FileDependency ::create(array_keys($config->files));
            if (!WINDOWS || $_SERVER['REMOTE_ADDR']!='127.0.0.1')    // Unterscheidung Production/Development
               $dependency->setMinValidity(60 * SECONDS);

            // ... und Config cachen
            $configCached = $cache->set(__CLASS__, $config, Cache ::EXPIRES_NEVER, $dependency);
         }
      }

      //Lock wird durch GarbageCollector freigegeben
      return $config;
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
   private function __construct() {
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
            $file = null;
            $files[$file] = is_file($file = $path.DIRECTORY_SEPARATOR.'config-custom.properties');
            $files[$file] = is_file($file = $path.DIRECTORY_SEPARATOR.'config.properties');
         }
      }
      $this->files = $files;


      // Weiter vorn im include_path stehende Dateien haben Vorrang vor weiter hinten stehenden.  Daher laden
      // wir die Dateien von hinten aus und überschreiben mit den folgenden Dateien die vorhandenen Werte.
      $files = array_reverse($files);

      // gefundene Dateien laden
      foreach ($files as $name => $fileExists) {
         if ($fileExists)
            $this->loadFile($name);
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
      if ($key!==(string)$key) throw new IllegalTypeException('Illegal type of argument $key: '.getType($key));

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
      if ($key!==(string)$key)     throw new IllegalTypeException('Illegal type of argument $key: '.getType($key));
      if ($value!==(string)$value) throw new IllegalTypeException('Illegal type of argument $value: '.getType($value));

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
      if ($persist && Cache ::me()->isCached(__CLASS__))
         Cache ::me()->set(__CLASS__, $this);
   }


   /**
    * Verhindert das Clonen der Config-Instanz.
    */
   private function __clone() {/* do not clone me */}
}
?>
