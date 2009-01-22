<?
/**
 * FileSystemCache
 *
 * Cacht Objekte im Dateisystem.
 *
 * TODO: Cache-Values in Wrapperobjekt speichern und CREATED, EXPIRES etc. verarbeiten
 */
final class FileSystemCache extends CachePeer {


   /**
    * Cache-Directory
    */
   private /*string*/ $directory;


   /**
    * Constructor.
    *
    * @param string $label   - Cache-Bezeichner
    * @param array  $options - zusätzliche Optionen
    */
   public function __construct($label, array $options = null) {
      $this->label     = $label;
      $this->namespace = $label;
      $this->options   = $options;

      // Cache-Verzeichnis ermitteln
      if (isSet($options['directory'])) $directory = $options['directory'];
      else                              $directory = 'etc/cache/'.$label;     // Defaultverzeichnis

      // relativen Pfad als relativ zum WEB-INF-Verzeichnis interpretieren
      $directory = str_replace('\\', '/', $directory);
      if ($directory{0}!='/' && (!WINDOWS || !preg_match('/^[a-z]:/i', $directory)))
         $directory = dirName($_SERVER['SCRIPT_FILENAME']).'/WEB-INF/'.$directory;


      // Cache-Verzeichnis ggf. erzeugen
      if (is_file($directory) || (!is_writable($directory) && !mkDir($directory, 0700, true)))
         throw new InvalidArgumentException('Can not write to $directory: '.$directory);

      $this->directory = realPath($directory).DIRECTORY_SEPARATOR;
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
    *
    * @return boolean
    */
   public function isCached($key) {
      // Hier wird die eigentliche Arbeit gemacht. Die Methode prüft nicht nur, ob der Wert im Cache
      // existiert, sondern speichert ihn auch im lokalen ReferencePool. Folgende Abfragen müssen so
      // nicht ein weiteres Mal auf den Cache zugreifen, sondern können aus dem lokalen Pool bedient
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

         // expires prüfen
         if ($expires && $created+$expires < time()) {
            $this->drop($key);
            return false;
         }

         // Dependency prüfen
         if ($dependency) {
            $minValid = $dependency->getMinValidity();

            if ($minValid) {
               if (time() > $created+$minValid && !WINDOWS) { // unter Windows wird $minValid ignoriert
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
         $this->getReferencePool()->set($key, $value, Cache ::EXPIRES_NEVER, $dependency);
         return true;
      }
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 Achtung: Ist im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    */
   public function get($key) {
      if ($this->isCached($key))
         return $this->getReferencePool()->get($key);

      return null;
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return boolean - TRUE bei Erfolg, FALSE, falls kein solcher Schlüssel existiert
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
    * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schlüssel wird
    * überschrieben.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param string      $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed       $value      - der zu speichernde Wert
    * @param int         $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param IDependency $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   public function set($key, &$value, $expires = Cache ::EXPIRES_NEVER, IDependency $dependency = null) {
      if ($key!==(string)$key)      throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if ($expires!==(int)$expires) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));

      // im Cache wird ein array(created, expires, value, dependency) gespeichert
      $created = time();

      $file = $this->getFilePath($key);
      $this->writeFile($file, array($created, $expires, $value, $dependency), $expires);

      $this->getReferencePool()->set($key, $value, $expires, $dependency);

      return true;
   }


   /**
    * Gibt den vollständigen Pfad zur Cache-Datei für den angegebenen Schlüssel zurück.
    *
    * @param string $key - Schlüssel des Wertes
    *
    * @return string - Dateipfad
    */
   private function getFilePath($key) {
      $key = md5($key);
      return $this->directory.$key{0}.DIRECTORY_SEPARATOR.$key{1}.DIRECTORY_SEPARATOR.subStr($key, 2);
   }


   /**
    * Liest die Datei mit dem angegebenen Namen ein und gibt den deserialisierten Inhalt zurück.
    *
    * @param string $fileName - vollständiger Dateiname
    *
    * @return mixed - Wert
    */
   private function readFile($fileName) {
      try {
         $data = file_get_contents($fileName, false);
      }
      catch (PHPErrorException $ex) {
         if (String ::endsWith($ex->getMessage(), 'failed to open stream: No such file or directory'))
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
    * @param string $fileName - vollständiger Dateiname
    * @param mixed  $value    - der in die Datei zu schreibende Wert
    *
    * @return boolean - TRUE bei Erfolg, FALSE andererseits
    */
   private function writeFile($fileName, $value, $expires) {
      // Unterverzeichnis ggf. erzeugen
      $directory = dirName($fileName);
      if (is_file($directory) || (!is_writable($directory) && !mkDir($directory, 0700, true)))
         throw new InvalidArgumentException('Can not write to directory: '.$directory);

      // Datei schreiben
      $fH = fOpen($fileName, 'wb');
      fWrite($fH, serialize($value));
      fClose($fH);

      return true;
   }
}
?>
