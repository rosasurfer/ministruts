<?php
namespace rosasurfer\config;

use rosasurfer\cache\Cache;
use rosasurfer\config\ConfigInterface as IConfig;
use rosasurfer\core\Object;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;
use rosasurfer\exception\UnimplementedFeatureException;

use rosasurfer\util\PHP;

use const rosasurfer\CLI;
use const rosasurfer\WINDOWS;


/**
 * General application configuration via property files.
 *
 * Settings for the application's default configuration are read from the config files "config-default.properties"
 * (if it exists) and "config.properties" (if it exists). Files in multiple directories are processed and merged in
 * the following order:
 *
 * - "config-default.properties" in the framework's configuration directory (MINISTRUTS_ROOT.'/src/')
 * - "config.properties"         in the same directory
 *
 * - "config-default.properties" in the application's configuration directory (APPLICATION_ROOT.'/app/config/')
 * - "config.properties"         in the same directory
 *
 * Additional configuration for CLI applications:
 * - "config-default.properties" in the directory of the running script
 * - "config.properties"         in the same directory
 *
 *
 * - Configurations consisting of multiple files are merged. Multiple occurrences of the same setting overwrite each
 *   other, the last encountered setting "wins".
 *
 * - Files "config-default.properties" contain global settings identical for all developers. These files are meant
 *   to be stored in the code repository and hold default settings.
 *
 * - Files "config.properties" contain custom developer specific settings and are not meant to be stored in the code
 *   repository. This files hold user or environment specific settings.
 *
 * - File format:
 *   Settings are defined as "key = value" pairs. Empty lines and enclosing white space are ignored. Subkeys can be used
 *   to create structures which can be queried as a whole (array) or as single values.
 *
 *
 * @example
 * <pre>
 * db.connector = mysql                               # subkey notation creates associative option arrays
 * db.host      = localhost:3306
 * db.username  = username
 * db.password  = password
 * db.database  = schema
 *
 * db.options[] = value-at-index-0                    # bracket notation creates numeric option arrays
 * db.options[] = value-at-index-1
 * db.options[] = value-at-index-2
 *
 * # comment on its own line
 * log.level.Action          = warn                   # comment at the end of line
 * log.level.foo\bar\MyClass = notice                 # keys may contain namespaces
 *
 * key.subkey with spaces    = value                  # keys may contain spaces
 * key.   indented.subkey    = value                  # enclosing space around subkeys is ignored
 * key."subkey.with.dots"    = value                  # quoted keys can contain otherwise illegal key characters
 * </pre>
 */
class Config extends Object implements ConfigInterface {


   /** @var IConfig - the application's current default configuration */
   private static $defaultInstance;

   /** @var string[] - config file names */
   protected $files = [];

   /** @var string - config directory (the last requested configuration file's directory) */
   protected $directory;

   /** @var string[] - tree structure of config values */
   protected $properties = [];


   /**
    * Constructor
    *
    * Create a new instance and load the specified property files.
    *
    * @param  string|[] $files - single or multiple filenames to load
    */
   public function __construct($files) {
      if      (is_string($files)) $files = [$files];
      else if (!is_array($files)) throw new IllegalTypeException('Illegal type of parameter $files: '.getType($files));

      // check and remember existence of the specified files
      $checkedFiles = [];
      foreach ($files as $i => $file) {
         if (!is_string($file)) throw new IllegalTypeException('Illegal type of parameter $files['.$i.']: '.getType($file));

         $checkedFiles[$file] = is_file($file);

         $relative = WINDOWS ? !preg_match('/^[a-z]:/i', $file) : ($file[0] != '/');
         $relative && $file=getCwd().PATH_SEPARATOR.$file;
         $this->directory = dirName($file);                          // save absolute path of the last specified file
      }
      $this->files = $checkedFiles;


      // load existing files
      $oldDetectStatus = PHP::ini_get_bool('auto_detect_line_endings');
      PHP::ini_set('auto_detect_line_endings', true);

      foreach ($this->files as $fileName => $fileExists) {
         $fileExists && $this->loadFile($fileName);
      }

      PHP::ini_set('auto_detect_line_endings', $oldDetectStatus);
   }


   /**
    * Load a single properties file. New settings overwrite existing ones.
    *
    * @param  string $filename
    */
   protected function loadFile($filename) {
      $lines = file($filename, FILE_IGNORE_NEW_LINES);

      foreach ($lines as $i => $line) {
         $parts = explode('#', $line, 2);
         $line  = trim($parts[0]);                    // drop comments
         if (!strLen($line))                          // skip empty lines
            continue;

         $parts = explode('=', $line, 2);             // separate key/value
         if (sizeOf($parts) < 2) {
            trigger_error(__METHOD__.'()  Skipping syntax error in "'.$filename.'", line '.($i+1).': missing key-value separator', E_USER_NOTICE);
            continue;
         }
         $key   = trim($parts[0]);
         $value = trim($parts[1]);

         // parse and store property value
         $this->setProperty($key, $value);
      }
   }


   /**
    * Get this instance's configuration directory. This is the directory of the last configuration file specified.
    *
    * @return string
    */
   public function getDirectory() {
      return $this->directory;
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
   protected function getProperty($key) {
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
   protected function setProperty($key, $value) {
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
            // the last subkey: check for bracket notation
            if (preg_match('/(.+)\b *\[ *\]$/', $subkey, $match)) {
               // bracket notation
               $subkey = $match[1];
               if (!isSet($properties[$subkey])) {
                  $properties[$subkey] = [$value];                   // create a new array value
               }
               else {
                  if (is_string($properties[$subkey]))               // make the string the array default value
                     $properties[$subkey] = ['' => $properties[$subkey]];
                  $properties[$subkey][] = $value;                   // add an arry value
               }
            }
            else {
               // regular non-bracket notation
               if (!isSet($properties[$subkey])) {
                  $properties[$subkey] = $value;                     // store the value regularily
               }
               elseif (is_string($properties[$subkey])) {
                  $properties[$subkey] = $value;                     // override the existing string value
               }
               else {
                  $properties[$subkey][''] = $value;                 // set/override the array default value
               }
            }
         }
      }

      // TODO: update the cache if the instance is a cached instance
   }


   /**
    * Parse the specified key into subkeys. Subkeys can consist of quoted strings.
    *
    * @param  string $key
    *
    * @return string[] - array of subkeys
    */
   protected function parseSubkeys($key) {
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
    * Get the current default configuration. This is the configuration set by Config::setDefault().
    *
    * @return IConfig
    */
   public static function getDefault() {
      // intentionally cause an error if $defaultInstance was not yet set
      return self::$defaultInstance;
   }


   /**
    * Set the default configuration to be returned by Config::getDefault().
    *
    * @param  IConfig $configuration
    */
   public static function setDefault(IConfig $configuration) {
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
    * Handle clones (public but can't be called).
    */
   public function __clone() {
      throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
      // TODO: update cache id or disable caching of this instance
   }


   /**
    * Return an informative text describing the instance.
    *
    * @return string
    */
   public function info() {
      return __METHOD__.'()  not yet implemented';
   }
}
