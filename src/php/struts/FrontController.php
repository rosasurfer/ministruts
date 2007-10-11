<?
/**
 * FrontController
 *
 * Der FrontController muß "thread-sicher" programmiert sein. Das bedeutet, er muß zustandlos sein und darf nichts speichern,
 * WAS SICH ÄNDERN KÖNNTE.
 *
 * Hintergrund ist, daß es nur eine Instanz gibt, die aus Performance-Gründen im Cache zwischengehalten und für jeden Request
 * wiederverwendet wird. Wenn nun z.B. eine Variable vor dem Speichern im Cache mit einem Zwischenwert belegt würde, dann würde
 * dieser Wert an weitere Requests weitergereicht, wodurch deren Verarbeitung gestört werden könnte.
 *
 * Als einfache Richtlinie läßt sich sagen, daß außerhalb von Funktionen keine Variablen angelegt werden dürfen.
 * Wird das eingehalten, ist er "thread-sicher".
 */
class FrontController extends Singleton {


   const STRUTS_CONFIG_FILE = 'struts-config.xml';


   // alle registrierten Module, aufgeschlüssselt nach ihrem Prefix
   private /*ModuleConfig[]*/ $registeredModules = array();


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück. Ist ein Cache installiert, wird sie gecacht.
    * Dadurch muß die Konfiguration nicht bei jedem Request neu eingelesen werden.
    *
    * @return FrontController
    */
   public static function me() {
      if (!Request ::me())          // ohne Request gibts keine FrontController-Instanz
         return null;

      // Anwendungskonfiguration laden
      if (!defined('APPLICATION_ROOT_DIRECTORY'))
         define('APPLICATION_ROOT_DIRECTORY', dirName($_SERVER['SCRIPT_FILENAME']));

      if (!defined('APPLICATION_ROOT_URL')) {
         $url = subStr($_SERVER['SCRIPT_FILENAME'], strLen($_SERVER['DOCUMENT_ROOT']));
         define('APPLICATION_ROOT_URL', subStr($url, 0, strRPos($url, '/')));
      }

      if (!defined('CONFIG_APP_ROOT'))
         define('CONFIG_APP_ROOT', APPLICATION_ROOT_URL);

      if (!isSet($_SERVER['DOMAIN'])) {
         $parts = explode('.', strRev($_SERVER['SERVER_NAME']), 3);
         $_SERVER['DOMAIN'] = strRev($parts[0].(sizeOf($parts) > 1 ? '.'.$parts[1] : null));
      }


      // development only (don't use cache)
      // ---------------------------------------
      return Singleton ::getInstance(__CLASS__);
      // ---------------------------------------


      $instance = Cache ::get($key=__CLASS__.'_instance');
      if (!$instance) {
         $instance = Singleton ::getInstance(__CLASS__);
         Cache ::set($key, $instance);
      }
      return $instance;
   }


   /**
    * Konstruktor
    *
    * Lädt die Struts-Konfiguration und parst und kompiliert sie.
    */
   protected function __construct() {
      // Webumgebung prüfen      !!! prüfen, ob Zugriff auf WEB-INF und CVS gesperrt ist !!!


      // Alle Struts-Konfigurationen in WEB-INF suchen
      $baseName = baseName(self:: STRUTS_CONFIG_FILE, '.xml');
      $files = glob(APPLICATION_ROOT_DIRECTORY.'/WEB-INF/'.$baseName.'*.xml', GLOB_ERR);
      if (sizeOf($files) == 0)
         throw new FileNotFoundException('Configuration file not found: '.self:: STRUTS_CONFIG_FILE);


      // Für jede Struts-Konfiguration eine ModuleConfig-Instanz erzeugen und registrieren
      try {
         foreach ($files as $file) {
            $config = new ModuleConfig($file);
            $config->freeze();
            $this->registerModule($config);
         }
      }
      catch (Exception $ex) {
         throw new RuntimeException('Error loading '.$file, $ex);
      }
   }


   /**
    * Registriert den Modul-Prefix der übergebenen ModuleConfig-Instanz.
    *
    * @param ModuleConfig $config
    */
   private function registerModule(ModuleConfig $config) {
      $prefix = $config->getPrefix();

      if (isSet($this->registeredModules[$prefix]))
         throw new RuntimeException('All modules must have unique module prefixes, non-unique: "'.$prefix.'"');

      $this->registeredModules[$prefix] = $config;
   }


   /**
    * Gibt den Prefix des Moduls zurück, das den angegebenen Request verarbeitet.
    *
    * @param Request request
    *
    * @return string - Modulprefix bzw. "" für das Default-Modul
    */
   private function getModulePrefix(Request $request) {
      $pathInfo = $request->getPathInfo();

      if (!String ::startsWith($pathInfo, APPLICATION_ROOT_URL))
         throw new RuntimeException('Cannot select module prefix of uri: '.$pathInfo);

      $matchPath = dirName(subStr($pathInfo, strLen(APPLICATION_ROOT_URL)));
      if ($matchPath === '\\')
         $matchPath = '';

      while (!isSet($this->registeredModules[$matchPath])) {
         $lastSlash = strRPos($matchPath, '/');
         if ($lastSlash === false)
            throw new RuntimeException('No module configured for uri: '.$pathInfo);
         $matchPath = subStr($matchPath, 0, $lastSlash);
      }

      return $matchPath;
   }


   /**
    * Gibt den RequestProcessor zurück, der für das angegebene Module zuständig ist.
    *
    * @param ModuleConfig $config
    *
    * @return RequestProcessor
    */
   private function getRequestProcessor(ModuleConfig $config) {
      $class = $config->getProcessorClass();
      return new $class($config);
   }


   /**
    * Verarbeitet den aktuellen Request.
    */
   public function processRequest() {
      $request  = Request ::me();
      $response = Response ::me();

      // Module selektieren
      $prefix = $this->getModulePrefix($request);
      $moduleConfig = $this->registeredModules[$prefix];

      // RequestProcessor holen
      $processor = $this->getRequestProcessor($moduleConfig);

      // Request verarbeiten
      $processor->process($request, $response);
   }
}
?>
