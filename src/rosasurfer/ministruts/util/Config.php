<?php
use rosasurfer\ministruts\exceptions\IllegalTypeException;
use rosasurfer\ministruts\exceptions\InvalidArgumentException;
use rosasurfer\ministruts\exceptions\RuntimeException;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\WINDOWS;


/**
 * Config
 *
 * Klasse zur Anwendungskonfiguration.  Einstellungen werden in Dateien "config.properties" und
 * "config-custom.properties" abgelegt, die in folgender Reihenfolge gesucht und verarbeitet werden:
 *
 *
 * Webanwendungen:
 * ---------------
 *    - "config-custom.properties" im Config-Verzeichnis der Anwendung: APPLICATION_ROOT.'/app/config/'
 *    - "config.properties"        im Config-Verzeichnis der Anwendung: APPLICATION_ROOT.'/app/config/'
 *
 *    Für jeden einzelnen Pfad des Include-Pfades:
 *    - "config-custom.properties"
 *    - "config.properties"
 *
 *
 * Konsolenanwendungen:
 * --------------------
 *    - "config-custom.properties" im aktuellen Verzeichnis
 *    - "config.properties"        im aktuellen Verzeichnis
 *    - "config-custom.properties" im Scriptverzeichnis
 *    - "config.properties"        im Scriptverzeichnis
 *
 *    Für jeden einzelnen Pfad des Include-Pfades:
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
 * log.level.Action = warn             # Kommentar am Ende einer Zeile
 */
final class Config extends Object {


   // Namen der gefundenen Config-Files
   private /*string[]*/ $files = array();


   // Property-Pool
   private $properties = array();


   /**
    * Gibt die Instanz dieser Klasse zurück.  Obwohl Config nicht Singleton implementiert, gibt es im
    * User-Code nur eine einzige Instanz.  Diese Instanz wird gecacht.
    *
    * @return Config
    */
   public static function me() {
      static /*Config*/ $config = null;      // emuliert Singleton (Config kann nicht Singleton sein)
      static /*bool  */ $locked = false;     // Hilfsvariable, falls me() rekursiv aufgerufen wird

      $cache = null;

      if (!$config) {
         $cache  = Cache ::me();
         $config = $cache->get(__CLASS__);   // gibt es bereits eine Config im Cache ?

         if (!$config) {
            $lock = null;

            if (!$locked) {
               // Lock holen und nochmal nachschauen
               $locked = true;
               $lock   = new Lock(APPLICATION_ID.'|'.__FILE__.'#'.__LINE__);
               $config = $cache->get(__CLASS__);
            }

            if (!$config) {
               // Config existiert tatsächlich noch nicht, also neu einlesen ...
               $config = new self();

               // ... FileDependency erzeugen ...
               $dependency = FileDependency ::create(array_keys($config->files));
               if (!WINDOWS && !CLI && !LOCALHOST)                   // Unterscheidung Production/Development
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
    * Constructor
    *
    * Lädt die Konfiguration aus allen existierenden config.properties- und config-custom.properties-
    * Dateien.  Die config-custom.properties-Dateien sollte nicht im Repository gespeichert werden, so
    * daß eine globale und eine lokale Konfiguration mit unterschiedlichen Einstellungen möglich sind.
    * Lokale Einstellungen überschreiben globale Einstellungen.
    */
   private function __construct() {
      // Suchpfad je nach Web- oder Konsolenanwendung definieren
      if (!CLI) $path = APPLICATION_ROOT.'/app/config';                                 // web
      else      $path = getCwd().PATH_SEPARATOR.dirName($_SERVER['SCRIPT_FILENAME']);   // console: aktuelles + Scriptverzeichnis

      // Include-Pfad anhängen und Suchpfad zerlegen
      $paths = explode(PATH_SEPARATOR, $path.PATH_SEPARATOR.ini_get('include_path'));

      // Config-Dateien suchen
      $files = array();
      foreach ($paths as $key => $path) {
         $path = realPath($path);
         if ($path) {
            $file = null;
            $files[$file] = is_file($file = $path.DIRECTORY_SEPARATOR.'config-custom.properties');
            $files[$file] = is_file($file = $path.DIRECTORY_SEPARATOR.'config.properties');
         }
      }
      $this->files = $files;

      // Weiter vorn im include-path stehende Dateien haben Vorrang vor weiter hinten stehenden.  Die Dateien
      // werden von "hinten" beginnend geladen, dadurch können weiter "vorn" stehende Einstellungen vorhandene
      // weiter "hinten" stehende Einstellungen überschreiben.
      foreach (array_reverse($files) as $name => $fileExists) {
         if ($fileExists)
            $this->loadFile($name);
      }
   }


   /**
    * Lädt eine Konfigurationsdatei. Schon vorhandene Einstellungen werden durch folgende Einstellungen
    * überschrieben.
    *
    * @param  string $filename - Dateiname
    */
   private function loadFile($filename) {
      $lines = file($filename, FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);

      foreach ($lines as $line) {
         // Kommentare entfernen
         $parts = explode('#', $line, 2);
         $line  = trim($parts[0]);
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
    * @param  string $key     - Schlüssel
    * @param  mixed  $default - Defaultwert (kann auch NULL sein, um z.B. eine Exception zu verhindern)
    *
    * @return mixed - Konfigurationseinstellung
    *
    * @throws RuntimeException - wenn unter dem angegebenen Schlüssel keine Einstellung existiert und
    *                            kein Defaultwert angegeben wurde
    */
   public static function get($key, $default=null) {
      if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));

      // TODO: Typen erkennen und automatisch casten
      $value = self ::me()->getProperty($key);

      if (is_null($value)) {
         if (func_num_args() == 1) throw new RuntimeException('Missing configuration setting for key "'.$key.'"');
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
    * @param  string $key   - Schlüssel
    * @param  string $value - Einstellung
    */
   public static function set($key, $value) {
      if (!is_string($key))   throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

      return self ::me()->setProperty($key, $value);
   }


   /**
    * @param  string $key
    *
    * @return mixed
    *
    * @see Config::get()
    */
   private function getProperty($key) {
      $properties =& $this->properties;

      $parts = explode('.', $key);
      $size  = sizeOf($parts);

      for ($i=0; $i<$size; ++$i) {
         $subkey = $parts[$i];

         if (!is_array($properties) || !isSet($properties[$subkey])) // PHP sucks!!! $some_string[$some_other_string] löst keinen Fehler aus,
            break;                                                   // sondern castet $some_other_string ohne Warnung zu 0
                                                                     // Ergebnis: $some_string[$some_other_string] == $some_string{0}
         if ($i+1 == $size)               // das letzte Element
            return $properties[$subkey];

         $properties =& $properties[$subkey];                        // wieder: $some_string[$some_other_string] == $some_string{0} => ohne Fehler
      }
      return null;
   }


   /**
    * @param  string $key
    * @param  string $value
    * @param  bool   $persist - (nicht implementiert)
    *
    * @see Config::set()
    */
   private function setProperty($key, $value, $persist = false) {
      $properties =& $this->properties;

      // alt: $parts = explode('.', $key);

      // neu
      $parts = array();
      $k = $key;

      while (true) {
         $k = trim($k);

         if ($k{0} != '"') {  // normaler Key ohne Anführungszeichen (")
            $pos = strPos($k, '.');
            if ($pos === false) {
               $parts[] = $k;
               break;
            }
            else {
               $parts[] = trim(subStr($k, 0, $pos));
               $k       = subStr($k, $pos+1);
            }
         }
         else {               // Key beginnt mit Anführungszeichen (")
            $pos = strPos($k, '"',1);
            if ($pos === false) throw new InvalidArgumentException('Invalid argument $key: '.$key);

            $parts[] = subStr($k, 1, $pos-1);
            $k       = trim(subStr($k, $pos+1));

            if (!strLen($k))
               break;
            if (strPos($k, '.') !== 0) throw new InvalidArgumentException('Invalid argument $key: '.$key);
            $k = subStr($k, 1);
         }
      }
      // end: neu

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
