<?php
namespace rosasurfer\monitor;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use const rosasurfer\WINDOWS;


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

    /** @var int|null - letzter Aenderungszeitpunkt der Datei (Unix-Timestamp) */
    private $lastModified;


    /**
     * Constructor
     *
     * Erzeugt eine neue FileDependency, die die angegebene Datei ueberwacht.
     *
     * @param  string $fileName - Dateiname
     */
    public function __construct($fileName) {
        if (!is_string($fileName)) throw new IllegalTypeException('Illegal type of parameter $fileName: '.getType($fileName));
        if (!strLen($fileName))    throw new InvalidArgumentException('Invalid argument $fileName: '.$fileName);

        if (file_exists($fileName)) {             // existierende Datei
            $this->fileName     = realPath($fileName);
            $this->lastModified = fileMTime($this->fileName);
        }
        else {                                    // nicht existierende Datei
            $name = str_replace('\\', '/', $fileName);

            if ((WINDOWS && !preg_match('/^[a-z]:/i', $name)) || (!WINDOWS && $name{0}!='/'))
                $name = getCwd().'/'.$name;         // relativer Pfad: absoluten Pfad erzeugen, da Arbeitsverzeichnis wechseln kann

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
            if (!is_string($fileNames)) throw new IllegalTypeException('Illegal type of parameter $fileNames: '.getType($fileNames));
            if (!strLen($fileNames))    throw new InvalidArgumentException('Invalid argument $fileNames: '.$fileNames);
            $fileNames = array($fileNames);
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
        // TODO: stat-Cache bei wiederholten Aufrufen loeschen, siehe clearStatCache()

        if (file_exists($this->fileName)) {
            if ($this->lastModified !== fileMTime($this->fileName))
                return false;
        }
        elseif ($this->lastModified !== null) {
            return false;
        }

        return true;
    }
}