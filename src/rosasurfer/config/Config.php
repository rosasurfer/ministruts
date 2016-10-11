<?php
namespace rosasurfer\config;

use rosasurfer\MiniStruts;

use rosasurfer\cache\Cache;

use rosasurfer\core\Object;

use rosasurfer\dependency\FileDependency;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;
use rosasurfer\exception\UnimplementedFeatureException;

use rosasurfer\lock\Lock;

use rosasurfer\log\Logger;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\MINISTRUTS_ROOT;
use const rosasurfer\SECONDS;
use const rosasurfer\WINDOWS;


/**
 * General application configuration via property files.
 *
 * Settings for the application's default configuration are read from the config files "config-default.properties"
 * (if exists) and "config.properties" (if exists). Files in multiple directories are processed and merged in the
 * following order:
 *
 * All applications (Web + CLI):
 * <pre>
 *   "config-default.properties" in the framework's config directory, that's: MINISTRUTS_ROOT.'/src/'
 *   "config.properties"         in the same directory
 *
 *   "config-default.properties" in the application's config directory, that's: APPLICATION_ROOT.'/app/config/'
 *   "config.properties"         in the same directory
 * </pre>
 *
 * CLI applications:
 * <pre>
 *   "config-default.properties" in the directory containing the running main script
 *   "config.properties"         in the same directory
 * </pre>
 *
 *
 * • Configurations consisting of multiple files are merged. Multiple occurrences of one setting overwrite each other,
 *   the last encountered setting "wins".
 *
 * • Files "config-default.properties" should contain global settings identical for all developers. These files are meant
 *   to be stored in the code repository and are the place to store default settings.
 *
 * • Files "config.properties" should contain custom user or working place specific settings and are not meant to be
 *   stored in the code repository. This files are the place to store user specific settings.
 *
 * • File format:<br>
 *   Settings are defined as "key = value" pairs. Empty lines and enclosing white space are ignored. Subkeys can be used
 *   to create structures which can be queried as a whole (array) or as single values.
 *
 *
 * @example
 * <pre>
 * db.connector = mysql                                     # subkeys create associative array structures
 * db.host      = localhost:3306
 * db.username  = username
 * db.password  = password
 * db.database  = schema
 *
 * db.options[] = option_1                                  # bracket notation create numeric array structures
 * db.options[] = option_2
 * db.options[] = option_3
 *
 * # comment on its own line
 * log.level.Action                 = warn                  # comment at the end of line
 * log.level.foo\bar\MyClass        = notice                # keys may contain namespaces
 *
 * key.subkey with spaces           = value                 # keys may contain spaces
 * key.   indented subkey           = value                 # enclosing space around keys is ignored
 *
 * key."subkey.with.key.separators" = value                 # quoted keys can contain otherwise illegal characters
 * </pre>
 */
class Config extends Object implements ConfigInterface {


   /**
    * @var ConfigInterface - default configuration; this is the instance returned by Config::getDefault()
    */
   private static $defaultInstance;


   /**
    * @var string[] - names of the looked-up config files (existing and non-existing)
    */
   public $files = [];

   /**
    * @var string[] - tree structure of the found configuration values
    */
   private $properties = [];


   /**
    * Constructor
    *
    * Create a new instance and load the specified property files.
    *
    * @param  string[] $files - array of filenames to load
    */
   public function __construct(array $files) {
      // check and remember existence of the specified config files
      $checkedFiles = [];
      foreach ($files as $file) {
         $checkedFiles[$file] = is_file($file);
         $checkedFiles[$file] = is_file($file);
      }
      $this->files = $checkedFiles;

      // load existing config files
      foreach ($this->files as $fileName => $fileExists) {
         $fileExists && $this->loadFile($fileName);
      }

      !self::$defaultInstance && self::setDefault($this);
   }


   /**
    * Load a single properties file. New settings overwrite existing ones.
    *
    * @param  string $filename
    */
   private function loadFile($filename) {
      $lines = file($filename, FILE_IGNORE_NEW_LINES);

      foreach ($lines as $i => $line) {
         $parts = explode('#', $line, 2);
         $line  = trim($parts[0]);                    // drop comments
         if (!strLen($line))                          // skip empty lines
            continue;

         $parts = explode('=', $line, 2);             // separate key/value
         if (sizeOf($parts) < 2) {
            Logger::log(__METHOD__.'()  Skipping syntax error in "'.$filename.'", line '.($i+1).': missing key-value separator', L_NOTICE);
            continue;
         }
         $key   = trim($parts[0]);
         $value = trim($parts[1]);

         // parse and store property value
         $this->setProperty($key, $value, $filename, $i+1);
      }
   }


   /**
    * Return the config setting with the specified key or the specified alternative value if no such is found.
    *
    * @param  string $key        - key
    * @param  mixed  $onNotFound - alternative value
    *
    * @return mixed - config setting
    *
    * @throws RuntimeException - if no such setting is found and no alternative value was specified
    */
   public function get($key, $onNotFound=null) {
      if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));

      $value = $this->getProperty($key);

      if ($value === null) {
         if (func_num_args() == 1) throw new RuntimeException('No configuration found for key "'.$key.'"');
         return $onNotFound;
      }
      return $value;
   }


   /**
    * Set/modify the config setting with the specified key. Modified values are not persistet and get lost with script
    * termination.
    *
    * @param  string $key
    * @param  string $value
    */
   public function set($key, $value) {
      if (!is_string($key))   throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

      $this->setProperty($key, $value);
   }


   /**
    * Look-up a property and return its value.
    *
    * @param  string $key
    *
    * @return string|[] - a string, a string array or NULL if no such setting is found
    */
   private function getProperty($key) {
      $properties  = $this->properties;
      $subkeys     = $this->parseSubkeys($key);
      $subKeysSize = sizeOf($subkeys);

      for ($i=0; $i < $subKeysSize; ++$i) {
         $subkey = trim($subkeys[$i]);
         if (!is_array($properties) || !isSet($properties[$subkey]))
            break;                                    // not found
         if ($i+1 == $subKeysSize)                    // return at the last subkey
            return $properties[$subkey];
         $properties = $properties[$subkey];          // go to the next sublevel
      }
      return null;
   }


   /**
    * Set/modify the property with the specified key.
    *
    * @param  string $key
    * @param  string $value
    */
   private function setProperty($key, $value, $file=null, $line=null) {
      // set the property depending on the existing data structure
      $properties  =& $this->properties;
      $subkeys     =  $this->parseSubkeys($key);
      $subkeysSize =  sizeOf($subkeys);

      for ($i=0; $i < $subkeysSize; ++$i) {
         $subkey = trim($subkeys[$i]);
         if (!strLen($subkey)) throw new InvalidArgumentException('Invalid argument $key: '.$key);

         if ($i+1 < $subkeysSize) {
            // not yet the last subkey
            if (!isSet($properties[$subkey])) {
               $properties[$subkey] = [];                            // create another array level
            }
            elseif (!is_array($properties[$subkey])) {
               $properties[$subkey] = ['' => $properties[$subkey]];  // create another array level and keep the
            }                                                        // existing non-array value   TODO: how to access?
            $properties =& $properties[$subkey];                     // reference the new array level
         }
         else {
            // the last subkey
            if (!isSet($properties[$subkey])) {
               $properties[$subkey] = $value;                        // store the value regularily
            }
            elseif (!is_array($properties[$subkey])) {
               $properties[$subkey] = $value;                        // overwrite the existing non-array value
            }
            else {
               $properties[$subkey][''] = $value;                    // overwrite the array default value
            }
         }
      }

      // TODO: update the cache if the instance is a cached instance
   }


   /**
    * Parse the specified into subkeys. Subkeys can consist of quoted strings.
    *
    * @param  string $key
    *
    * @return string[] - array of subkeys
    */
   private function parseSubkeys($key) {
      $k          = $key;
      $subkeys    = [];
      $quoteChars = ["'", '"'];                    // single and double quotes

      while (true) {
         $k = trim($k);

         foreach ($quoteChars as $char) {
            if (strPos($k, $char) === 0) {         // subkey starts with a quote char
               $pos = strPos($k, $char, 1);        // find the ending quote char
               if ($pos === false) throw new InvalidArgumentException('Invalid argument $key: '.$key);
               $subkeys[] = subStr($k, 1, $pos-1);
               $k         = trim(subStr($k, $pos+1));
               if (!strLen($k))                    // last subkey or next char is a key separator
                  break 2;
               if (strPos($k, '.') !== 0) throw new InvalidArgumentException('Invalid argument $key: '.$key);
               $k = subStr($k, 1);
               continue 2;
            }
         }

         // key is not quoted
         $pos = strPos($k, '.');                   // find next key separator
         if ($pos === false) {
            $subkeys[] = $k;                       // last subkey
            break;
         }
         $subkeys[] = trim(subStr($k, 0, $pos));
         $k         = subStr($k, $pos+1);          // next subkey
      }

      return $subkeys;
   }


   /**
    * Get the application's default configuration. This is the configuration set by Config::setDefault(). If none was
    * yet set, one is created. The configuration is cached.
    *
    * @return ConfigInterface
    */
   public static function getDefault() {
      $config = self::$defaultInstance;

      if (!$config) {
         // block recursive calls
         static $isActive = false;
         if ($isActive) throw new RuntimeException('Blocking recursive call to '.__METHOD__.'()');
         $isActive = true;                                              // lock the method

         $cache  = Cache::me();
         $config = $cache->get(static::class);                          // is there a cached instance?

         if (!$config) {
            // default config does not yet exist, create a new instance
            // define config paths according to runtime context
            $paths = [];
            $paths[]          = MINISTRUTS_ROOT.'/src';                 // all:   framework config directory
            $paths[]          = MiniStruts::getConfigDir();             // all: + app config directory
            if (CLI) $paths[] = dirName($_SERVER['SCRIPT_FILENAME']);   // cli: + script directory

            // normalize paths and remove duplicates
            foreach ($paths as $i => $path) {
               if (!is_dir($path)) {                                    // drop entries of non-existing directories,
                  unset($paths[$i]);                                    // e.g. the app config directory
                  continue;
               }
               $paths[$i] = realPath($path);
            }
            $paths = array_unique($paths);

            // define application config files
            $files = [];
            foreach ($paths as $path) {
               $files[] = $path.DIRECTORY_SEPARATOR.'config-default.properties';
               $files[] = $path.DIRECTORY_SEPARATOR.'config.properties';
            }

            // create the instance
            $config = new static($files);

            // create FileDependency and cache the instance
            $dependency = FileDependency::create(array_keys($config->files));
            if (!WINDOWS && !CLI && !LOCALHOST)                         // distinction dev/production (sense???)
               $dependency->setMinValidity(60 * SECONDS);
            $cache->set(static::class, $config, Cache::EXPIRES_NEVER, $dependency);

         }

         // set it as default
         if ($config)
            self::setDefault($config);

         // unlock the method
         $isActive = false;
      }
      return $config;
   }


   /**
    * Set the default configuration to be returned by Config::getDefault().
    *
    * @param  ConfigInterface $configuration
    */
   public static function setDefault(ConfigInterface $configuration) {
      self::$defaultInstance = $configuration;
      // TODO: update cache config
   }


   /**
    * Reset the internal default configuration.
    */
   public static function resetDefault() {
      self::$defaultInstance = null;
      // TODO: update cache config
   }


   /**
    * Handle clones (public but can't be called manually).
    */
   public function __clone() {
      throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
      // TODO: update cache id or disable caching of this instance
   }
}
