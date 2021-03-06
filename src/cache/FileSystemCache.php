<?php
namespace rosasurfer\cache;

use rosasurfer\cache\monitor\Dependency;
use rosasurfer\config\ConfigInterface;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\core\exception\error\PHPError;
use rosasurfer\file\FileSystem as FS;

use function rosasurfer\isRelativePath;
use function rosasurfer\strEndsWith;


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
     * @param  string $label              - Cache-Bezeichner
     * @param  array  $options [optional] - zusaetzliche Optionen (default: none)
     */
    public function __construct($label, array $options = []) {
        $this->label     = $label;
        $this->namespace = $label;
        $this->options   = $options;

        /** @var ConfigInterface $config */
        $config = $this->di('config');

        // Cache-Verzeichnis ermitteln
        if (isset($options['directory'])) {
            $directory = $options['directory'];
            if (isRelativePath($directory)) {
                $directory = $config['app.dir.root'].'/'.$directory;
            }
        }
        else {
            /** @var string $directory */
            $directory = $config['app.dir.cache'];
        }

        // Verzeichnis ggf. erzeugen
        FS::mkDir($directory);

        $this->directory = realpath($directory).DIRECTORY_SEPARATOR;
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
     * @param  string $key                - Schluessel, unter dem der Wert gespeichert ist
     * @param  mixed  $default [optional] - Defaultwert (kann selbst auch NULL sein)
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
     * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schluessel wird
     * ueberschrieben.  Laeuft die angegebene Zeitspanne ab oder aendert sich der Status der angegebenen
     * Abhaengigkeit, wird der Wert automatisch ungueltig.
     *
     * @param  string     $key                   - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed      $value                 - der zu speichernde Wert
     * @param  int        $expires    [optional] - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfaellt (default: nie)
     * @param  Dependency $dependency [optional] - Abhaengigkeit der Gueltigkeit des gespeicherten Wertes
     *
     * @return bool - TRUE bei Erfolg, FALSE andererseits
     */
    public function set($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
        Assert::string($key,  '$key');
        Assert::int($expires, '$expires');

        // im Cache wird ein array(created, expires, value, dependency) gespeichert
        $created = time();

        $file = $this->getFilePath($key);
        $this->writeFile($file, [$created, $expires, $value, $dependency], $expires);

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
        return $this->directory.$key[0].DIRECTORY_SEPARATOR.$key[1].DIRECTORY_SEPARATOR.substr($key, 2);
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
        FS::mkDir(dirname($fileName));
        file_put_contents($fileName, serialize($value));

        // TODO: http://phpdevblog.niknovo.com/2009/11/serialize-vs-var-export-vs-json-encode.html
        return true;
    }
}
