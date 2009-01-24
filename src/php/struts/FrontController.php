<?
/**
 * FrontController
 *
 * NOTE:
 * -----
 * Diese Klasse muß "thread-sicher" programmiert sein. Das bedeutet, in den Methoden dürfen keine
 * Instanzvariablen geändert werden.
 *
 * Hintergrund ist, daß es nur eine einzige FrontController-Instanz gibt, die aus Performance-
 * Gründen gecacht und bei jedem Request wiederverwendet wird.  Wenn nun z.B. während eines Methoden-
 * aufrufs ein Wert in einer Instanzvariable geändert würde, dann würde sich diese Änderung nicht nur
 * auf diesen, sondern auch auf alle weiteren Prozesse, die diese Instanz verwenden, auswirken,
 * wodurch deren Ablauf gestört werden könnte.
 *
 * Als einfache Richtlinie gilt, daß in den Methoden keine Werte in $this oder self geändert werden
 * dürfen.  Wird das eingehalten, ist die Klasse "thread-sicher".
 *
 */
final class FrontController extends Singleton {


   private static /*bool*/ $logDebug, $logInfo, $logNotice;


   /**
    * alle registrierten Module, Schlüssel ist ihr Prefix
    */
   private /*Module[]*/ $modules = array();


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück. Ist ein Cache installiert, wird sie gecacht.
    * Dadurch muß die XML-Konfiguration nicht bei jedem Request neu eingelesen werden.
    *
    * @return Singleton
    */
   public static function me() {
      if (!isSet($_SERVER['REQUEST_METHOD']))
         throw new IllegalStateException('You can not use '.__CLASS__.' in this context.');

      $cache = Cache ::me();

      // Ist schon eine Instanz im Cache ?
      $controller = $cache->get(__CLASS__);

      if (!$controller) {
         // TODO: Application::getBaseDirectory() implementieren
         $configFile = dirName($_SERVER['SCRIPT_FILENAME']).'/WEB-INF/struts-config.xml';

         // Parsen der struts-config.xml synchronisieren
         $lock = new FileLock($configFile);

            $controller = $cache->get(__CLASS__);
            if (!$controller) {
               // neue Instanz erzeugen ...
               $controller = Singleton ::getInstance(__CLASS__);

               $dependency = FileDependency ::create($configFile);
               if (!WINDOWS || $_SERVER['REMOTE_ADDR']!='127.0.0.1')    // Unterscheidung Production/Development
                  $dependency->setMinValidity(60 * SECONDS);

               // ... und mit FileDependency cachen
               $cache->set(__CLASS__, $controller, Cache ::EXPIRES_NEVER, $dependency);
            }

         $lock->release();
      }
      return $controller;
   }


   /**
    * Konstruktor
    *
    * Lädt die Struts-Konfiguration und erzeugt einen entsprechenden Objektbaum.
    */
   protected function __construct() {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      // TODO: keine Fehlermeldung bei falschem $_SERVER['APPLICATION_PATH'] ( z.B. 'myapp/' statt '/myapp')

      // Umgebung prüfen:  Ist der Zugriff auf WEB-INF und CVS-Daten gesperrt ?
      // TODO: apache_lookup_uri() oder virtual() benutzen
      $baseURL = Request ::me()->getApplicationURL();
      $locations = array($baseURL.'/WEB-INF',
                         $baseURL.'/WEB-INF/',
                         $baseURL.'/WEB-INF/struts-config.xml',
                         $baseURL.'/CVS',
                         $baseURL.'/CVS/',
                         );
      foreach ($locations as $location) {
         $request  = HttpRequest ::create()->setUrl($location);
         $response = CurlHttpClient ::create()
                                    ->setTimeout(10)
                                    ->send($request);
         $status = $response->getStatus();

         // TODO: HTTP-Authentication-Support einbauen
         if ($status == 401) {
            Logger ::log('Web server configuration check: authentication support not yet implemented for location: "'.$location.'"', L_NOTICE, __CLASS__);
         }
         elseif ($status != 403 && $status != 404) {
            throw new InfrastructureException('Web server configuration error, resource at "'.$location.'" is not blocked: '.$status.' ('.HttpResponse ::$sc[$status].')');
         }
      }


      // Struts-Konfigurationsdateien suchen
      $appDirectory = dirName($_SERVER['SCRIPT_FILENAME']);
      if (!is_file($appDirectory.'/WEB-INF/struts-config.xml'))
         throw new FileNotFoundException('Configuration file not found: "struts-config.xml"');

      $files   = glob($appDirectory.'/WEB-INF/struts-config-*.xml', GLOB_ERR);
      $files[] = $appDirectory.'/WEB-INF/struts-config.xml';


      // Für jede Datei ein Modul erzeugen und registrieren
      try {
         foreach ($files as $file) {
            $baseName = baseName($file, '.xml');
            $prefix = (String ::startsWith($baseName, 'struts-config-')) ? '/'.subStr($baseName, 14) : '';

            $module = new Module($file, $prefix);
            $module->freeze();

            if (isSet($this->modules[$prefix]))
               throw new RuntimeException('All modules must have unique module prefixes, non-unique prefix: "'.$prefix.'"');

            $this->modules[$prefix] = $module;
         }
      }
      catch (Exception $ex) {
         throw new RuntimeException('Error loading '.$file, $ex);
      }
   }


   /**
    * Verarbeitet den aktuellen Request.
    */
   public static function processRequest() {
      $controller = self     ::me();
      $request    = Request  ::me();
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
    * Ermittelt den Prefix des Moduls, das den Request verarbeiten soll.
    *
    * @param Request request
    *
    * @return string - Modulprefix
    */
   private function getModulePrefix(Request $request) {
      $requestPath     = $request->getPath();
      $applicationPath = $request->getApplicationPath();

      if ($applicationPath && !String ::startsWith($requestPath, $applicationPath))
         throw new RuntimeException('Can not resolve module prefix from request path: '.$requestPath);

      $prefix = subStr($requestPath, $len=strLen($applicationPath), strRPos($requestPath, '/')-$len);

      while (!isSet($this->modules[$prefix])) {
         $lastSlash = strRPos($prefix, '/');
         if ($lastSlash === false)
            throw new RuntimeException('No module configured for request path: '.$requestPath);
         $prefix = subStr($prefix, 0, $lastSlash);
      }
      return $prefix;
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
