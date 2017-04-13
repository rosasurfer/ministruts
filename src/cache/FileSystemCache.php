<?php
namespace rosasurfer\cache;

use rosasurfer\config\Config;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\exception\error\PHPError;

use rosasurfer\monitor\Dependency;

use function rosasurfer\mkDirWritable;
use function rosasurfer\strEndsWith;

use const rosasurfer\WINDOWS;


/**
 * FileSystemCache
 *
 * Cacht Objekte im Dateisystem.
 *
 * @todo  Cache-Values in Wrapperobjekt speichern und CREATED, EXPIRES etc. verarbeiten
 */
final class FileSystemCache extends CachePeer {


    /** @var string - Cache-Directory */
    private $directory;


    /**
     * Constructor.
     *
     * @param  string $label   - Cache-Bezeichner
     * @param  array  $options - zusaetzliche Optionen (default: none)
     */
    public function __construct($label, array $options=[]) {
        $this->label     = $label;
        $this->namespace = $label;
        $this->options   = $options;

        // Cache-Verzeichnis ermitteln
        if (isSet($options['directory'])) {
            $directory    = $options['directory'];
            $relativePath = WINDOWS ? !preg_match('/^[a-z]:/i', $directory) : ($directory[0]!='/');
            if ($relativePath) {
                $directory = Config::getDefault()->get('app.dir.root').'/'.$directory;
            }
        }
        else {
            /** @var string $directory */
            $directory = Config::getDefault()->get('app.dir.cache');
        }

        // Verzeichnis ggf. erzeugen
        mkDirWritable($directory);

        $this->directory = realPath($directory).DIRECTORY_SEPARATOR;
    }


    /**
     * Ob unter dem angegebenen Schluessel ein Wert im Cache gespeichert ist.
     *
     * @param  string $key - Schluessel
     *
     * @return bool
     */
    public function isCached($key) {
        // Hier wird die eigentliche Arbeit gemacht. Die Methode prueft nicht nur, ob der Wert im Cache
        // existiert, sondern speichert ihn auch im lokalen ReferencePool. Folgende Abfragen muessen so
        // nicht ein weiteres Mal auf den Cache zugreifen, sondern koennen aus dem lokalen Pool bedient
        // werden.

        // ReferencePool abfragen
        if ($this->getReferencePool()->isCached($key)) {
            return true;
        }
        else {
            // Datei suchen und auslesen
            $file = $this->getFilePath($key);
            $data = $this->readFile($file);
            if (!$data)       // Cache-Miss
                return false;

            // Cache-Hit, $data Format: array(created, $expires, $value, $dependency)
            $created    = $data[0];
            $expires    = $data[1];
            $value      = $data[2];
            $dependency = $data[3];

            // expires pruefen
            if ($expires && $created+$expires < time()) {
                $this->drop($key);
                return false;
            }

            // Dependency pruefen
            if ($dependency) {
                $minValid = $dependency->getMinValidity();

                if ($minValid) {
                    if (time() > $created+$minValid) {
                        if (!$dependency->isValid()) {
                            $this->drop($key);
                            return false;
                        }
                        // created aktualisieren (Wert praktisch neu in den Cache schreiben)
                        return $this->set($key, $value, $expires, $dependency);
                    }
                }
                elseif (!$dependency->isValid()) {
                    $this->drop($key);
                    return false;
                }
            }

            // ok, Wert im ReferencePool speichern
            $this->getReferencePool()->set($key, $value, Cache::EXPIRES_NEVER, $dependency);
            return true;
        }
    }


    /**
     * Gibt einen Wert aus dem Cache zurueck.  Existiert der Wert nicht, wird der angegebene Defaultwert
     * zurueckgegeben.
     *
     * @param  string $key     - Schluessel, unter dem der Wert gespeichert ist
     * @param  mixed  $default - Defaultwert (kann selbst auch NULL sein)
     *
     * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schluessel existiert.
     *                 Achtung: Ist im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurueckgegeben.
     */
    public function get($key, $default = null) {
        if ($this->isCached($key))
            return $this->getReferencePool()->get($key);

        return $default;
    }


    /**
     * Loescht einen Wert aus dem Cache.
     *
     * @param  string $key - Schluessel, unter dem der Wert gespeichert ist
     *
     * @return bool - TRUE bei Erfolg, FALSE, falls kein solcher Schluessel existiert
     */
    public function drop($key) {
        $fileName = $this->getFilePath($key);

        if (is_file($fileName)) {
            if (unLink($fileName)) {
                clearStatCache();

                $this->getReferencePool()->drop($key);
                return true;
            }
            throw new RuntimeException('Cannot delete file: '.$fileName);
        }

        return false;
    }


    /**
     * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schluessel wird
     * ueberschrieben.  Laeuft die angegebene Zeitspanne ab oder aendert sich der Status der angegebenen
     * Abhaengigkeit, wird der Wert automatisch ungueltig.
     *
     * @param  string          $key        - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed           $value      - der zu speichernde Wert
     * @param  int             $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfaellt
     * @param  Dependency|null $dependency - Abhaengigkeit der Gueltigkeit des gespeicherten Wertes (default: none)
     *
     * @return bool - TRUE bei Erfolg, FALSE andererseits
     */
    public function set($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
        if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
        if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));

        // im Cache wird ein array(created, expires, value, dependency) gespeichert
        $created = time();

        $file = $this->getFilePath($key);
        $this->writeFile($file, array($created, $expires, $value, $dependency), $expires);

        $this->getReferencePool()->set($key, $value, $expires, $dependency);

        return true;
    }


    /**
     * Gibt den vollstaendigen Pfad zur Cache-Datei fuer den angegebenen Schluessel zurueck.
     *
     * @param  string $key - Schluessel des Wertes
     *
     * @return string - Dateipfad
     */
    private function getFilePath($key) {
        $key = md5($key);
        return $this->directory.$key[0].DIRECTORY_SEPARATOR.$key[1].DIRECTORY_SEPARATOR.subStr($key, 2);
    }


    /**
     * Liest die Datei mit dem angegebenen Namen ein und gibt den deserialisierten Inhalt zurueck.
     *
     * @param  string $fileName - vollstaendiger Dateiname
     *
     * @return mixed - Wert
     */
    private function readFile($fileName) {
        try {
            $data = file_get_contents($fileName, false);
        }
        catch (PHPError $ex) {
            if (strEndsWith($ex->getMessage(), 'failed to open stream: No such file or directory'))
                return null;
            throw $ex;
        }

        if ($data === false)
            throw new RuntimeException('file_get_contents() returned FALSE, $fileName: "'.$fileName);

        return unserialize($data);
    }


    /**
     * Schreibt den angegebenen Wert in die Datei mit dem angegebenen Namen.
     *
     * @param  string $fileName - vollstaendiger Dateiname
     * @param  mixed  $value    - der in die Datei zu schreibende Wert
     *
     * @return bool - TRUE bei Erfolg, FALSE andererseits
     */
    private function writeFile($fileName, $value, $expires) {
        mkDirWritable(dirName($fileName));

        // TODO: http://phpdevblog.niknovo.com/2009/11/serialize-vs-var-export-vs-json-encode.html

        $fH = fOpen($fileName, 'wb');
        fWrite($fH, serialize($value));
        fClose($fH);

        return true;
    }
}
