<?
/**
 * FrontController
 *
 * Der FrontController muß "thread-sicher" programmiert sein. Das bedeutet, er muß zustandlos sein und
 * darf nichts speichern, WAS SICH ÄNDERN KÖNNTE.
 *
 * Hintergrund ist, daß es nur eine Instanz gibt, die aus Performance-Gründen im Cache zwischengehalten
 * und für jeden Request wiederverwendet wird. Wenn nun z.B. eine Variable vor dem Speichern im Cache mit
 * einem Zwischenwert belegt würde, dann würde dieser Wert an weitere Requests weitergereicht, wodurch
 * deren Verarbeitung gestört werden könnte.
 *
 * Als einfache Richtlinie läßt sich sagen, daß außerhalb von Funktionen keine Variablen angelegt werden
 * dürfen. Wird das eingehalten, ist er "thread-sicher".
 */
class FrontController extends Singleton {


   /**
    * Das Wurzelverzeichnis der aktuellen Webapplikation.
    */
   private $applicationDir;


   /**
    * Die Pfadkomponente der URL der laufenden Webapplikation (= Context-URL).
    */
   private $applicationPath;


   // alle registrierten Module, aufgeschlüssselt nach ihrem Prefix
   private /*Module[]*/ $registeredModules = array();


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

      // ist die Struts-Konfiguration im Cache, wird sie von dort geladen
      $instance = Cache ::get(__CLASS__);
      if (!$instance) {
         $instance = parent:: getInstance(__CLASS__);

         // auf dem Entwicklungssystem wird sie nicht gecacht
         //if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')
            Cache ::set(__CLASS__, $instance);
      }
      return $instance;
   }


   /**
    * Konstruktor
    *
    * Lädt die Struts-Konfiguration und erzeugt einen entsprechenden Objektbaum.
    */
   protected function __construct() {
      // Konfiguration vervollständigen
      $this->applicationDir  = dirName($script=$_SERVER['SCRIPT_FILENAME']);
      $this->applicationPath = subStr($script, $length=strLen($_SERVER['DOCUMENT_ROOT']), strRPos($script, '/')-$length);
      Config ::set('application.directory', $this->applicationDir );
      Config ::set('application.path'     , $this->applicationPath);


      // Umgebung überprüfen:  Ist der Zugriff auf WEB-INF und CVS gesperrt ?
      $applicationRoot = subStr($_SERVER['SCRIPT_URI'], 0, strPos($_SERVER['SCRIPT_URI'], '/', 8)).$this->applicationPath;
      $locations = array($applicationRoot.'/WEB-INF',
                         $applicationRoot.'/WEB-INF/',
                         $applicationRoot.'/CVS',
                         $applicationRoot.'/CVS/',
                         );
      foreach ($locations as $location) {
         $request  = HttpRequest ::create()->setUrl($location);
         $response = CurlHttpClient ::create()->send($request);
         $status = $response->getStatus();
         if ($status != 403 && $status != 404)
            throw new InfrastructureException('Fatal web server configuration error: URL "'.$location.'" is accessible');
      }

      // Alle Struts-Konfigurationen in WEB-INF suchen
      if (!is_file($this->applicationDir.'/WEB-INF/struts-config.xml')) throw new FileNotFoundException('Configuration file not found: struts-config.xml');
      $files[] = $this->applicationDir.'/WEB-INF/struts-config.xml';
      $files   = array_merge($files, glob($this->applicationDir.'/WEB-INF/struts-config-*.xml', GLOB_ERR));


      // Für jede Struts-Konfiguration eine Module-Instanz erzeugen und Prefix registrieren
      try {
         foreach ($files as $file) {
            $baseName = baseName($file, '.xml');
            $prefix = (String ::startsWith($baseName, 'struts-config-')) ? '/'.subStr($baseName, 14) : '';

            $module = new Module($file, $prefix);
            $module->freeze();
            $this->registerModule($module);
         }
      }
      catch (Exception $ex) {
         throw new RuntimeException('Error loading '.$file, $ex);
      }
   }


   /**
    * Registriert den Module-Prefix der übergebenen Instanz.
    *
    * @param Module $module
    */
   private function registerModule(Module $module) {
      $prefix = $module->getPrefix();

      if (isSet($this->registeredModules[$prefix]))
         throw new RuntimeException('All modules must have unique module prefixes, non-unique prefix: "'.$prefix.'"');

      $this->registeredModules[$prefix] = $module;
   }


   /**
    * Gibt den Prefix des Moduls zurück, das den angegebenen Request verarbeitet.
    *
    * @param Request request
    *
    * @return string - Modulprefix bzw. "" für das Default-Modul
    */
   private function getModulePrefix(Request $request) {
      $module = $request->getAttribute(Struts ::MODULE_KEY);

      if ($module !== null) {
         return $module->getPrefix();
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
    * @param Module $module
    *
    * @return RequestProcessor
    */
   private function getRequestProcessor(Module $module) {
      $class = $module->getRequestProcessorClass();
      return new $class($module);
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
      $module = $this->registeredModules[$prefix];
      $request->setAttribute(Struts ::MODULE_KEY, $module);

      // RequestProcessor holen
      $processor = $this->getRequestProcessor($module);

      // Request verarbeiten
      $processor->process($request, $response);
   }
}
?>
