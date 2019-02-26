<?php
namespace rosasurfer\cache\monitor;

use rosasurfer\core\assert\Assert;
use rosasurfer\exception\InvalidArgumentException;

use function rosasurfer\isRelativePath;


/**
 * FileDependency
 *
 * Abhaengigkeit vom letzten Aenderungszeitpunkt einer Datei.  Die Abhaengigkeit ist erfuellt, wenn sich
 * der Zustand der Datei seit dem letzten Aufruf nicht geaendert hat.  Auch die Abhaengigkeit von einer
 * nicht existierenden Datei ist moeglich, die Abhaengigkeit ist in diesem Falle solange erfuellt, wie
 * die Datei weiterhin nicht existiert.
 *
 * Anwendungsbeispiel:
 * -------------------
 *
 *    $dependency = new FileDependency('/etc/crontab');
 *    ....
 *
 *    if (!$dependency->isValid()) {
 *       // irgendeine Aktion
 *    }
 *
 * Dieses Beispiel definiert eine Abhaengigkeit vom Aenderungszeitpunkt der Datei '/etc/crontab'.
 * Solange die Datei nicht veraendert wird, bleibt die Abhaengigkeit erfuellt und der Aufruf von
 * $dependency->isValid() gibt TRUE zurueck.  Nach Aenderung oder Loeschen der Datei gibt der Aufruf von
 * $dependency->isValid() FALSE zurueck.
 */
class FileDependency extends Dependency {


    /** @var string - Dateiname */
    private $fileName;

    /** @var int - letzter Aenderungszeitpunkt der Datei (Unix-Timestamp) */
    private $lastModified;


    /**
     * Constructor
     *
     * Erzeugt eine neue FileDependency, die die angegebene Datei ueberwacht.
     *
     * @param  string $fileName - Dateiname
     */
    public function __construct($fileName) {
        Assert::string($fileName);
        if (!strlen($fileName)) throw new InvalidArgumentException('Invalid argument $fileName: '.$fileName);

        if (file_exists($fileName)) {                       // existierende Datei
            $this->fileName     = realpath($fileName);
            $this->lastModified = filemtime($this->fileName);
        }
        else {                                              // nicht existierende Datei
            $name = str_replace('\\', '/', $fileName);
            if (isRelativePath($name))
                $name=getcwd().'/'.$name;      // absoluten Pfad erzeugen, da Arbeitsverzeichnis wechseln kann

            $this->fileName     = str_replace('/', DIRECTORY_SEPARATOR, $name);
            $this->lastModified = null;
        }
    }


    /**
     * Erzeugt eine neue FileDependency, die eine oder mehrere Dateien ueberwacht.
     *
     * @param  mixed $fileNames - einzelner Dateiname (String) oder Array von Dateinamen
     *
     * @return Dependency
     */
    public static function create($fileNames) {
        if (!is_array($fileNames)) {
            Assert::string($fileNames);
            if (!strlen($fileNames)) throw new InvalidArgumentException('Invalid argument $fileNames: '.$fileNames);
            $fileNames = [$fileNames];
        }
        if (!$fileNames) throw new InvalidArgumentException('Invalid argument $fileNames: '.$fileNames);

        $dependency = null;

        foreach ($fileNames as $name) {
            if (!$dependency) $dependency = new static($name);
            else              $dependency = $dependency->andDependency(new static($name));
        }

        return $dependency;
    }


    /**
     * Ob die der Abhaengigkeit zugrunde liegende Datei weiterhin unveraendert ist.
     *
     * @return bool - TRUE, wenn die Datei sich nicht geaendert hat.
     *                FALSE, wenn die Datei sich geaendert hat.
     */
    public function isValid() {
        // TODO: stat-Cache bei wiederholten Aufrufen loeschen, siehe clearstatcache()

        if (file_exists($this->fileName)) {
            if ($this->lastModified !== filemtime($this->fileName))
                return false;
        }
        elseif ($this->lastModified !== null) {
            return false;
        }

        return true;
    }
}
