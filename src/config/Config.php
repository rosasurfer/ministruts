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

use function rosasurfer\isRelativePath;

use const rosasurfer\ERROR_LOG_DEFAULT;
use const rosasurfer\NL;


/**
 * General configuration mechanism via property files.
 *
 * File format: <br>
 * Settings are defined as "key = value" pairs. Enclosing white space and empty lines are ignored. Subkeys can be used to
 * create structures which can be queried as a whole (array) or as single values. Keys are case-insensitive. Config instances
 * can be accessed like arrays.
 *
 * @example
 * <pre>
 * db.connector = mysql                         # subkey notation creates associative array branches
 * db.host      = localhost:3306
 * db.database  = dbname
 *
 * db.options[] = value-at-index-0              # bracket notation creates indexed array branches
 * db.options[] = value-at-index-1
 * db.options[] = value-at-index-2
 *
 * # a comment on its own line
 * log.level.Action          = warn             # a comment at the end of line
 * log.level.foo\bar\MyClass = notice           # subkeys may contain any character except the dot "."
 *
 * key.subkey with spaces    = value            # subkeys may contain spaces...
 * key.   indented.subkey    = value            # ...but enclosing white space is ignored
 * key."a.subkey.with.dots"  = value            # quoted subkeys can contain otherwise illegal key characters
 * key                       = value            # the root value of an array is accessed with an empty subkey: key.""
 *
 * &lt;?php
 * Config::get('db.connector')                  // return a single value
 * Config::get('db')                            // return an associative array of values ['connector'=>..., 'host'=>...]
 * Config::get('db.options')                    // return a numerical indexed array of values [0=>..., 1=>..., 2=>...]
 * </pre>
 */
class Config extends Object implements ConfigInterface {


    /** @var IConfig - the application's current default configuration */
    private static $defaultInstance;

    /** @var bool[] - config file names and their existence status */
    protected $files = [];

    /** @var array - tree structure of config values */
    protected $properties = [];


    /**
     * Constructor
     *
     * Create a new instance and load the specified property files. Settings of all files are merged, later settings
     * override earlier (already existing) ones.
     *
     * @param  string|string[] $files - a single or multiple configuration file names
     */
    public function __construct($files) {
        if      (is_string($files)) $files = [$files];
        else if (!is_array($files)) throw new IllegalTypeException('Illegal type of parameter $files: '.getType($files));

        ini_set('auto_detect_line_endings', true);
        $this->files = [];

        // check and load existing files
        foreach ($files as $i => $file) {
            if (!is_string($file)) throw new IllegalTypeException('Illegal type of parameter $files['.$i.']: '.getType($file));

            $isFile = is_file($file);
            if      ($isFile)               $file = realPath($file);
            else if (isRelativePath($file)) $file = getCwd().PATH_SEPARATOR.$file;

            $this->files[$file] = $isFile && $this->loadFile($file);
        }
    }


    /**
     * Load a single properties file. New settings overwrite existing ones.
     *
     * @param  string $filename
     *
     * @return bool - success status
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
                $msg = __METHOD__.'()  Skipping syntax error in "'.$filename.'", line '.($i+1).': missing key-value separator';
                error_log($msg, ERROR_LOG_DEFAULT);
                //trigger_error($msg, E_USER_NOTICE);   // don't use trigger_error() as it will enter an infinite loop if
                continue;                               // the same file is accessed for reading the Logger configuration
            }
            $key   = trim($parts[0]);
            $value = trim($parts[1]);

            // parse and store property value
            $this->setProperty($key, $value);
        }
        return true;
    }


    /**
     * Return the names of the monitored configuration files. The resulting array will contain names of existing and (still)
     * non-existing files.
     *
     * @return bool[] - array with elements "file-name" => (bool)status or an empty array if the configuration
     *                  is not based on files
     */
    public function getMonitoredFiles() {
        return $this->files;
    }


    /**
     * Return the config setting with the specified key or the default value if no such setting is found.
     *
     * @param  string $key                - case-insensitive key
     * @param  mixed  $default [optional] - default value
     *
     * @return mixed - config setting
     */
    public function get($key, $default = null) {
        if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));

        $value = $this->getProperty($key);

        if ($value === null) {
            if (func_num_args() == 1) throw new RuntimeException('No configuration found for key "'.$key.'"');
            return $default;
        }
        return $value;
    }


    /**
     * Return the config setting with the specified key as a boolean. Accepted boolean value representations are "1" and "0",
     * "true" and "false", "on" and "off", "yes" and "no" (case-insensitive).
     *
     * @param  string         $key                - case-insensitive key
     * @param  bool|int|array $options [optional] - additional options as supported by <tt>filter_var($var, FILTER_VALIDATE_BOOLEAN)</tt>, <br>
     *                                              may be any of: <br>
     *                   bool $default            - default value to return if the setting is not found <br>
     *                   int  $flags              - flags as supported by <tt>filter_var($var, FILTER_VALIDATE_BOOLEAN)</tt>: <br>
     *                                              FILTER_NULL_ON_FAILURE - return NULL instead of FALSE on failure <br>
     *                  array $options            - multiple options are passed as elements of an array: <br>
     *                                              <tt>$options[              <br>
     *                                                  'default' => $default, <br>
     *                                                  'flags'   => $flags    <br>
     *                                              ]</tt>                     <br>
     * @return bool|null - boolean value or NULL if the flag FILTER_NULL_ON_FAILURE is set and the setting does not represent
     *                     a boolean value
     *
     * @throws RuntimeException if the setting is not found and $default was not specified
     */
    public function getBool($key, $options = null) {
        if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));

        $value = $this->getProperty($key);

        if ($value === null) {
            if (is_bool($options))
                return $options;
            if (is_array($options) && array_key_exists('default', $options)) {
                if (!is_bool($options['default'])) throw new IllegalTypeException('Illegal type of option "default": '.getType($options['default']));
                return $options['default'];
            }
            throw new RuntimeException('No configuration found for key "'.$key.'"');
        }

        $flags = 0;
        if (is_int($options)) {
            $flags = $options;
        }
        else if (is_array($options) && array_key_exists('flags', $options)) {
            if (!is_int($options['flags'])) throw new IllegalTypeException('Illegal type of option "flags": '.getType($options['flags']));
            $flags = $options['flags'];
        }
        if ($flags & FILTER_NULL_ON_FAILURE && $value==='')         // crappy PHP considers '' as a valid strict boolean
            return null;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, $flags);
    }


    /**
     * Set/modify the config setting with the specified key.
     *
     * @param  string $key   - case-insensitive key
     * @param  mixed  $value - new value
     *
     * @return $this
     */
    public function set($key, $value) {
        if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
        $this->setProperty($key, $value);
        return $this;
    }


    /**
     * Look-up a property and return its value.
     *
     * @param  string $key
     *
     * @return string|string[]|null - a string, a string array or NULL if no such setting is found
     */
    protected function getProperty($key) {
        $properties  = $this->properties;
        $subkeys     = $this->parseSubkeys(strToLower($key));
        $subKeysSize = sizeOf($subkeys);

        for ($i=0; $i < $subKeysSize; ++$i) {
            $subkey = trim($subkeys[$i]);
            if (!isSet($properties[$subkey]))
                break;                                      // not found
            if ($i+1 == $subKeysSize)                       // return at the last subkey
                return $properties[$subkey];
            $properties = $properties[$subkey];             // go to the next sublevel
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
        $properties  = &$this->properties;
        $subkeys     =  $this->parseSubkeys(strToLower($key));
        $subkeysSize =  sizeOf($subkeys);

        for ($i=0; $i < $subkeysSize; ++$i) {
            $subkey = trim($subkeys[$i]);
            if (!strLen($subkey)) throw new InvalidArgumentException('Invalid argument $key: '.$key);

            if ($i+1 < $subkeysSize) {
                // not yet the last subkey
                if (!isSet($properties[$subkey])) {
                    $properties[$subkey] = [];                              // create another array level
                }
                elseif (!is_array($properties[$subkey])) {
                    $properties[$subkey] = ['' => $properties[$subkey]];    // create another array level and keep the
                }                                                           // existing non-array value
                $properties = &$properties[$subkey];                        // reference the new array level
            }
            else {
                // the last subkey: check for bracket notation
                if (preg_match('/(.+)\b *\[ *\]$/', $subkey, $match)) {
                    // bracket notation
                    $subkey = $match[1];
                    if (!isSet($properties[$subkey])) {
                        $properties[$subkey] = [$value];                    // create a new array value
                    }
                    else {
                        if (!is_array($properties[$subkey]))                // make the existing value the array default value
                            $properties[$subkey] = ['' => $properties[$subkey]];
                        $properties[$subkey][] = $value;                    // add an array value
                    }
                }
                else {
                    // regular non-bracket notation
                    if (!isSet($properties[$subkey])) {
                        $properties[$subkey] = $value;                      // store the value regularily
                    }
                    else if (!is_array($properties[$subkey])) {
                        $properties[$subkey] = $value;                      // override the existing value
                    }

                    // modification of an array value
                    else if ($value === null) {
                        $properties[$subkey] = $value;                      // set the array to NULL, don't remove the key
                    }
                    else if (is_array($value)) {
                        $properties[$subkey] = $value;                      // replace the array
                    }
                    else {
                        $properties[$subkey][''] = $value;                  // set/override the array default value
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
        $quoteChars = ["'", '"'];                       // single and double quotes

        while (true) {
            $k = trim($k);

            foreach ($quoteChars as $char) {
                if (strPos($k, $char) === 0) {          // subkey starts with a quote char
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
            $pos = strPos($k, '.');                     // find next key separator
            if ($pos === false) {
                $subkeys[] = $k;                        // last subkey
                break;
            }
            $subkeys[] = trim(subStr($k, 0, $pos));
            $k         = subStr($k, $pos+1);            // next subkey
        }
        return $subkeys;
    }


    /**
     * Get the current default configuration. This is the configuration set by Config::setDefault().
     *
     * @return IConfig
     */
    public static function getDefault() {
        // intentionally accept an error if $defaultInstance was not yet set
        return self::$defaultInstance;
    }


    /**
     * Set the default configuration to be returned by Config::getDefault().
     *
     * @param  IConfig $configuration
     *
     * @return IConfig - the same configuration
     */
    public static function setDefault(IConfig $configuration) {
        return self::$defaultInstance = $configuration;
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
     * Clone the instance.
     */
    public function __clone() {
        throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
        // TODO: update cache id or disable caching of this instance
    }


    /**
     * Return a dump with the preferences of the instance.
     *
     * @param  array $options [optional] - array with optional dump options:
     *                                     'sort'     => SORT_ASC|SORT_DESC (default: unsorted)
     *                                     'pad-left' => string             (default: no padding)
     * @return string
     */
    public function dump(array $options = null) {
        $lines = [];
        $maxKeyLength = 0;
        $values = $this->dumpNode([], $this->properties, $maxKeyLength);

        if (isSet($options['sort'])) {
            if ($options['sort'] == SORT_ASC) {
                kSort($values);
            }
            else if ($options['sort'] == SORT_DESC) {
                kSort($values);
                $values = array_reverse($values, true);
            }
        }

        foreach ($values as $key => &$value) {
            $value = str_pad($key, $maxKeyLength, ' ', STR_PAD_RIGHT).' = '.$value;
        }; unset($value);
        $lines += $values;

        $padLeft = isSet($options['pad-left']) ? $options['pad-left'] : '';

        return $padLeft.join(NL.$padLeft, $lines);
    }


    /**
     * Dump keys and values of the instance into a human-readable string and return it.
     *
     * @param  string[] $node         [In]
     * @param  array    $values       [In]
     * @param  int     &$maxKeyLength [Out]
     *
     * @return string[]
     */
    private function dumpNode(array $node, array $values, &$maxKeyLength) {
        $self   = __FUNCTION__;
        $result = [];

        foreach ($values as $subkey => $value) {
            if ($subkey==trim($subkey) && (strLen($subkey) || sizeof($values) > 1)) {
                $sSubkey = $subkey;
            }
            else {
                $sSubkey = '"'.$subkey.'"';
            }
            $key          = join('.', array_merge($node, $sSubkey=='' ? [] : [$sSubkey]));
            $maxKeyLength = max(strLen($key), $maxKeyLength);

            if (is_array($value)) {
                $result += $this->$self(array_merge($node, [$subkey]), $value, $maxKeyLength);
            }
            else {
                $result[$key] = $value;
            }
        }
        return $result;
    }


    /**
     * Whether or not a config setting with the specified key exists.
     *
     * @param  string $key - case-insensitive key
     *
     * @return bool
     */
    public function offsetExists($key) {
        if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
        return ($this->getProperty($key) !== null);
    }


    /**
     * Return the config setting with the specified key.
     *
     * @param  string $key - case-insensitive key
     *
     * @return mixed - config setting
     *
     * @throws RuntimeException if the setting is not found
     */
    public function offsetGet($key) {
        return $this->get($key);
    }


    /**
     * Set/modify the config setting with the specified key.
     *
     * @param  string $key   - case-insensitive key
     * @param  mixed  $value - new value
     *
     * @return mixed - new value
     */
    public function offsetSet($key, $value) {
        $this->set($key, $value);
        return $value;
    }


    /**
     * Unset the config setting with the specified key.
     *
     * @param  string $key - case-insensitive key
     */
    public function offsetUnset($key) {
        $this->set($key, null);
    }
}
