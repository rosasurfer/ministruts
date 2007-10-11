<?
/**
 * Config
 *
 * Klasse zur Anwendungskonfiguration.
 */
final class Config extends Singleton {


   // Property-Pool
   private $properties = array();


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück.
    *
    * @return Config
    */
   public static function me() {
      // unter Windows (= Development) wird die Config jedes mal neu eingelesen
      if (WINDOWS)
         return Singleton ::getInstance(__CLASS__);

      // unter Linux wird sie nach der Initialisierung im Cache abgelegt
      $instance = Cache ::get($key=__CLASS__);
      if (!$instance) {
         $instance = Singleton ::getInstance(__CLASS__);
         Cache ::set($key, $instance);
      }
      return $instance;
   }


   /**
    * Konstruktor
    *
    * Sucht und lädt alle verfügbaren Konfigurationsdateien.
    */
   protected function __construct() {
      // alle Konfigurationdateien suchen
      $configfiles = null;
      $filename = 'config.properties';
      $baseName = baseName($filename, '.properties');

      if (Request ::me()) {
         // bei Webapplikation: in WEB-INF
         $files = array_reverse(glob(getCwd().'/WEB-INF/'.$baseName.'*.properties', GLOB_ERR));
         foreach ($files as $file)
            $configfiles[] = $file;
      }
      else {
         // bei Konsolenappliaktion: im aktuellen Verzeichnis
         $files = array_reverse(glob(getCwd().'/'.$baseName.'*.properties', GLOB_ERR));
         foreach ($files as $file)
            $configfiles[] = $file;
      }

      // im include-Pfad
      $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
      foreach ($paths as $path) {
         $files = array_reverse(glob($path.'/'.$baseName.'*.properties', GLOB_ERR));
         foreach ($files as $file)
            $configfiles[] = $file;
      }


      if (sizeOf($configfiles) == 0) {
         Logger ::log('Configuration file not found: '.$filename, L_WARN, __CLASS__);
         return;
      }

      // alle gefundenen Konfigurationen laden
      foreach ($configfiles as $file)
         $this->loadFile($file);
   }


   /**
    * Lädt eine einzelne Konfigurationsdatei.  Schon vorhandene Einstellungen werden dabei nicht überschrieben.
    *
    * @param string $filename - Dateiname
    */
   private function loadFile($filename) {
      if (!is_readable($filename)) throw new IOException('File not readable: '.$filename);
      $lines = file($filename, FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);

      foreach ($lines as $line) {
         $parts = explode('=', $line, 2);
         if (sizeOf($parts) < 2) throw new RuntimeException('Syntax error in "'.$filename.'" at: '.$parts[0]);

         $key   = trim($parts[0]);
         $value = trim($parts[1]);

         if (!isSet($this->properties[$key]))
            $this->properties[$key] = $value;
      }
   }


   /**
    * Gibt die unter dem angegebenen Schlüssel gespeicherte Konfigurationseinstellung zurück oder NULL, wenn unter diesem
    * Schlüssel keine Einstellung existiert.
    *
    * @param string $key - Schlüssel
    *
    * @return string - Konfigurationseinstellung
    */
   public static function get($key) {
      return self ::me()->getProperty($key);
   }


   /**
    */
   private function getProperty($key) {
      if (isSet($this->properties[$key]))
         return $this->properties[$key];
      return null;
   }
}
?>
