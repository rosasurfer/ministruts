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
      // ist die Instanz im Cache, wird sie von dort geladen
      $instance = Cache ::get($key=__CLASS__.'_instance');
      if (!$instance) {
         $instance = parent:: getInstance(__CLASS__);

         // auf dem Production-Server wird sie nach der Erzeugung gecacht
         if (isSet($_SERVER['REQUEST_METHOD']) && $_SERVER['REMOTE_ADDR']!='127.0.0.1')
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
      // Konfigurationsdateien suchen
      $configfiles = null;
      $filename = 'config.properties';
      $baseName = baseName($filename, '.properties');

      if (Request ::me()) {   // bei Webapplikation in WEB-INF suchen
         $files = array_reverse(glob(getCwd().'/WEB-INF/'.$baseName.'*.properties', GLOB_ERR));
      }
      else {                  // bei Konsolenapplikation im aktuellen Verzeichnis suchen
         $files = array_reverse(glob(getCwd().'/'.$baseName.'*.properties', GLOB_ERR));
      }
      foreach ($files as $file) {
         $configfiles[] = $file;
      }

      // include-Pfad nach weiteren Konfigurationsdateien durchsuchen
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
    * Lädt eine einzelne Konfigurationsdatei.  Schon vorhandene Einstellungen werden dabei nicht
    * überschrieben.
    *
    * @param string $filename - Dateiname
    */
   private function loadFile($filename) {
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
    */
   private function getProperty($key) {
      if (isSet($this->properties[$key]))
         return $this->properties[$key];
      return null;
   }


   /**
    */
   private function setProperty($key, $value) {
      $this->properties[$key] = $value;

      // Cache ggf. aktualisieren
      if (Cache ::isCached($key=__CLASS__))
         Cache ::set($key, $this);
   }


   /**
    * Gibt die unter dem angegebenen Schlüssel gespeicherte Konfigurationseinstellung zurück oder NULL,
    * wenn unter diesem Schlüssel keine Einstellung existiert.
    *
    * @param string $key - Schlüssel
    *
    * @return mixed - String oder Array mit Konfigurationseinstellung
    */
   public static function get($key) {
      return self ::me()->getProperty($key);
   }


   /**
    * Setzt oder überschreibt die Einstellung mit dem angegebenen Schlüssel. Ist der Wert kein String,
    * wird er in einen String konvertiert.
    *
    * @param string $key   - Schlüssel
    * @param mixed  $value - Einstellung
    */
   public static function set($key, $value) {
      return self ::me()->setProperty($key, (string) $value);
   }
}
?>
