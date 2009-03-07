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
 *    Für jeden einzelnen Pfad des PHP-Include-Pfades:
 *    - "config-custom.properties"
 *    - "config.properties"
 *
 *
 * Konsolenanwendungen:
 * --------------------
 *    - "config-custom.properties" im Scriptverzeichnis
 *    - "config.properties"        im Scriptverzeichnis
 *
 *    Für jeden einzelnen Pfad des PHP-Include-Pfades:
 *    - "config-custom.properties"
 *    - "config.properties"
 *
 *
 * Durch diese Reihenfolge können mehrere Dateien gleichen Namens geladen werden, z.B. eine für die
 * Konfiguration einer Bibliothek (liegt im Include-Path der Bibliothek) und eine weitere für die
 * Konfiguration eines einzelnen Projektes (liegt im WEB-INF-Verzeichnis des Projektes).  Dabei haben
 * die zuerst eingelesenen Einstellungen eine höhere Priorität als die später eingelesenen.  Allgemeine
 * Einstellungen in einer Bibliothek können so durch eigene Einstellungen im Projektverzeichnis
 * überschrieben werden.  Einstellungen in "config-custom.properties" haben eine höhere Priorität als
 * die entsprechenden Einstellungen einer "config.properties" im selben Verzeichnis.
 *
 * Die Datei "config.properties" enthält allgemeingültige oder Default-Einstellungen eines Projektes.
 * Diese Datei wird im Repository gespeichert.  Die Datei "config-custom.properties" hingegen enthält
 * arbeitsplatzspezifische Einstellungen für den Entwicklungsbetrieb oder spezielle, nicht öffentliche
 * Einstellungen für den Produktivbetrieb.  Sie wird nicht im Repository gespeichert.
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
 * logger.Action = warn    # Kommentar innerhalb einer Zeile
 */
final class Config extends Object {


   // die gefundenen Config-Files
   private /*string[]*/ $files = array();


   // Property-Pool
   private $properties = array();


   /**
    * Gibt die Instanz dieser Klasse zurück.  Obwohl Config nicht Singleton implementiert, gibt es im
    * User-Code nur eine einzige Instanz.  Die Konfiguration wird im Defaul-Cache gecacht.
    *
    * @return Config
    */
   public static function me() {
      static /*Config*/ $config = null;      // emuliert Singleton (Config kann nicht Singleton sein)
      static /*bool*/   $locked = false;     // Hilfsvariable, falls me() rekursiv aufgerufen wird

      $cache = null;

      if (!$config) {
         $cache  = Cache ::me();
         $config = $cache->get(__CLASS__);   // gibt es bereits eine Config im Cache ?

         if (!$config) {
            $lock = null;

            if (!$locked) {
               // Lock holen und nochmal nachschauen
               $locked = true;
               $lock   = new Lock(APPLICATION_NAME.'|'.__FILE__.'#'.__LINE__);
               $config = $cache->get(__CLASS__);
            }

            if (!$config) {
               // Config existiert tatsächlich noch nicht, also neu einlesen ...
               $config = new self();

               // ... FileDependency erzeugen ...
               $dependency = FileDependency ::create(array_keys($config->files));
               if (!WINDOWS || (isSet($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']!='127.0.0.1'))    // Unterscheidung Production/Development
                  $dependency->setMinValidity(60 * SECONDS);

               // ... und cachen
               $cache->set(__CLASS__, $config, Cache ::EXPIRES_NEVER, $dependency);
            }
         }
      }
      // evt. Lock wird durch GarbageCollector freigegeben
      return $config;
   }


   /**
    * Konstruktor
    *
    * Lädt die Konfiguration aus allen existierenden config.properties- und config-custom.properties-
    * Dateien.  Diese config-custom.propertie-Datei sollte nicht im Repository gespeichert werden, so
    * daß eine globale und eine lokale Konfiguration mit unterschiedlichen Einstellungen möglich sind.
    * Lokale Einstellungen überschreiben globale Einstellungen.
    */
   private function __construct() {
      $files = array();

      // Ausgangsverzeichnis ermitteln (bei Webapplikation "WEB-INF", bei Shellscripten das Scriptverzeichnis)
      $path = realPath(dirName($_SERVER['SCRIPT_FILENAME']));
      if (isSet($_SERVER['REQUEST_METHOD']))
         $path .= DIRECTORY_SEPARATOR.'WEB-INF';

      // Include-Pfad zerlegen
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

      // Weiter vorn im include-path stehende Dateien haben Vorrang vor weiter hinten stehenden.  Wir
      // laden die Dateien von hinten aus und überschreiben mit folgenden Einstellungen bereits vorhandene.
      foreach (array_reverse($files) as $name => $fileExists) {
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

      // TODO: Typen erkennen und automatisch casten
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
    * Diese Methode kann aus der Anwendung heraus aufgerufen werden, um zur Laufzeit Einstellungen zu
    * zu ändern. Diese Änderungen werden nicht in "config.properties" gespeichert und gehen nach Ende
    * des Requests verloren.
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
    * @param boolean $persist - (nicht implementiert)
    *
    * @see Config::set()
    */
   private function setProperty($key, $value, $persist = false) {
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
      //if ($persist && Cache ::me()->isCached($class=get_class($this)))
      //   Cache ::me()->set($class, $this);
   }


   /**
    * Verhindert das Clonen der Config-Instanz.
    */
   private function __clone() {/* do not clone me */}
}
?>
