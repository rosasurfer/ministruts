<?php
/**
 * FrontController
 *
 * NOTE:
 * -----
 * Diese Klasse muß innerhalb der Applikation "thread-sicher" sein (genauer: "request-sicher").  Das bedeutet,
 * daß in statischen Membervariablen kein dynamischer Laufzeitstatus gespeichert werden darf. Es gelten dieselben
 * Regeln wie für thread-sichere Programmierung.
 *
 * Hintergrund ist, daß es je Applikation nur eine FrontController-Instanz gibt, die gecacht (serialisiert) und von
 * folgenden Requests wiederverwendet wird. Dadurch muß die XML-Konfiguration nicht bei jedem Request neu eingelesen
 * werden.
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
         $configFile = str_replace('\\', '/', APPLICATION_ROOT.'/app/config/struts-config.xml');

         // TODO: Win7/NTFS: Auf einer gesperrten Datei (Handle 1 ) funktionieren fread/file_get_contents im selben Prozeß
         //       mit einem zweiten Handle (2) nicht mehr (keine Fehlermeldung, unter Linux funktioniert es). Mit dem zum
         //       Sperren verwendeten Handle funktionieren die Funktionen.

         // Parsen der struts-config.xml synchronisieren
         //$lock = new FileLock($configFile);
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

         //$lock->release();
      }
      return $controller;
   }


   /**
    * Constructor
    *
    * Lädt die Struts-Konfiguration und erzeugt einen entsprechenden Objektbaum.
    */
   protected function __construct() {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      // TODO: keine Fehlermeldung bei falschem $_SERVER['APPLICATION_PATH'] ( z.B. 'myapp/' statt '/myapp')

      // Struts-Konfigurationsdateien suchen
      $appDirectory = str_replace('\\', '/', APPLICATION_ROOT);
      if (!is_file($appDirectory.'/app/config/struts-config.xml'))
         throw new FileNotFoundException('Configuration file not found: "struts-config.xml"');

      $files   = glob($appDirectory.'/app/config/struts-config-*.xml', GLOB_ERR);
      $files[] = $appDirectory.'/app/config/struts-config.xml';


      // Für jede Datei ein Modul erzeugen und registrieren
      try {
         foreach ($files as $file) {
            $baseName = baseName($file, '.xml');
            $prefix = (strStartsWith($baseName, 'struts-config-')) ? '/'.subStr($baseName, 14) : '';

            $module = new Module($file, $prefix);
            $module->freeze();

            if (isSet($this->modules[$prefix]))
               throw new plRuntimeException('All modules must have unique module prefixes, non-unique prefix: "'.$prefix.'"');

            $this->modules[$prefix] = $module;
         }
      }
      catch (Exception $ex) {
         throw new plRuntimeException('Error loading '.$file, $ex);
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
      $request->setAttribute(Struts::MODULE_KEY, $module);

      // RequestProcessor holen
      $processor = $controller->getRequestProcessor($module);

      // Request verarbeiten
      $processor->process($request, $response);
   }


   /**
    * Ermittelt den Prefix des Moduls, das den Request verarbeiten soll.
    *
    * @param  Request $request
    *
    * @return string - Modulprefix
    */
   private function getModulePrefix(Request $request) {
      $requestPath     = $request->getPath();
      $applicationPath = $request->getApplicationPath();

      if ($applicationPath && !strStartsWith($requestPath, $applicationPath))
         throw new plRuntimeException('Can not resolve module prefix from request path: '.$requestPath);

      $value  = strRightFrom($requestPath, $applicationPath);
      $prefix = strLeftTo($value, '/', -1);

      while (!isSet($this->modules[$prefix])) {
         $lastSlash = strRPos($prefix, '/');
         if ($lastSlash === false)
            throw new plRuntimeException('No module configured for request path: '.$requestPath);
         $prefix = subStr($prefix, 0, $lastSlash);
      }
      return $prefix;
   }


   /**
    * Gibt den RequestProcessor zurück, der für das angegebene Module zuständig ist.
    *
    * @param  Module $module
    *
    * @return RequestProcessor
    */
   private function getRequestProcessor(Module $module) {
      $class = $module->getRequestProcessorClass();
      return new $class($module);
   }
}
