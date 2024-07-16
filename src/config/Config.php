<?php
namespace rosasurfer\config;

use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidValueException;
use rosasurfer\core\exception\RuntimeException;

use function rosasurfer\isRelativePath;
use function rosasurfer\stderr;
use function rosasurfer\strContains;
use function rosasurfer\strIsQuoted;
use function rosasurfer\strLeft;
use function rosasurfer\strStartsWith;

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
 *  db.connector = mysql                         # named key notation creates associative property branches
 *  db.host      = localhost:3306
 *  db.database  = dbname
 *
 *  db.options[] = value at index 0              # bracket notation creates indexed property branches
 *  db.options[] = value at index 1
 *  db.options[] = value at index 2
 *
 *  # a comment on its own line
 *  log.level.Action          = warn             # a trailing comment
 *  log.level.foo\bar\MyClass = notice           # subkeys may contain any character except the dot "."
 *
 *  key.subkey with spaces    = value            # subkeys may contain spaces...
 *  key.   indented.subkey    = value            # ...but enclosing white space is ignored
 *  key."a.subkey.with.dots"  = value            # quoted subkeys can contain otherwise illegal key characters (i.e. dots)
 *  key                       = value            # the root value of an array is accessed via an empty subkey: key.""
 *
 *  &lt;?php
 *  $config = Application::getConfig();
 *  $config->get('db.connector')                 # return a single value
 *  $config->get('db')                           # return an associative array of values ['connector'=>..., 'host'=>...]
 *  $config->get('db.options')                   # return a numerical indexed array of values [0=>..., 1=>..., 2=>...]
 * </pre>
 */
class Config extends CObject implements ConfigInterface {


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
        if (is_string($files)) $files = [$files];
        else Assert::isArray($files);

        $this->files = [];

        // check and load existing files
        foreach ($files as $i => $file) {
            Assert::string($file, '$files['.$i.']');

            $isFile = is_file($file);
            if      ($isFile)               $file = realpath($file);
            else if (isRelativePath($file)) $file = getcwd().PATH_SEPARATOR.$file;

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
        $lines = file($filename, FILE_IGNORE_NEW_LINES);            // don't use FILE_SKIP_EMPTY_LINES to have correct line
                                                                    // numbers for error messages
        if ($lines && strStartsWith($lines[0], "\xEF\xBB\xBF")) {
            $lines[0] = substr($lines[0], 3);                       // detect and drop a possible BOM header
        }

        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (!strlen($line) || $line[0]=='#')                    // skip empty and comment lines
                continue;

            $parts = explode('=', $line, 2);                        // split key/value
            if (sizeof($parts) < 2) {
                // Don't trigger a regular error as it will cause an infinite loop if the same config is used by the error handler.
                $msg = __METHOD__.'()  Skipping syntax error in "'.$filename.'", line '.($i+1).': missing key-value separator';
                stderr($msg.NL);
                error_log($msg, ERROR_LOG_DEFAULT);
                continue;
            }
            $key      = trim($parts[0]);
            $rawValue = trim($parts[1]);

            // drop possible comments
            if (strpos($rawValue, '#')!==false && strlen($comment=$this->getLineComment($rawValue))) {
                $value = trim(strLeft($rawValue, -strlen($comment)));
            }
            else {
                $value = $rawValue;
            }

            // parse and store property value
            $this->setProperty($key, $value);
        }
        return true;
    }


    /**
     * Resolve the line comment of a raw configuration setting. The only supported comment separator is the hash "#".
     *
     * @param  string $value
     *
     * @return string - line comment or an empty string if the line has no comment
     */
    private function getLineComment($value) {
        error_clear_last();
        $tokens = token_get_all('<?php '.$value);
        $error  = error_get_last();

        // Don't use trigger_error() as it will enter an infinite loop if the same config is accessed again.
        if ($error && !strStartsWith($error['message'], 'Unterminated comment starting line')) {                // that's /*...
            error_log(__METHOD__.'()  Unexpected token_get_all() error for $value: '.$value, ERROR_LOG_DEFAULT);
        }

        $lastToken = end($tokens);

        if (!is_array($lastToken) || token_name($lastToken[0])!='T_COMMENT')
            return '';

        $comment = $lastToken[1];
        if ($comment[0] == '#')
            return $comment;

        return $this->{__FUNCTION__}(substr($comment, 1));
    }


    /**
     * Return the names of the monitored configuration files. The resulting array will contain names of existing and (still)
     * non-existing files.
     *
     * @return bool[] - array with elements "file-name" => (bool)status or an empty array if the configuration is not
     *                  based on files
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
        Assert::string($key, '$key');
        // TODO: a numerically indexed property array will have integer keys

        $notFound = false;
        $value = $this->getProperty($key, $notFound);

        if ($notFound) {
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
     *                   bool $default            - default value to return if the setting does not exist <br>
     *                   int  $flags              - flags as supported by <tt>filter_var($var, FILTER_VALIDATE_BOOLEAN)</tt>: <br>
     *                                              FILTER_NULL_ON_FAILURE - return NULL instead of FALSE on failure <br>
     *                  array $options            - multiple options are passed as elements of an array: <br>
     *                                              <tt>$options[              <br>
     *                                                  'default' => $default, <br>
     *                                                  'flags'   => $flags    <br>
     *                                              ]</tt>                     <br>
     * @return bool? - boolean value or NULL if the flag FILTER_NULL_ON_FAILURE is set and the setting does not represent
     *                 a boolean value
     *
     * @throws RuntimeException if the setting does not exist and no default value was specified
     */
    public function getBool($key, $options = null) {
        Assert::string($key, '$key');
        $notFound = false;
        $value = $this->getProperty($key, $notFound);

        if ($notFound) {
            if (is_bool($options))
                return $options;
            if (is_array($options) && \key_exists('default', $options))
                return (bool) $options['default'];
            throw new RuntimeException('No configuration found for key "'.$key.'"');
        }

        $flags = 0;
        if (is_int($options)) {
            $flags = $options;
        }
        else if (is_array($options) && \key_exists('flags', $options)) {
            Assert::int($options['flags'], 'option "flags"');
            $flags = $options['flags'];
        }
        if ($flags & FILTER_NULL_ON_FAILURE && ($value===null || $value===''))  // crap-PHP considers NULL and '' as valid strict booleans
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
        Assert::string($key, '$key');
        $this->setProperty($key, $value);
        return $this;
    }


    /**
     * Look-up a property and return its value.
     *
     * @param  string $key      - property key
     * @param  bool   $notFound - reference to a flag indicating whether the property was found
     *
     * @return mixed - Property value (including NULL) or NULL if no such property was found. If NULL is returned the flag
     *                 $notFound must be checked to find out whether the property was found.
     */
    protected function getProperty($key, &$notFound) {
        $properties  = $this->properties;
        $subkeys     = $this->parseSubkeys(strtolower($key));
        $subKeysSize = sizeof($subkeys);
        $notFound    = false;

        for ($i=0; $i < $subKeysSize; ++$i) {
            $subkey = trim($subkeys[$i]);
            if (!is_array($properties) || !\key_exists($subkey, $properties))
                break;                                      // not found
            if ($i+1 == $subKeysSize)                       // return at the last subkey
                return $properties[$subkey];
            $properties = $properties[$subkey];             // go to the next sublevel
        }

        $notFound = true;
        return null;
    }


    /**
     * Set/modify the property with the specified key.
     *
     * @param  string $key
     * @param  mixed  $value
     */
    protected function setProperty($key, $value) {
        // convert string representations of special values
        if (is_string($value)) {
            switch (strtolower($value)) {
                case  'null' :
                case '(null)':
                    $value = null;
                    break;

                case  'true' :
                case '(true)':
                case  'on'   :
                case  'yes'  :
                    $value = true;
                    break;

                case  'false' :
                case '(false)':
                case  'off'   :
                case  'no'    :
                    $value = false;
                    break;

                default:
                    if (strIsQuoted($value)) {
                        $value = substr($value, 1, strlen($value)-2);
                    }
            }
        }

        // set the property depending on the existing data structure
        $properties  = &$this->properties;
        $subkeys     =  $this->parseSubkeys(strtolower($key));
        $subkeysSize =  sizeof($subkeys);

        for ($i=0; $i < $subkeysSize; ++$i) {
            $subkey = trim($subkeys[$i]);
            if (!strlen($subkey)) throw new InvalidValueException('Invalid parameter $key: '.$key);

            if ($i+1 < $subkeysSize) {
                // not yet the last subkey
                if (!\key_exists($subkey, $properties)) {
                    $properties[$subkey] = [];                              // create another array level
                }
                elseif (!is_array($properties[$subkey])) {
                    $properties[$subkey] = ['' => $properties[$subkey]];    // create another array level and keep the
                }                                                           // existing non-array value
                $properties = &$properties[$subkey];                        // reference the new array level
            }
            else {
                // the last subkey: check for bracket notation
                $match = null;
                if (preg_match('/(.+)\b *\[ *\]$/', $subkey, $match)) {
                    // bracket notation
                    $subkey = $match[1];
                    if (!\key_exists($subkey, $properties)) {
                        $properties[$subkey] = [$value];                    // create a new array value
                    }
                    else {
                        if (!is_array($properties[$subkey]))                // make the existing value the array root value
                            $properties[$subkey] = ['' => $properties[$subkey]];
                        $properties[$subkey][] = $value;                    // add an array value
                    }
                }
                else {
                    // regular non-bracket notation
                    if (!\key_exists($subkey, $properties)) {
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
                        $properties[$subkey][''] = $value;                  // set/override the array root value
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
                if (strpos($k, $char) === 0) {          // subkey starts with a quote char
                    $pos = strpos($k, $char, 1);        // find the ending quote char
                    if ($pos === false) throw new InvalidValueException('Invalid parameter $key: '.$key);
                    $subkeys[] = substr($k, 1, $pos-1);
                    $k         = trim(substr($k, $pos+1));
                    if (!strlen($k))                    // last subkey or next char is a key separator
                        break 2;
                    if (strpos($k, '.') !== 0) throw new InvalidValueException('Invalid parameter $key: '.$key);
                    $k = substr($k, 1);
                    continue 2;
                }
            }

            // key is not quoted
            $pos = strpos($k, '.');                     // find next key separator
            if ($pos === false) {
                $subkeys[] = $k;                        // last subkey
                break;
            }
            $subkeys[] = trim(substr($k, 0, $pos));
            $k         = substr($k, $pos+1);            // next subkey
        }
        return $subkeys;
    }


    /**
     * Return a plain text dump of the instance's preferences.
     *
     * @param  array $options [optional] - array with dump options: <br>
     *                                     'sort'     => SORT_ASC|SORT_DESC (default: unsorted) <br>
     *                                     'pad-left' => string             (default: no padding) <br>
     * @return string
     */
    public function dump(array $options = null) {
        $lines = [];
        $maxKeyLength = 0;
        $values = $this->dumpNode([], $this->properties, $maxKeyLength);

        if (isset($options['sort'])) {
            if ($options['sort'] == SORT_ASC) {
                ksort($values, SORT_NATURAL);
            }
            else if ($options['sort'] == SORT_DESC) {
                krsort($values, SORT_NATURAL);
            }
        }

        foreach ($values as $key => &$value) {
            // convert special values to their string representation
            if     (!isset($value))  $value = '(null)';
            elseif (is_bool($value)) $value = ($value ? '(true)' : '(false)');
            elseif (is_string($value)) {
                switch (strtolower($value)) {
                    case  'null'  :
                    case '(null)' :
                    case  'true'  :
                    case '(true)' :
                    case  'false' :
                    case '(false)':
                    case  'on'    :
                    case  'off'   :
                    case  'yes'   :
                    case  'no'    :
                        $value = '"'.$value.'"';
                        break;
                    default:
                        if (strContains($value, '#'))
                            $value = '"'.$value.'"';
                }
            }
            $value = str_pad($key, $maxKeyLength, ' ', STR_PAD_RIGHT).' = '.$value;
        }
        unset($value);
        $lines += $values;

        $padLeft = isset($options['pad-left']) ? $options['pad-left'] : '';

        return $padLeft.join(NL.$padLeft, $lines);
    }


    /**
     * Dump the tree structure of a node into a flat format and return it.
     *
     * @param  string[] $node         [in ]
     * @param  array    $values       [in ]
     * @param  int      $maxKeyLength [out]
     *
     * @return array
     */
    private function dumpNode(array $node, array $values, &$maxKeyLength) {
        $result = [];

        foreach ($values as $subkey => $value) {
            if ($subkey==trim($subkey) && (strlen($subkey) || sizeof($values) > 1)) {
                $sSubkey = $subkey;
            }
            else {
                $sSubkey = '"'.$subkey.'"';
            }
            $key          = join('.', \array_merge($node, $sSubkey=='' ? [] : [$sSubkey]));
            $maxKeyLength = max(strlen($key), $maxKeyLength);

            if (is_array($value)) {
                $result += $this->{__FUNCTION__}(\array_merge($node, [$subkey]), $value, $maxKeyLength);
            }
            else {
                $result[$key] = $value;
            }
        }
        return $result;
    }



    /**
     * Return an array with "key-value" pairs of the config settings.
     *
     * @param  array $options [optional] - array with export options: <br>
     *                                     'sort' => SORT_ASC|SORT_DESC (default: unsorted) <br>
     * @return string[]
     */
    public function export(array $options = null) {
        $maxKeyLength = null;
        $values = $this->dumpNode([], $this->properties, $maxKeyLength);

        if (isset($options['sort'])) {
            if ($options['sort'] == SORT_ASC) {
                ksort($values, SORT_NATURAL);
            }
            else if ($options['sort'] == SORT_DESC) {
                krsort($values, SORT_NATURAL);
            }
        }

        foreach ($values as &$value) {
            // convert special values to their string representation
            if     (!isset($value))  $value = '(null)';
            elseif (is_bool($value)) $value = ($value ? '(true)' : '(false)');
            elseif (is_string($value)) {
                switch (strtolower($value)) {
                    case  'null'  :
                    case '(null)' :
                    case  'true'  :
                    case '(true)' :
                    case  'false' :
                    case '(false)':
                    case  'on'    :
                    case  'off'   :
                    case  'yes'   :
                    case  'no'    :
                        $value = '"'.$value.'"';
                        break;
                    default:
                        if (strContains($value, '#'))
                            $value = '"'.$value.'"';
                }
            }
        }
        unset($value);

        return $values;
    }


    /**
     * Whether a config setting with the specified key exists.
     *
     * @param  string $key - case-insensitive key
     *
     * @return bool
     */
    public function offsetExists($key) {
        Assert::string($key);
        $notFound = false;
        $this->getProperty($key, $notFound);

        return !$notFound;
    }


    /**
     * Return the config setting with the specified key.
     *
     * @param  string $key - case-insensitive key
     *
     * @return mixed - config setting
     *
     * @throws RuntimeException if the setting does not exist
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


    /**
     * Count the root properties of the configuration.
     *
     * @return int
     */
    public function count() {
        return sizeof($this->properties);
    }
}
