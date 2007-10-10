<?
/**
 * ModuleConfig
 */
class ModuleConfig extends Object {


   /**
    * Ob diese Komponente vollständig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder Versuch,
    * die Komponente zu ändern, eine IllegalStateException.
    */
   protected $configured = false;


   /**
    * Der Prefix dieses Modules relative zur ROOT_URL der Anwendung.  Die Prefixe innerhalb einer Anwendung
    * sind eindeutig.  Die ModuleConfig mit einem Leerstring als Prefix ist das Default-Module der Anwendung.
    */
   protected $prefix;      // string


   /**
    * Die globalen Forwards dieses Moduls.
    */
   protected /*ActionForward[]*/ $forwards = array();


   /**
    * Die ActionMappings dieses Moduls.
    */
   protected /*ActionMapping[]*/ $mappings = array();


   /**
    * Das Default-ActionMapping dieses Moduls oder NULL, wenn keines definiert wurde.
    */
   protected /*ActionMapping*/ $defaultMapping;


   /**
    * Der Klassenname der RequestProcessor-Implementierung, die für dieses Modul definiert ist.
    */
   protected $processorClass = Struts ::DEFAULT_PROCESSOR_CLASS;


   /**
    * Der Klassenname der ActionMapping-Implementierung, die für dieses Modul definiert ist.
    */
   protected $mappingClass   = Struts ::DEFAULT_MAPPING_CLASS;


   /**
    * Der Klassenname der ActionForward-Implementierung, die für dieses Modul definiert ist.
    */
   protected $forwardClass   = Struts ::DEFAULT_FORWARD_CLASS;


   /**
    * Erzeugt eine neue ModuleConfig.
    *
    * @param string $configfile - Pfad zur Konfigurationsdatei dieses Modules
    */
   public function __construct($configfile) {
      if (!is_string($configfile)) throw new IllegalTypeException('Illegal type of argument $configfile: '.getType($configfile));

      $xml = $this->loadConfiguration($configfile);

      $this->setPrefix((string) $xml['module']);
      $this->createForwards($xml);
      $this->createMappings($xml);
   }


   /**
    * Liest die angegebene Konfigurationsdatei in ein XML-Objekt ein (mit Validierung).
    *
    * @param string $fileName - Pfad zur Konfigurationsdatei
    *
    * @return SimpleXMLElement
    */
   protected function loadConfiguration($fileName) {
      if (!is_file($fileName))     throw new FileNotFoundException('Configuration file not found: '.$fileName);
      if (!is_readable($fileName)) throw new IOException('File is not readable: '.$fileName);
      $content = file_get_contents($fileName, false);

      // die DTD liegt relativ zum Rootverzeichnis der Library
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
    * Liest und erzeugt die im XML-Objekt definierten globalen ActionForwards.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function createForwards(SimpleXMLElement $xml) {
      if ($xml->{'global-forwards'}) {
         foreach ($xml->{'global-forwards'}->forward as $elem) {
            if (sizeOf($elem->attributes()) > 2)
               throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for global forward "'.$elem['name'].'"');

            $name = (string) $elem['name'];
            $forward = null;

            if ($path = (string) $elem['include']) {
               $forward = new $this->forwardClass($name, $path, false);
            }
            elseif ($path = (string) $elem['redirect']) {
               $forward = new $this->forwardClass($name, $path, true);
            }
            elseif ($alias = (string) $elem['alias']) {
               $forward = $this->findForward($alias);
               if (!$forward) throw new RuntimeException('No ActionForward found for alias: '.$alias);
            }
            $this->addForward($name, $forward);
         }
      }
   }


   /**
    * Liest und erzeugt die im XML-Objekt definierten ActionMappings.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function createMappings(SimpleXMLElement $xml) {
      if ($xml->{'action-mappings'}) {
         foreach ($xml->{'action-mappings'}->mapping as $elem) {
            $mapping = new $this->mappingClass($this);
            $mapping->setPath((string) $elem['path']);

            if (isSet($elem['action' ])) $mapping->setAction ((string) $elem['action' ]);
            if (isSet($elem['form'   ])) $mapping->setForm   ((string) $elem['form'   ]);
            if (isSet($elem['forward'])) $mapping->setForward((string) $elem['forward']);
            if (isSet($elem['method' ])) $mapping->setMethod ((string) $elem['method' ]);
            if (isSet($elem['roles'  ])) $mapping->setRoles  ((string) $elem['roles'  ]);
            if (isSet($elem['default'])) $mapping->setDefault((string) $elem['default'] == 'true');

            foreach ($elem->forward as $elem) {
               if (sizeOf($elem->attributes()) > 2)
                  throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for local forward "'.$elem['name'].'"');

               $name = (string) $elem['name'];
               $forward = null;

               if ($path = (string) $elem['include']) {
                  $forward = new $this->forwardClass($name, $path, false);
               }
               elseif ($path = (string) $elem['redirect']) {
                  $forward = new $this->forwardClass($name, $path, true);
               }
               elseif ($alias = (string) $elem['alias']) {
                  $forward = $mapping->findForward($alias);
                  if (!$forward) throw new RuntimeException('No ActionForward found for alias: '.$alias);
               }
               $mapping->addForward($name, $forward);
            }
            $this->addMapping($mapping);
         }
      }
   }


   /**
    * Gibt den Prefix dieser ModuleConfig zurück. Anhand dieses Prefix werde die verschiedenen Module der
    * Anwendung unterschieden.
    *
    * @return string
    */
   public function getPrefix() {
      return $this->prefix;
   }


   /**
    * Setzt den Prefix der ModuleConfig.
    *
    * @param string prefix
    */
   protected function setPrefix($prefix) {
      if ($this->configured)   throw new IllegalStateException('Configuration is frozen');
      if (!is_string($prefix)) throw new IllegalTypeException('Illegal type of argument $prefix: '.getType($prefix));
      if ($prefix!=='' && !String ::startsWith($prefix, '/'))
         throw new IllegalTypeException('Module prefixes must start with a slash "/" character, found: '.$prefix);

      $this->prefix = $prefix;
   }


   /**
    * Fügt dieser Modulkonfiguration einen globalen ActionForward unter dem angegebenen Namen hinzu.
    * Der angegebene Name kann vom internen Namen des Forwards abweichen, sodaß die Definition von Aliassen
    * möglich ist (ein Forward ist unter mehreren Namen auffindbar).
    *
    * @param string        $name
    * @param ActionForward $forward
    */
   protected function addForward($name, ActionForward $forward) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));

      if (isSet($this->forwards[$name]))
         throw new RuntimeException('Non-unique identifier detected for global ActionForward: '.$name);

      $this->forwards[$name] = $forward;
   }


   /**
    * Fügt dieser Modulkonfiguration ein ActionMapping hinzu.
    *
    * @param ActionMapping $mapping
    */
   protected function addMapping(ActionMapping $mapping) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');

      if ($mapping->isDefault()) {
         if ($this->defaultMapping)
            throw new RuntimeException('Only one ActionMapping can be marked as "default" within a module.');

         $this->defaultMapping = $mapping;
      }

      $this->mappings[$mapping->getPath()] = $mapping;
   }


   /**
    * Gibt das ActionMapping für den angegebenen Pfad zurück. Zuerst wird nach einer genauen Übereinstimmung
    * gesucht und danach, wenn keines gefunden wurde, nach einem Default-ActionMapping.
    *
    * @param string $path
    *
    * @return ActionMapping
    */
   public function findMapping($path) {
      if (isSet($this->mappings[$path]))
         return $this->mappings[$path];

      return $this->defaultMapping;
   }


   /**
    * Setzt den Klassennamen der RequestProcessor-Implementierung, die für dieses Module benutzt wird.
    * Diese Klasse muß eine Subklasse von RequestProcessor sein.
    *
    * @param string $className
    */
   protected function setProcessorClass($className) {
      if ($this->configured)                                             throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                        throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::DEFAULT_PROCESSOR_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_PROCESSOR_CLASS.': '.$className);

      $this->processorClass = $className;
   }


   /**
    * Gibt den Klassennamen der RequestProcessor-Implementierung zurück.
    *
    * @return string
    */
   public function getProcessorClass() {
      return $this->processorClass;
   }


   /**
    * Setzt den Klassennamen der ActionMapping-Implementierung, die für dieses Modul benutzt wird.
    * Diese Klasse muß eine Subklasse von ActionMapping sein.
    *
    * @param string $className
    */
   protected function setMappingClass($className) {
      if ($this->configured)                                           throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                      throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::DEFAULT_MAPPING_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_MAPPING_CLASS.': '.$className);

      $this->mappingClass = $className;
   }


   /**
    * Gibt den Klassennamen der ActionMapping-Implementierung zurück.
    *
    * @return string
    */
   public function getMappingClass() {
      return $this->mappingClass;
   }


   /**
    * Setzt den Klassennamen der ActionForward-Implementierung, die für dieses Modul benutzt wird.
    * Diese Klasse muß eine Subklasse von ActionForward sein.
    *
    * @param string $className
    */
   protected function setForwardClass($className) {
      if ($this->configured)                                           throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                      throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::DEFAULT_FORWARD_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_FORWARD_CLASS.': '.$className);

      $this->forwardClass = $className;
   }


   /**
    * Gibt den Klassennamen der ActionForward-Implementierung zurück.
    *
    * @return string
    */
   public function getForwardClass() {
      return $this->forwardClass;
   }


   /**
    * Friert die Konfiguration ein, sodaß sie nicht mehr geändert werden kann.
    *
    * @return ModuleConfig
    */
   public function freeze() {
      if (!$this->configured) {
         foreach ($this->forwards as $forward)
            $forward->freeze();

         foreach ($this->mappings as $mapping)
            $mapping->freeze();

         $this->configured = true;
      }
      return $this;
   }


   /**
    * Sucht und gibt den globalen ActionForward mit dem angegebenen Namen zurück.
    * Wird kein Forward gefunden, wird NULL zurückgegeben.
    *
    * @param $name - logischer Name des ActionForwards
    *
    * @return ActionForward
    */
   public function findForward($name) {
      if (isSet($this->forwards[$name]))
         return $this->forwards[$name];

      return null;
   }
}
?>
