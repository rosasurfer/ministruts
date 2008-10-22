<?
/**
 * FileDependency
 *
 * Abhängigkeit vom letzten Änderungszeitpunkt einer Datei.  Die Abhängigkeit ist nur dann erfüllt,
 * wenn sich der Zustand der Datei seit dem letzten Aufruf nicht geändert hat.  Auch die Abhängigkeit
 * von einer nicht existierenden Datei ist möglich, die Abhängigkeit ist in diesem Falle solange
 * erfüllt, wie die Datei (weiterhin) nicht existiert.
 *
 * Anwendungsbeispiel:
 * -------------------
 *
 *    $dependency = new FileDependency('/etc/crontab');
 *    ....
 *
 *    if ($dependency->isStatusChanged()) {
 *       // irgendeine Aktion
 *    }
 *
 * Dieses Beispiel definiert eine Abhängigkeit vom Zustand der Datei '/etc/crontab'.  Solange die
 * Datei nicht verändert oder gelöscht wird, bleibt die Abhängigkeit erfüllt und der Aufruf von
 * $dependency->isStatusChanged() gibt FALSE zurück.  Nach Änderung oder Löschen der Datei gibt der
 * Aufruf von $dependency->isStatusChanged() TRUE zurück.
 */
class FileDependency extends ChainableDependency {


   /**
    * Dateiname
    */
   private /*string*/ $fileName;


   /**
    * letzter Änderungszeitpunkt der Datei (Unix-Timestamp)
    */
   private /*int*/ $timestamp;


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

      // TODO: Directories mit DirectoryDependency verarbeiten

      if (file_exists($fileName)) {
         $this->fileName = realPath($fileName);
         $this->timestamp = fileMTime($this->fileName);
      }
      else {
         $name = str_replace('\\', '/', $fileName);

         if ((WINDOWS && !preg_match('/^[a-z]:/i', $name)) || (!WINDOWS && $name{0}!='/'))
            $name = getCwd().'/'.$name;      // absoluten Pfad erzeugen, da Arbeitsverzeichnis wechseln kann

         $this->fileName = str_replace('/', DIRECTORY_SEPARATOR, $name);
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
    * Ob sich die der Abhängigkeit zugrunde liegende Datei geändert hat oder nicht.
    *
    * @return boolean
    */
   public function isStatusChanged() {
      // TODO: stat-Cache bei wiederholten Aufrufen löschen, siehe clearStatCache()

      if (file_exists($this->fileName)) {
         if ($this->timestamp !== fileMTime($this->fileName))
            return true;
      }
      elseif ($this->timestamp !== null) {
         return true;
      }

      return parent::isStatusChanged();
   }
}
?>
