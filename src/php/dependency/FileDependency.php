<?
/**
 * FileDependency
 *
 * Abhängigkeit vom letzten Änderungszeitpunkt einer Datei.  Die Abhängigkeit ist erfüllt, wenn sich
 * der Zustand der Datei seit dem letzten Aufruf nicht geändert hat.  Auch die Abhängigkeit von einer
 * nicht existierenden Datei ist möglich, die Abhängigkeit ist in diesem Falle solange erfüllt, wie
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
 * Dieses Beispiel definiert eine Abhängigkeit vom Änderungszeitpunkt der Datei '/etc/crontab'.
 * Solange die Datei nicht verändert wird, bleibt die Abhängigkeit erfüllt und der Aufruf von
 * $dependency->isValid() gibt TRUE zurück.  Nach Änderung oder Löschen der Datei gibt der Aufruf von
 * $dependency->isValid() FALSE zurück.
 */
class FileDependency extends ChainableDependency {


   /**
    * Dateiname
    */
   private /*string*/ $fileName;


   /**
    * letzter Änderungszeitpunkt der Datei (Unix-Timestamp)
    */
   private /*int*/ $lastModified;


   /**
    * Constructor
    *
    * Erzeugt eine neue FileDependency, die die Datei mit dem übergebenen Namen überwacht.
    *
    * @param string $fileName - Dateiname
    */
   public function __construct($fileName) {
      if (!is_string($fileName)) throw new IllegalTypeException('Illegal type of argument $fileName: '.getType($fileName));
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
    * Erzeugt eine neue FileDependency, die die Datei mit dem übergebenen Namen überwacht.
    *
    * @param string $fileName - Dateiname
    *
    * @return FileDependency
    */
   public static function create($fileName) {
      return new self($fileName);
   }


   /**
    * Ob die der Abhängigkeit zugrunde liegende Datei weiterhin unverändert ist.
    *
    * @return boolean - TRUE, wenn die Datei sich nicht geändert hat.
    *                   FALSE, wenn die Datei sich geändert hat.
    */
   public function isValid() {
      // TODO: stat-Cache bei wiederholten Aufrufen löschen, siehe clearStatCache()

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
?>
