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


   const STRUTS4P_CONFIG_FILE = 'struts4p-config.xml';


   // es gibt zur Zeit nur ein Modul (daß sich nicht ändern kann)
   private /* ModuleConfig */ $moduleConfig;


   /**
    * Konstruktor.
    *
    * Lädt die Struts-Konfiguration, parst und kompiliert sie.
    */
   protected function __construct() {
      try {
         $xmlObject = $this->loadConfiguration();

         $config = new ModuleConfig();
         $this->initGlobalForwards($config, $xmlObject);
         $this->initMappings($config, $xmlObject);
         $config->freeze();

         $this->moduleConfig = $config;
      }
      catch (Exception $ex) {
         throw new RuntimeException('Error loading '.self ::STRUTS4P_CONFIG_FILE, $ex);
      }
   }


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück. Ist ein Cache installiert, wird sie gecacht.
    * Dadurch muß die Konfiguration nicht bei jedem Request neu eingelesen werden.
    *
    * @return FrontController
    */
   public static function me() {

      // development only (don't uses cache)
      //return Singleton ::getInstance(__CLASS__);
      //

      $instance = Cache ::get($key = __CLASS__.'_instance');
      if (!$instance) {
         $instance = Singleton ::getInstance(__CLASS__);
         Cache ::set($key, $instance);
      }
      return $instance;
   }


   /**
    * @return SimpleXMLElement
    */
   private function loadConfiguration() {
      $fileName = self ::STRUTS4P_CONFIG_FILE;
      //if (!is_file($fileName))     throw new FileNotFoundException('No such file: '.$fileName);
      //if (!is_readable($fileName)) throw new IOException('File is not readable: '.$fileName);

      $xml = file_get_contents($fileName, true);      // !!! der Include-Path darf nicht durchsucht werden !!!


      // Rootverzeichnis der Library ermitteln
      $dirs = explode(DIRECTORY_SEPARATOR, dirName(__FILE__));
      while (($dir=array_pop($dirs)) !== null)
         if ($dir == 'src')
            break;
      if (sizeOf($dirs) == 0)
         throw new RuntimeException('Could not resolve root path of library, giving up.');
      $libroot = join(DIRECTORY_SEPARATOR, $dirs);

      // aktuelles Verzeichnis merken und ins Rootverzeichnis wechseln, damit die DTD gefunden wird
      $cwd = getCwd();
      try {
         chdir($libroot);
      }
      catch (Exception $ex) { throw new RuntimeException('Could not change working directory to "'.$libroot.'"', $ex); }

      // Datei laden und validieren ...
      $object = new SimpleXMLElement($xml, LIBXML_DTDVALID);

      // ... und zurück ins Ausgangsverzeichnis
      try {
         chdir($cwd);
      }
      catch (Exception $ex) { throw new RuntimeException('Could not change working directory back to "'.$cwd.'"', $ex); }

      return $object;
   }


   /**
    */
   private function initGlobalForwards(ModuleConfig $config, SimpleXMLElement $xml) {
      if ($xml->{'global-forwards'}) {
         foreach ($xml->{'global-forwards'}->forward as $forward) {
            $config->addGlobalForward(new ActionForward((string) $forward['name'],
                                                        (string) $forward['path'],
                                                        (string) $forward['redirect'] == 'true'));
         }
      }
   }


   /**
    */
   private function initMappings(ModuleConfig $config, SimpleXMLElement $xml) {
      if ($xml->{'action-mappings'}) {

         foreach ($xml->{'action-mappings'}->action as $action) {
            $mapping = ActionMapping ::create()->setPath((string) $action['path'])
                                               ->setAction((string) $action['name'])
                                               ->setDefault((string) $action['default'] == 'true');
            foreach ($action->forward as $forward) {
               $mapping->addForward(new ActionForward((string) $forward['name'],
                                                      (string) $forward['path'],
                                                      (string) $forward['redirect'] == 'true'));
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
      $processor = new $class($config);
      return $processor;
   }


   /**
    * Verarbeitet den aktuellen Request.
    */
   public function processRequest() {
      $request = Request ::me();
      $processor = $this->getRequestProcessor($this->moduleConfig);
      $processor->process($request);
   }
}
?>
