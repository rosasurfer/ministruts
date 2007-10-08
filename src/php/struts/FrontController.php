<?
/**
 * FrontController
 *
 * Der FrontController muß "thread-sicher" programmiert sein. Das bedeutet, er muß zustandlos sein und darf nichts speichern,
 * WAS SICH ÄNDERN KÖNNTE.
 *
 * Hintergrund ist, daß es nur eine Instanz gibt, die aus Performance-Gründen im Cache zwischengehalten und für jeden Request
 * wiederverwendet wird. Wenn nun z.B. eine Variable vor dem Speichern im Cache mit einem Zwischenwert belegt würde, dann würde
 * dieser Wert an weitere Requests weitergereicht, wodurch deren Abarbeitung unerwartet gestört werden könnte.
 *
 * Als einfache Richtlinie läßt sich sagen, daß außerhalb von Funktionen keine Variablen angelegt werden dürfen.
 * Wird das eingehalten, ist er thread-sicher.
 */
class FrontController extends Singleton {


   const STRUTS_CONFIG_FILE = 'struts-config.xml';


   // es gibt zur Zeit nur ein Modul (daß sich nicht ändern kann)
   private /* ModuleConfig */ $moduleConfig;


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück. Ist ein Cache installiert, wird sie gecacht.
    * Dadurch muß die Konfiguration nicht bei jedem Request neu eingelesen werden.
    *
    * @return FrontController
    */
   public static function me() {

      // development only (don't uses cache)
      return Singleton ::getInstance(__CLASS__);


      $instance = Cache ::get($key = __CLASS__.'_instance');
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
      try {
         // !!! Wir müssen zuerst prüfen, ob der Zugriff auf WEB-INF und CVS-Verzeichnisse gesperrt ist !!!

         $xmlObject = $this->loadConfiguration();

         $configfile = APPLICATION_ROOT_DIRECTORY.DIRECTORY_SEPARATOR.'WEB-INF'.DIRECTORY_SEPARATOR.self ::STRUTS_CONFIG_FILE;
         $config = new ModuleConfig('', $configfile);

         $this->initGlobalForwards($config, $xmlObject);
         $this->initMappings($config, $xmlObject);
         $config->freeze();

         $this->moduleConfig = $config;
      }
      catch (Exception $ex) {
         throw new RuntimeException('Error loading '.self ::STRUTS_CONFIG_FILE, $ex);
      }
   }


   /**
    * Sucht die Konfigurationsdatei, liest sie ein und parst sie in ein XML-Objekt. Die XML-Datei wird validiert.
    *
    * @return SimpleXMLElement
    */
   private function loadConfiguration() {
      $fileName = APPLICATION_ROOT_DIRECTORY.DIRECTORY_SEPARATOR.'WEB-INF'.DIRECTORY_SEPARATOR.self ::STRUTS_CONFIG_FILE;
      if (!is_file($fileName))     throw new FileNotFoundException('Configuration file not found: '.$fileName);
      if (!is_readable($fileName)) throw new IOException('File is not readable: '.$fileName);
      $content = file_get_contents($fileName, false);

      // DTD suchen, Rootverzeichnis der Library ermitteln
      $dirs = explode(DIRECTORY_SEPARATOR, dirName(__FILE__));
      while (($dir=array_pop($dirs)) !== null)
         if ($dir == 'src')
            break;
      if (sizeOf($dirs) == 0)
         throw new RuntimeException('Could not resolve root path of library, giving up.');
      $libroot = join(DIRECTORY_SEPARATOR, $dirs);

      $cwd = getCwd();
      try {
         // ins Rootverzeichnis wechseln
         chdir($libroot);
      }
      catch (Exception $ex) { throw new RuntimeException('Could not change working directory to "'.$libroot.'"', $ex); }

      // Konfiguration parsen und validieren ...
      $object = new SimpleXMLElement($content, LIBXML_DTDVALID);

      try {
         // zurück ins Ausgangsverzeichnis wechseln
         chdir($cwd);
      }
      catch (Exception $ex) { throw new RuntimeException('Could not change working directory back to "'.$cwd.'"', $ex); }

      return $object;
   }


   /**
    */
   private function initGlobalForwards(ModuleConfig $config, SimpleXMLElement $xml) {
      if ($xml->{'global-forwards'}) {
         foreach ($xml->{'global-forwards'}->forward as $elem) {
            if (sizeOf($elem->attributes()) > 2)
               throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for global forward "'.$elem['name'].'"');

            $name = (string) $elem['name'];
            $forward = null;

            if ($path = (string) $elem['include']) {
               $forward = new ActionForward($name, $path, false);
            }
            elseif ($path = (string) $elem['redirect']) {
               $forward = new ActionForward($name, $path, true);
            }
            elseif ($alias = (string) $elem['alias']) {
               $forward = $config->findForward($alias);
               if (!$forward)
                  throw new RuntimeException('No ActionForward found for alias: '.$alias);
            }
            $config->addGlobalForward($name, $forward);
         }
      }
   }


   /**
    */
   private function initMappings(ModuleConfig $config, SimpleXMLElement $xml) {
      if ($xml->{'action-mappings'}) {
         foreach ($xml->{'action-mappings'}->mapping as $elem) {
            $mapping = new ActionMapping($config);
            $mapping->setPath((string) $elem['path']);

            if (isSet($elem['action' ])) $mapping->setAction ((string) $elem['action'   ]);
            if (isSet($elem['forward'])) $mapping->setForward((string) $elem['forward']);
            if (isSet($elem['form'   ])) $mapping->setForm   ((string) $elem['form'   ]);
            if (isSet($elem['default'])) $mapping->setDefault((string) $elem['default'] == 'true');

            foreach ($elem->forward as $elem) {
               if (sizeOf($elem->attributes()) > 2)
                  throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for local forward "'.$elem['name'].'"');

               $name = (string) $elem['name'];
               $forward = null;

               if ($path = (string) $elem['include']) {
                  $forward = new ActionForward($name, $path, false);
               }
               elseif ($path = (string) $elem['redirect']) {
                  $forward = new ActionForward($name, $path, true);
               }
               elseif ($alias = (string) $elem['alias']) {
                  $forward = $mapping->findForward($alias);
                  if (!$forward)
                     throw new RuntimeException('No ActionForward found for alias: '.$alias);
               }
               $mapping->addForward($name, $forward);
            }
            $config->addActionMapping($mapping);
         }
      }
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
      $processor = $this->getRequestProcessor($this->moduleConfig);
      $processor->process($request, $response);
   }
}
?>
