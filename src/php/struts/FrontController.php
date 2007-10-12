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


   /**
    * Das Wurzelverzeichnis der aktuellen Webapplikation.
    */
   private $applicationDir;


   /**
    * Die Pfadkomponente der URL der laufenden Webapplikation (= Context-URL).
    */
   private $applicationPath;


   // alle registrierten Module, aufgeschlüssselt nach ihrem Prefix
   private /*ModuleConfig[]*/ $registeredModules = array();


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück. Ist ein Cache installiert, wird sie gecacht.
    * Dadurch muß die Konfiguration nicht bei jedem Request neu eingelesen werden.
    *
    * @return FrontController
    */
   public static function me() {
      if (!isSet($_SERVER['REQUEST_METHOD'])) {
         Logger ::log('You can not use this componente at the command line', L_ERROR, __CLASS__);
         return null;
      }

      // auf dem Entwicklungssystem wird die Struts-Konfiguration immer neu einlesen
      if ($_SERVER['REMOTE_ADDR']=='127.0.0.1')
         return Singleton ::getInstance(__CLASS__);


      // auf dem Production-Server wird sie nach der Initialisierung im Cache abgelegt
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
      // Konfiguration vervollständigen
      $this->applicationDir  = dirName($script=$_SERVER['SCRIPT_FILENAME']);
      $this->applicationPath = subStr($script, $length=strLen($_SERVER['DOCUMENT_ROOT']), strRPos($script, '/')-$length);


      // Umgebung prüfen (ist Zugriff auf WEB-INF und CVS gesperrt ?)


      // Alle Struts-Konfigurationen in WEB-INF suchen
      $baseName = baseName(self:: STRUTS_CONFIG_FILE, '.xml');
      $files = glob($this->applicationDir.'/WEB-INF/'.$baseName.'*.xml', GLOB_ERR);
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
      $config = $request->getAttribute(Struts ::MODULE_KEY);

      if ($config !== null) {
         return $config->getPrefix();
      }

      $scriptName = $request->getPathInfo();

      if (!String ::startsWith($scriptName, $this->applicationPath))
         throw new RuntimeException('Can not select module prefix of uri: '.$scriptName);

      $matchPath = dirName(subStr($scriptName, strLen($this->applicationPath)));
      if ($matchPath === '\\')
         $matchPath = '';

      while (!isSet($this->registeredModules[$matchPath])) {
         $lastSlash = strRPos($matchPath, '/');
         if ($lastSlash === false)
            throw new RuntimeException('No module configured for uri: '.$scriptName);
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

      $request->setAttribute(Struts ::APPLICATION_PATH_KEY, $this->applicationPath);

      // Module selektieren
      $prefix = $this->getModulePrefix($request);
      $moduleConfig = $this->registeredModules[$prefix];
      $request->setAttribute(Struts ::MODULE_KEY, $moduleConfig);

      // RequestProcessor holen
      $processor = $this->getRequestProcessor($moduleConfig);

      // Request verarbeiten
      $processor->process($request, $response);
   }
}
?>
