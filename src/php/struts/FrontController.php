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
final class FrontController extends Singleton {


   /**
    * Der logische Pfad der aktuellen Webapplikation (Context-Pfad) relativ zur Wurzel-URL des Webservers.
    * Dieser Pfad kann physisch existieren (Verzeichnis unterhalb von DOCUMENT_ROOT) oder virtuell sein,
    * wenn die Anwendung z.B. mit mod_alias oder mod_rewrite eingebunden wurde.
    *
    * z.B. ""       - Default, Anwendung liegt im Wurzelverzeichnis des Webservers "http://domain.tld/"
    *      "/myapp" - Anwendung ist unter "http://domain.tld/myapp/" erreichbar
    */
   const APPLICATION_CONTEXT = APPLICATION_CONTEXT;


   /**
    * die registrierten Module, Schlüssel ist ihr Prefix
    */
   private /*Module[]*/ $modules = array();


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück. Ist ein Cache installiert, wird sie gecacht.
    * Dadurch muß die XML-Konfiguration nicht bei jedem Request neu eingelesen werden.
    *
    * @return FrontController
    */
   public static function me() {
      if (!isSet($_SERVER['REQUEST_METHOD']))
         throw new IllegalStateException('You can not use '.__CLASS__.' in this context.');

      // Ist schon eine Instanz im Cache ?
      $instance = Cache ::get(__CLASS__);
      if (!$instance) {                   // nein
         $instance = parent:: getInstance(__CLASS__);

         // Instanz cachen, wenn nicht auf lokaler Maschine (localhost = development)
         if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')
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
      $appDirectory = dirName($script=$_SERVER['SCRIPT_FILENAME']);
      Config ::set('application.directory', $appDirectory);


      // Umgebung überprüfen:  Ist der Zugriff auf WEB-INF und CVS gesperrt ?
      $contextURL = $this->getContextURL();
      $locations = array($contextURL.'WEB-INF',
                         $contextURL.'WEB-INF/',
                         $contextURL.'CVS',
                         $contextURL.'CVS/',
                         );
      foreach ($locations as $location) {
         // TODO: Authentifizierungs-Infos müssen ggf. vom aktuellen Request übernommen werden
         $request  = HttpRequest ::create()->setUrl($location);
         $response = CurlHttpClient ::create()->send($request);
         $status = $response->getStatus();
         if ($status != 404)
            throw new InfrastructureException('Fatal web server configuration error, resource at "'.$location.'" is not hidden: '.$status);
      }


      // Alle Struts-Konfigurationen in WEB-INF suchen
      if (!is_file($appDirectory.'/WEB-INF/struts-config.xml'))
         throw new FileNotFoundException('Configuration file not found: struts-config.xml');

      $files   = glob($appDirectory.'/WEB-INF/struts-config-*.xml', GLOB_ERR);
      $files[] = $appDirectory.'/WEB-INF/struts-config.xml';


      // Für jede Datei ein Modul erzeugen und registrieren
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

      if (isSet($this->modules[$prefix]))
         throw new RuntimeException('All modules must have unique module prefixes, non-unique prefix: "'.$prefix.'"');

      $this->modules[$prefix] = $module;
   }


   /**
    * Verarbeitet den aktuellen Request.
    */
   public function processRequest() {
      $request  = Request ::me();
      $response = Response ::me();

      $context = self:: APPLICATION_CONTEXT;
      $request->setAttribute(Struts ::APPLICATION_PATH_KEY, $context); // by reference

      // Module selektieren
      $prefix = $this->getModulePrefix($request);
      $module = $this->modules[$prefix];
      $request->setAttribute(Struts ::MODULE_KEY, $module);

      // RequestProcessor holen
      $processor = $this->getRequestProcessor($module);

      // Request verarbeiten
      $processor->process($request, $response);
   }


   /**
    * Ermittelt den Prefix des Moduls, das den Request verarbeiten soll.
    *
    * @param Request request
    *
    * @return string - Modulprefix
    */
   private function getModulePrefix(Request $request) {
      $scriptName = $request->getPath();

      if (!String ::startsWith($scriptName, self:: APPLICATION_CONTEXT))
         throw new RuntimeException('Can not resolve module prefix from uri: '.$scriptName);

      $matchPath = dirName(subStr($scriptName, strLen(self:: APPLICATION_CONTEXT)));
      if ($matchPath === '\\')
         $matchPath = '';

      while (!isSet($this->modules[$matchPath])) {
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
    * Gibt die vollständige Basis-URL der aktuellen Anwendung zurück.
    * (Protokoll + Hostname + Port + Context-Pfad).
    *
    * z.B.: https://www.domain.tld:433/myapp/
    *
    * @return string
    */
   private function getContextURL() {
      $protocol = isSet($_SERVER['HTTPS']) ? 'https' : 'http';
      $host     = $_SERVER['SERVER_NAME'];
      $port     = $_SERVER['SERVER_PORT']=='80' ? '' : ':'.$_SERVER['SERVER_PORT'];
      return $protocol.'://'.$host.$port.self ::APPLICATION_CONTEXT.'/';
   }
}
?>
