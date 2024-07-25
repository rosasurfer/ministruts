<?php
namespace rosasurfer\cache\monitor;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidValueException;

use function rosasurfer\isRelativePath;


/**
 * FileDependency - dependence on the last time a file was changed.
 *
 * The dependency is fulfilled if the state of the file has not changed since the last call. Dependence on a non-existent
 * file is also possible; in this case the dependency is fulfilled as long as the file still does not exist.
 *
 * @example
 * <pre>
 *  &lt;?php
 *  $dependency = new FileDependency('/etc/crontab');
 *
 *  // ...
 *
 *  if (!$dependency->isValid()) {
 *      // file state has changed, trigger some action...
 *  }
 * </pre>
 *
 * The example defines a dependency on the modification time of the file '/etc/crontab'. As long as the file is not changed,
 * the dependency remains fulfilled and calling $dependency->isValid() returns TRUE. After changing or deleting the file,
 * calling $dependency->isValid() returns FALSE.
 *
 * @phpstan-consistent-constructor
 */
class FileDependency extends Dependency {


    /** @var string - name of the monitored file */
    private $fileName;

    /** @var ?int - last modification time of the monitored file (Unix timestamp) */
    private $lastModified = null;


    /**
     * Constructor
     *
     * Create a new dependency monitoring a single file.
     *
     * @param  string $fileName - file name
     */
    public function __construct($fileName) {
        Assert::string($fileName);
        if (!strlen($fileName)) throw new InvalidValueException('Invalid parameter $fileName: '.$fileName);

        if (file_exists($fileName)) {
            $this->fileName = realpath($fileName);
            $this->lastModified = filemtime($this->fileName);
        }
        else {
            $name = str_replace('\\', '/', $fileName);
            if (isRelativePath($name)) {
                $name = getcwd().'/'.$name;     // convert to absolute path
            }
            $this->fileName = str_replace('/', DIRECTORY_SEPARATOR, $name);
            $this->lastModified = null;
        }
    }


    /**
     * Create a new dependency monitoring multiple file.
     *
     * @param  string[] $fileNames - file names
     *
     * @return Dependency
     */
    public static function create(array $fileNames) {
        if (!$fileNames) throw new InvalidValueException('Invalid argument $fileNames (empty)');

        $dependency = null;

        foreach ($fileNames as $name) {
            if (!$dependency) $dependency = new static($name);
            else              $dependency = $dependency->andDependency(new static($name));
        }

        return $dependency;
    }


    /**
     * {@inheritdoc}
     */
    public function isValid() {
        // TODO: reset stat cache on repeated call, @see clearstatcache()

        if (file_exists($this->fileName)) {
            if ($this->lastModified !== filemtime($this->fileName)) {
                return false;
            }
        }
        elseif ($this->lastModified !== null) {
            return false;
        }

        return true;
    }
}
