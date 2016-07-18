<?php
use rosasurfer\ministruts\core\Object;

use rosasurfer\ministruts\exception\IllegalTypeException;
use rosasurfer\ministruts\exception\InvalidArgumentException;
use rosasurfer\ministruts\exception\RuntimeException;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\MINISTRUTS_ROOT;
use const rosasurfer\SECONDS;
use const rosasurfer\WINDOWS;


/**
 * Default application configuration. Settings in the defined configuration directories are read from the file
 * "config-default.properties" (if it exists) and "config.properties" (if it exists). Config files in the directories
 * are looked-up and processed in the following order:
 *
 * CLI applications:
 * <pre>
 *   "config.properties"         in the directory containing the main running script
 *   "config-default.properties" in the same directory
 * </pre>
 *
 * All applications (web + CLI):
 * <pre>
 *   "config.properties"         in the application's config directory, that's: APPLICATION_ROOT.'/app/config/'
 *   "config-default.properties" in the same directory
 *
 *   "config.properties"         in the framework's config directory, that's: MINISTRUTS_ROOT.'/src/'
 *   "config-default.properties" in the same directory
 * </pre>
 *
 *
 * - The settings of the found files are merged, whether a single setting is defined in one file or another doesn't matter.
 *   If multiple occurrences of the same setting are found the first encountered setting "wins".
 *
 * - A "config-default.properties" file should contain common global settings identical for all developers. It is meant
 *   to be stored in the code repository and is the place to store default settings.
 *
 * - A "config.properties" file should contain custom user or working place specific settings and is not meant to be stored
 *   in the code repository. It is the place to store specific settings, e.g. production settings.
 *
 *
 * File format:<br>
 * Settings are defined as "key = value" pairs. Empty lines and enclosing white space are ignored. Sub-keys can be used
 * to create array structures which can be queried as a whole (array) or as single values (string).
 *
 *
 * @example
 * <pre>
 * db.connector = mysql
 * db.host      = localhost:3306
 * db.username  = username
 * db.password  = password
 * db.database  = schema
 *
 * # comment on its own line
 * log.level.Action                            = warn             # comment at the end of line
 * log.level.rosasurfer\ministruts\util\Config = notice           # keys may contain namespaces
 *
 * key.subkey with spaces = value                                 # subkeys may contain spaces
 * key.   indented subkey = value                                 # enclosing space around subkeys is ignored
 *
 * key."double.quoted.subkey.with.separators"            = value  # quoted subkeys can contain otherwise illegal characters
 * key.'single.quoted.subkey.with.separators'            = value
 *
 * key."quote characters \" in a subkey must be escaped" = value
 * key.'quote characters \' in a subkey must be escaped' = value
 * </pre>
 */
class Config extends Object {


   /**
    * @var string[] - config file names (existing and non-existing) of all config directories
    */
   private $files = [];

   /**
    * @var string[] - property pool
    */
   private $properties = [];


   /**
    * Gibt die Instanz dieser Klasse zurück. Obwohl Config nicht Singleton implementiert, gibt es im User-Code nur eine
    * einzige Instanz. Diese Instanz wird gecacht.
    *
    * @return Config
    */
   public static function me() {
      static /*Config*/ $config = null;      // emuliert Singleton (Config kann nicht Singleton sein, um serialisiert werden zu können)
      static /*bool  */ $locked = false;     // detect recursive calls

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
               $dependency = FileDependency::create(array_keys($config->files));
               if (!WINDOWS && !CLI && !LOCALHOST)                   // Unterscheidung Production/Development
                  $dependency->setMinValidity(60 * SECONDS);

               // ... und cachen
               $cache->set(__CLASS__, $config, Cache::EXPIRES_NEVER, $dependency);
            }
         }
      }
      // evt. Lock wird durch GarbageCollector freigegeben
      return $config;
   }


   /**
    * Constructor
    *
    * Lädt die Konfiguration aus allen existierenden config-default.properties- und config.properties-Dateien.
    * Die config.properties-Dateien sollen nicht im Repository gespeichert werden, so daß eine Default- und eine
    * Custom-Konfiguration mit unterschiedlichen Einstellungen möglich sind. Custom-Einstellungen überschreiben
    * Default-Einstellungen.
    */
   private function __construct() {
      // define config paths according to runtime context
      $paths = [];
      CLI && $paths[] = dirName($_SERVER['SCRIPT_FILENAME']);           // cli:   script directory
      $paths[]        = APPLICATION_ROOT.'/app/config';                 // all: + app config directory
      $paths[]        = MINISTRUTS_ROOT.'/src';                         // all: + framework config directory

      // normalize paths and remove duplicates
      foreach ($paths as $i => $path) {
         if (!is_dir($path)) {
            unset($paths[$i]);                                          // app config directory might not exist
            continue;
         }
         $paths[$i] = realPath($path);
      }
      $paths = array_unique($paths);

      // look-up existence of all config files
      $files = [];
      foreach ($paths as $path) {
         $files[$file] = is_file($file=$path.DIRECTORY_SEPARATOR.'config.properties');
         $files[$file] = is_file($file=$path.DIRECTORY_SEPARATOR.'config-default.properties');
      }
      $this->files = $files;

      // Load config files in reverse order. This way duplicate incoming settings over-write existing ones.
      foreach (array_reverse($files) as $fileName => $fileExists) {
         $fileExists && $this->loadFile($fileName);
      }
   }


   /**
    * Lädt eine Konfigurationsdatei. Schon vorhandene Einstellungen werden durch folgende Einstellungen
    * überschrieben.
    *
    * @param  string $filename - Dateiname
    */
   private function loadFile($filename) {
      $lines = file($filename, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);

      foreach ($lines as $line) {
         // Kommentare entfernen
         $parts = explode('#', $line, 2);
         $line  = trim($parts[0]);
         if ($line == '')
            continue;

         // Key und Value trennen
         $parts = explode('=', $line, 2);
         if (sizeOf($parts) < 2) throw new RuntimeException('Syntax error in configuration file "'.$filename.'" at line: "'.$line.'"');

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
      $value = self::me()->getProperty($key);

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

      return self::me()->setProperty($key, $value);
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

         if (!is_array($properties) || !isSet($properties[$subkey])) // PHP sucks!!! $some_string[$other_string] löst keinen Fehler aus,
            break;                                                   // sondern castet $other_string ohne Warnung zu 0
                                                                     // Ergebnis: $some_string[$other_string] == $some_string{0}
         if ($i+1 == $size)               // das letzte Element
            return $properties[$subkey];
                                                                     // wieder:
         $properties =& $properties[$subkey];                        // $some_string[$other_string] == $some_string{0} (ohne Fehler)
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
