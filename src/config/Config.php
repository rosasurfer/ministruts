<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\config;

use ReturnTypeWillChange;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\RuntimeException;

use function rosasurfer\ministruts\isRelativePath;
use function rosasurfer\ministruts\preg_match;
use function rosasurfer\ministruts\realpath;
use function rosasurfer\ministruts\stderr;
use function rosasurfer\ministruts\strContains;
use function rosasurfer\ministruts\strIsQuoted;
use function rosasurfer\ministruts\strLeft;
use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\ERROR_LOG_DEFAULT;
use const rosasurfer\ministruts\NL;
use const rosasurfer\ministruts\CLI;

/**
 * A configuration mechanism using Java-like property files.
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

    /** @var array<string, bool> - config file names and their existence status */
    protected array $files = [];

    /** @var array<(?scalar)|array<?scalar>> - tree structure of config values */
    protected array $properties = [];


    /**
     * Constructor
     *
     * Create a new instance and load the specified property files. Settings of all files are merged, later settings
     * override earlier (already existing) ones.
     *
     * @param  string[] $files - one or more configuration file names
     */
    public function __construct(array $files) {
        $this->files = [];

        // check and load existing files
        foreach ($files as $file) {
            $success = false;
            if (is_file($file)) {
                $success = $this->loadFile($file);
            }
            elseif (isRelativePath($file)) {
                $file = getcwd().PATH_SEPARATOR.$file;
            }
            $this->files[$file] = $success;
        }
    }


    /**
     * Create a new instance with a standard set of configuration files from a directory in the following order:
     *
     *  - a versioned/distributable configuration: "config.dist.properties"
     *  - a CLI specific configuration if on CLI:  "config.cli.properties"
     *  - one custom configuration, either
     *    (1) a custom file if specified or:       parameter $location
     *    (2) an enviroment specific file or:      "config.$_SERVER[APP_ENVIRONMENT].properties"
     *    (3) the default custom configuration:    "config.properties"
     *
     * New config settings override existing settings with the same key.
     *
     * @param  string $location - Directory or a custom configuration file. The directory part of $location is used
     *                            for all non-custom configuration files.
     * @return static
     */
    public static function createFrom(string $location): self {
        // collect applicable config files
        $configDir = $configFile = null;

        if (is_file($location)) {
            $configFile = realpath($location);
            $configDir = dirname($configFile);
        }
        elseif (is_dir($location)) {
            $configDir = realpath($location);
        }
        else throw new InvalidValueException('Location not found: "'.$location.'"');

        // versioned/distributable config file
        $files = [];
        $files[] = $configDir.'/config.dist.properties';

        // runtime environment
        if (CLI) $files[] = $configDir.'/config.cli.properties';

        // application environment: user or staging configuration
        if ($configFile) {
            $files[] = $configFile;                                 // explicit
        }
        else {
            /** @var string $appEnv */
            $appEnv = $_SERVER['APP_ENVIRONMENT'] ?? '';
            if (strlen($appEnv)) {
                $files[] = $configDir."/config.$appEnv.properties";
            }
            else {
                $files[] = $configDir.'/config.properties';         // default
            }
        }

        // load all files (do not pass a provided $baseDir but apply it manually in the next step)
        $instance = new static($files);

        // set "app.dir.config" to the directory of the most recently added file
        end($instance->files);
        /** @var string $file */
        $file = key($instance->files);
        $instance->set('app.dir.config', dirname($file));

        // create FileDependency and cache the instance
        //$dependency = FileDependency::create(\array_keys($instance->files));
        //$dependency->setMinValidity(60 * SECONDS);
        //$cache->set('config', $config, Cache::EXPIRES_NEVER, $dependency);

        return $instance;
    }


    /**
     * Load a single properties file. New settings overwrite existing ones.
     *
     * @param  string $filename
     *
     * @return bool - success status
     */
    protected function loadFile(string $filename): bool {
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
            if (strContains($rawValue, '#') && strlen($comment = $this->getLineComment($rawValue))) {
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
    private function getLineComment(string $value): string {
        error_clear_last();
        $tokens = token_get_all('<?php '.$value);
        $error = error_get_last();

        // Don't use trigger_error() as it will enter an infinite loop if the same config is accessed again.
        if ($error && !strStartsWith($error['message'], 'Unterminated comment starting line')) {                // that's /*...
            error_log(__METHOD__.'()  Unexpected token_get_all() error for $value: '.$value, ERROR_LOG_DEFAULT);
        }

        $lastToken = end($tokens);

        if (!is_array($lastToken) || token_name($lastToken[0])!='T_COMMENT') {
            return '';
        }
        $comment = $lastToken[1];
        if ($comment[0] == '#') {
            return $comment;
        }
        return $this->getLineComment(substr($comment, 1));
    }


    /**
     * {@inheritDoc}
     */
    public function getConfigFiles(): array {
        return $this->files;
    }


    /**
     * {@inheritDoc}
     */
    public function get(string $key, $default = null) {
        $notFound = false;
        $value = $this->getProperty($key, $notFound);

        if ($notFound) {
            if (func_num_args() == 1) throw new RuntimeException("Config key '$key' not found (no default value specified)");
            return $default;
        }
        return $value;
    }


    /**
     * {@inheritDoc}
     */
    public function getBool(string $key, bool $strict=false, bool $default=false): bool {
        $notFound = false;
        $value = $this->getProperty($key, $notFound);

        if ($notFound) {
            // key not found: return a passed default value
            if (func_num_args() > 2) {
                return $default;
            }
            throw new RuntimeException("Config key '$key' not found (no default value specified)");
        }

        // key found: validate it as a boolean
        $flags = 0;
        if ($strict) {
            if ($value===null || $value==='') {     // filter_var() considers NULL and an empty string '' as valid strict booleans
                throw new RuntimeException("Invalid type of config key '$key': ".gettype($value).' (boolean expected)');
            }
            $flags = FILTER_NULL_ON_FAILURE;
        }
        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, $flags);
        if ($result === null) {                     // @phpstan-ignore identical.alwaysFalse (PHPStan doesn't see flag FILTER_NULL_ON_FAILURE)
            throw new RuntimeException("Invalid type of config key '$key': ".gettype($value).' (boolean expected)');
        }
        return $result;
    }


    /**
     * {@inheritDoc}
     */
    public function getString(string $key, string $default = ''): string {
        $notFound = false;
        $value = $this->getProperty($key, $notFound);

        if ($notFound) {
            // key not found: return a passed default value
            if (func_num_args() > 1) {
                return $default;
            }
            throw new RuntimeException("Config key '$key' not found (no default value specified)");
        }

        // key found
        if (is_scalar($value)) {
            return (string) $value;
        }
        throw new RuntimeException("Invalid type of config key '$key': ".gettype($value).' (scalar expected)');
    }


    /**
     * {@inheritDoc}
     *
     * @param  int|string $key - case-insensitive key
     */
    public function set($key, $value): self {
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
    protected function getProperty(string $key, bool &$notFound) {
        $properties  = $this->properties;
        $subkeys     = $this->parseSubkeys(strtolower($key));
        $subKeysSize = sizeof($subkeys);
        $notFound    = false;

        for ($i=0; $i < $subKeysSize; ++$i) {
            $subkey = trim($subkeys[$i]);
            if (!is_array($properties) || !\key_exists($subkey, $properties)) {
                break;                                      // not found
            }
            if ($i+1 == $subKeysSize) {
                return $properties[$subkey];                // return at the last subkey
            }
            $properties = $properties[$subkey];             // go to the next sublevel
        }

        $notFound = true;
        return null;
    }


    /**
     * Set/modify the property with the specified key.
     *
     * @param  int|string $key
     * @param  mixed      $value
     *
     * @return void
     */
    protected function setProperty($key, $value): void {
        $key = (string) $key;

        // convert string representations of special values
        if (is_string($value)) {
            switch (strtolower($value)) {
                case 'null':
                case '(null)':
                    $value = null;
                    break;

                case 'true':
                case '(true)':
                case 'on':
                case 'yes':
                    $value = true;
                    break;

                case 'false':
                case '(false)':
                case 'off':
                case 'no':
                    $value = false;
                    break;

                default:
                    if (strIsQuoted($value)) {
                        $value = substr($value, 1, strlen($value)-2);
                    }
            }
        }

        // set the property depending on the existing data structure
        $properties = &$this->properties;
        $subkeys = $this->parseSubkeys(strtolower($key));
        $subkeysSize = sizeof($subkeys);

        for ($i=0; $i < $subkeysSize; ++$i) {
            $subkey = trim($subkeys[$i]);
            if (!strlen($subkey)) throw new InvalidValueException("Invalid parameter \$key: $key");

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
                $result = preg_match('/(.+)\b *\[ *\]$/', $subkey, $match);

                if ($result) {
                    // bracket notation
                    $subkey = $match[1];
                    if (!\key_exists($subkey, $properties)) {
                        $properties[$subkey] = [$value];                    // create a new array value
                    }
                    else {
                        if (!is_array($properties[$subkey])) {              // make the existing value the array root value
                            $properties[$subkey] = ['' => $properties[$subkey]];
                        }
                        $properties[$subkey][] = $value;                    // add an array value
                    }
                }
                else {
                    // regular non-bracket notation
                    if (!\key_exists($subkey, $properties)) {
                        $properties[$subkey] = $value;                      // store the value regularily
                    }
                    elseif (!is_array($properties[$subkey])) {
                        $properties[$subkey] = $value;                      // override the existing value
                    }

                    // modification of an array value
                    elseif ($value === null) {
                        $properties[$subkey] = $value;                      // set the array to NULL, don't remove the key
                    }
                    elseif (is_array($value)) {
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
    protected function parseSubkeys(string $key): array {
        $k = $key;
        $subkeys = [];
        $quoteChars = ["'", '"'];                       // single and double quotes

        while (true) {
            $k = trim($k);

            foreach ($quoteChars as $char) {
                if (strpos($k, $char) === 0) {          // subkey starts with a quote char
                    $pos = strpos($k, $char, 1);        // find the ending quote char
                    if ($pos === false) throw new InvalidValueException("Invalid parameter \$key: $key");
                    $subkeys[] = substr($k, 1, $pos-1);
                    $k = trim(substr($k, $pos+1));
                    if (!strlen($k)) break 2;           // last subkey or next char is a key separator

                    if (strpos($k, '.') !== 0) throw new InvalidValueException("Invalid parameter \$key: $key");
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
            $k = substr($k, $pos+1);                    // next subkey
        }
        return $subkeys;
    }


    /**
     * {@inheritDoc}
     */
    public function dump(array $options = []): string {
        $lines = [];
        $maxKeyLength = 0;
        $values = $this->dumpNode([], $this->properties, $maxKeyLength);

        if (isset($options['sort'])) {
            if ($options['sort'] == SORT_ASC) {
                ksort($values, SORT_NATURAL);
            }
            elseif ($options['sort'] == SORT_DESC) {
                krsort($values, SORT_NATURAL);
            }
        }

        foreach ($values as $key => &$value) {
            // convert special values to their string representation
            if     (!isset($value))  $value = '(null)';
            elseif (is_bool($value)) $value = ($value ? '(true)' : '(false)');
            elseif (is_string($value)) {
                switch (strtolower($value)) {
                    case 'null':
                    case '(null)':
                    case 'true':
                    case '(true)':
                    case 'false':
                    case '(false)':
                    case 'on':
                    case 'off':
                    case 'yes':
                    case 'no':
                        $value = "\"$value\"";
                        break;
                    default:
                        if (strContains($value, '#')) {
                            $value = "\"$value\"";
                        }
                }
            }
            $value = str_pad($key, $maxKeyLength, ' ', STR_PAD_RIGHT)." = $value";
        }
        unset($value);
        $lines += $values;

        $padLeft = $options['pad-left'] ?? '';

        return $padLeft.join(NL.$padLeft, $lines);
    }


    /**
     * Dump the tree structure of a node into a flat format and return it.
     *
     * @param  string[]                        $node         [in ]
     * @param  array<(?scalar)|array<?scalar>> $values       [in ]
     * @param  int                             $maxKeyLength [out]
     *
     * @return array<string, ?scalar>
     */
    private function dumpNode(array $node, array $values, int &$maxKeyLength): array {
        $result = [];

        foreach ($values as $subkey => $value) {
            $subkey = (string)$subkey;

            if ($subkey==trim($subkey) && (strlen($subkey) || sizeof($values) > 1)) {
                $sSubkey = $subkey;
            }
            else {
                $sSubkey = "\"$subkey\"";
            }
            $key = join('.', \array_merge($node, $sSubkey=='' ? [] : [$sSubkey]));
            $maxKeyLength = max(strlen($key), $maxKeyLength);

            if (is_array($value)) {             // recursion
                $result += $this->dumpNode(\array_merge($node, [$subkey]), $value, $maxKeyLength);
            }
            else {
                $result[$key] = $value;
            }
        }
        return $result;
    }


    /**
     * {@inheritDoc}
     */
    public function export(array $options = []): array {
        $maxKeyLength = 0;
        $values = $this->dumpNode([], $this->properties, $maxKeyLength);

        if (isset($options['sort'])) {
            if ($options['sort'] == SORT_ASC) {
                ksort($values, SORT_NATURAL);
            }
            elseif ($options['sort'] == SORT_DESC) {
                krsort($values, SORT_NATURAL);
            }
        }

        foreach ($values as &$value) {
            // convert special values to their string representation
            if     (!isset($value))    $value = '(null)';
            elseif (is_bool($value))   $value = ($value ? '(true)' : '(false)');
            elseif (is_int($value))    $value = (string) $value;
            elseif (is_float($value))  $value = (string) $value;
            elseif (is_string($value)) {
                switch (strtolower($value)) {
                    case 'null':
                    case '(null)':
                    case 'true':
                    case '(true)':
                    case 'false':
                    case '(false)':
                    case 'on':
                    case 'off':
                    case 'yes':
                    case 'no':
                        $value = "\"$value\"";
                        break;
                    default:
                        if (strContains($value, '#')) {
                            $value = "\"$value\"";
                        }
                }
            }
        }
        unset($value);

        /** @var array<string, string> $values */
        $values = $values;
        return $values;
    }


    /**
     * Whether a config setting with the specified key exists.
     *
     * {@inheritDoc}
     */
    public function offsetExists($key): bool {
        $notFound = false;
        $this->getProperty($key, $notFound);
        return !$notFound;
    }


    /**
     * Return the config setting with the specified key.
     *
     * {@inheritDoc}
     *
     * @throws RuntimeException if the setting does not exist
     */
    #[ReturnTypeWillChange]
    public function offsetGet($key) {
        return $this->get($key);
    }


    /**
     * Set/modify the config setting with the specified key.
     *
     * @param  int|string $key - case-insensitive key
     *
     * {@inheritDoc}
     */
    public function offsetSet($key, $value): void {
        $this->set($key, $value);
    }


    /**
     * Unset the config setting with the specified key.
     *
     * {@inheritDoc}
     */
    public function offsetUnset($key): void {
        $this->set($key, null);
    }


    /**
     * Count the root properties of the configuration.
     *
     * {@inheritDoc}
     */
    public function count(): int {
        return sizeof($this->properties);
    }
}
