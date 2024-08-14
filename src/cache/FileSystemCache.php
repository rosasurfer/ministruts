<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\cache;

use rosasurfer\ministruts\cache\monitor\Dependency;
use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\file\FileSystem as FS;

use function rosasurfer\ministruts\isRelativePath;
use function rosasurfer\ministruts\strEndsWith;

use function rosasurfer\ministruts\realpath;


/**
 * FileSystemCache
 *
 * A chache storing objects in the file system.
 *
 * @todo  store values in an additional wrapper object and process parameters, CREATED, EXPIRES, DEPENDENCY
 */
final class FileSystemCache extends CachePeer {


    /** @var string - filepath of the chaching directory */
    private string $directory;


    /**
     * Constructor
     *
     * @param  string               $label              - cache identifier (namespace)
     * @param  array<string, mixed> $options [optional] - additional instantiation options (default: none)
     */
    public function __construct($label, array $options = []) {
        $this->label     = $label;
        $this->namespace = $label;
        $this->options   = $options;

        /** @var Config $config */
        $config = $this->di('config');

        // determine the cache directory to use
        /** @var ?string $directory */
        $directory = $options['directory'] ?? $config['app.dir.cache'] ?? null;
        if (!isset($directory)) throw new RuntimeException('Missing cache instantiation option "directory"');

        if (isRelativePath($directory)) {
            $directory = $config['app.dir.root'].'/'.$directory;
        }

        // make sure the directory exists
        FS::mkDir($directory);

        $this->directory = realpath($directory).DIRECTORY_SEPARATOR;
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $key
     *
     * @return bool
     */
    public function isCached($key) {
        // The actual working horse. This method does not only check the key's existence, it also retrieves the value and
        // stores it in the local reference pool. Thus following cache queries can use the local reference.

        // check local reference pool
        if ($this->getReferencePool()->isCached($key)) {
            return true;
        }

        // find and read a stored file
        $file = $this->getFilePath($key);
        if (!is_file($file)) return false;  // cache miss

        $data = $this->readFile($file);

        // cache hit
        /** @var int $created */
        $created    = $data[0];             // data: [created, $expires, $value, $dependency]
        /** @var int $expires */
        $expires    = $data[1];
        $value      = $data[2];
        $dependency = $data[3];

        // check expiration
        if ($expires && $created+$expires < time()) {
            $this->drop($key);
            return false;
        }

        // check dependency
        if ($dependency) {
            $minValid = $dependency->getMinValidity();

            if ($minValid) {
                if (time() > $created+$minValid) {
                    if (!$dependency->isValid()) {
                        $this->drop($key);
                        return false;
                    }
                    // reset creation time by writing back to the cache (resets $minValid period)
                    return $this->set($key, $value, $expires, $dependency);
                }
            }
            elseif (!$dependency->isValid()) {
                $this->drop($key);
                return false;
            }
        }

        // store the validated value in the local reference pool
        $this->getReferencePool()->set($key, $value, Cache::EXPIRES_NEVER, $dependency);
        return true;
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @param  mixed  $default [optional]
     *
     * @return mixed
     */
    public function get($key, $default = null) {
        if ($this->isCached($key))
            return $this->getReferencePool()->get($key);
        return $default;
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $key
     *
     * @return bool
     */
    public function drop($key) {
        $fileName = $this->getFilePath($key);

        if (is_file($fileName)) {
            if (unlink($fileName)) {
                clearstatcache();
                $this->getReferencePool()->drop($key);
                return true;
            }
            throw new RuntimeException('Cannot delete file: '.$fileName);
        }
        return false;
    }


    /**
     * {@inheritdoc}
     *
     * @param  string      $key
     * @param  mixed       $value
     * @param  int         $expires    [optional]
     * @param  ?Dependency $dependency [optional]
     *
     * @return bool
     */
    public function set($key, $value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
        Assert::string($key,  '$key');
        Assert::int($expires, '$expires');

        // stored data: [created, expires, value, dependency]
        $created = time();

        $file = $this->getFilePath($key);
        $this->writeFile($file, [$created, $expires, $value, $dependency], $expires);

        $this->getReferencePool()->set($key, $value, $expires, $dependency);
        return true;
    }


    /**
     * Return the filepath in the cache for the specified key.
     *
     * @param  string $key - key
     *
     * @return string - filepath
     */
    private function getFilePath($key) {
        $key = md5($key);
        return $this->directory.$key[0].DIRECTORY_SEPARATOR.$key[1].DIRECTORY_SEPARATOR.substr($key, 2);
    }


    /**
     * Read the specified file and return the deserialized content.
     *
     * @param  string $fileName - full filepath
     *
     * @return mixed - content
     */
    private function readFile(string $fileName) {
        $data = file_get_contents($fileName, false);
        return unserialize($data);
    }


    /**
     * Write the given value to the specified file.
     *
     * @param  string $fileName - full filepath
     * @param  mixed  $value    - value to store
     * @param  int    $expires  - expiration time in seconds for automatic invalidation (default: never)
     *
     * @return bool - success status
     */
    private function writeFile($fileName, $value, $expires) {
        FS::mkDir(dirname($fileName));
        file_put_contents($fileName, serialize($value));

        // TODO: https://phpdevblog.niknovo.com/2009/11/serialize-vs-var-export-vs-json-encode.html
        return true;
    }
}
