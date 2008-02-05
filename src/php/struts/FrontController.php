<?
/**
 * FrontController
 */
final class FrontController extends Singleton {


   /**
    * die registrierten Module, Schlüssel ist ihr Prefix
    */
   private /*Module[]*/ $modules = array();


   /**
    * Verarbeitet den aktuellen Request.
    */
   public static function processRequest() {
      $controller = self ::me();
      $request    = Request ::me();
      $response   = Response ::me();

      // Module selektieren
      $prefix = $controller->getModulePrefix($request);
      $module = $controller->modules[$prefix];
      $request->setAttribute(Struts ::MODULE_KEY, $module);

      // RequestProcessor holen
      $processor = $controller->getRequestProcessor($module);

      // Request verarbeiten
      $processor->process($request, $response);
   }


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück. Ist ein Cache installiert, wird sie gecacht.
    * Dadurch muß die XML-Konfiguration nicht bei jedem Request neu eingelesen werden.
    *
    * NOTE:
    * -----
    * Diese Methode muß "thread-sicher" programmiert sein. Das bedeutet, sie darf keine Werte in
    * Klassenvariablen speichern.
    *
    * Hintergrund ist, daß es nur eine einzige FrontController-Instanz gibt, die aus Performance-
    * Gründen gecacht und bei jedem Request wiederverwendet wird.  Wenn nun z.B. ein Wert in einer
    * Klassenvariable gespeichert würde, dann würde dieser Wert nicht nur in diesem, sondern auch in
    * allen weiteren Requests existieren, wodurch deren Verarbeitung gestört werden könnte.
    *
    * Als einfache Richtlinie gilt, daß diese Methode keine Werte in $this oder self speichern darf.
    * Wird das eingehalten, ist die Klasse "thread-sicher".
    *
    * @return Singleton
    */
   public static function me() {
      if (!isSet($_SERVER['REQUEST_METHOD']))
         throw new IllegalStateException('You can not use '.__CLASS__.' in this context.');

      // Ist schon eine Instanz im Cache ?
      $instance = Cache ::get(__CLASS__);
      if (!$instance) {                   // nein, neue Instanz erzeugen ...
         $instance = Singleton ::getInstance(__CLASS__);

         // ... und mit FileDependency cachen
         $appDirectory = dirName($_SERVER['SCRIPT_FILENAME']);
         $dependency = new FileDependency($appDirectory.'/WEB-INF/struts-config.xml');
         Cache ::set(__CLASS__, $instance, Cache ::EXPIRES_NEVER, $dependency);
      }

      return $instance;
   }


   /**
    * Konstruktor
    *
    * Lädt die Struts-Konfiguration und erzeugt einen entsprechenden Objektbaum.
    */
   protected function __construct() {
      // Umgebung prüfen 1:  Ist die Servervariable APPLICATION_PATH richtig gesetzt ?
      if (!isSet($_SERVER['APPLICATION_PATH'])) {
         throw new InfrastructureException('Web server configuration error, environment variable "APPLICATION_PATH" is not defined');
      }
      elseif (!preg_match('/^(\/[^\/]+)*$/', $_SERVER['APPLICATION_PATH'])) {
         throw new InfrastructureException('Web server configuration error, invalid value of environment variable "APPLICATION_PATH": '.$_SERVER['APPLICATION_PATH']);
      }

      // Umgebung prüfen 2:  Ist der Zugriff auf WEB-INF und CVS-Daten gesperrt ?
      $baseURL = Request ::me()->getApplicationURL();
      $locations = array($baseURL.'/WEB-INF',
                         $baseURL.'/WEB-INF/',
                         $baseURL.'/WEB-INF/struts-config.xml',
                         $baseURL.'/CVS',
                         $baseURL.'/CVS/',
                         );
      foreach ($locations as $location) {
         $request  = HttpRequest ::create()->setUrl($location);
         $response = CurlHttpClient ::create()->send($request);
         $status = $response->getStatus();

         // TODO: HTTP-Authentication-Support in Serverprüfung einbauen
         if ($status == 401) {
            Logger ::log('Web server configuration check: authentication support not yet implemented for location: "'.$location.'"', L_NOTICE, __CLASS__);
         }
         // TODO: Apache 2.2 liefert statt eines 404 einen 403
         elseif ($status != 404) {
            throw new InfrastructureException('Web server configuration error, resource at "'.$location.'" is not hidden: '.$status.' ('.HttpResponse ::$sc[$status].')');
         }
      }


      // Struts-Konfigurationsdateien suchen
      $appDirectory = dirName($_SERVER['SCRIPT_FILENAME']);
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
    * Ermittelt den Prefix des Moduls, das den Request verarbeiten soll.
    *
    * @param Request request
    *
    * @return string - Modulprefix
    */
   private function getModulePrefix(Request $request) {
      $scriptName      = $request->getPath();
      $applicationPath = $request->getApplicationPath();

      if (!String ::startsWith($scriptName, $applicationPath))
         throw new RuntimeException('Can not resolve module prefix from uri: '.$scriptName);

      $matchPath = dirName(subStr($scriptName, strLen($applicationPath)));
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
}
?>
