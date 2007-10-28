<?
/**
 * Module
 */
class Module extends Object {


   /**
    * Ob diese Komponente vollständig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder Versuch,
    * die Komponente zu ändern, eine IllegalStateException.
    */
   protected $configured = false;


   /**
    * Der Pfad der Konfigurationsdatei dieses Moduls.
    */
   protected $configFile;     // string


   /**
    * Der Prefix dieses Modules relative zur ROOT_URL der Anwendung.  Die Prefixe innerhalb einer Anwendung
    * sind eindeutig.  Das Module mit einem Leerstring als Prefix ist das Default-Module der Anwendung.
    */
   protected $prefix;         // string


   /**
    * Das Basisverzeichnis für von diesem Modul einzubindende lokale Resourcen.
    */
   protected $resourceBase;   // string


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
    * Die Tiles dieses Moduls.
    */
   protected /*Tile[]*/ $tiles = array();


   /**
    * Der Klassenname der RequestProcessor-Implementierung, die für dieses Modul definiert ist.
    */
   protected $requestProcessorClass = Struts ::DEFAULT_REQUEST_PROCESSOR_CLASS;


   /**
    * Der Klassenname der ActionForward-Implementierung, die für dieses Modul definiert ist.
    */
   protected $forwardClass   = Struts ::DEFAULT_ACTION_FORWARD_CLASS;


   /**
    * Der Klassenname der ActionMapping-Implementierung, die für dieses Modul definiert ist.
    */
   protected $mappingClass   = Struts ::DEFAULT_ACTION_MAPPING_CLASS;


   /**
    * Der Klassenname der Tiles-Implementierung, die für dieses Modul definiert ist.
    */
   protected $tilesClass     = Struts ::DEFAULT_TILES_CLASS;


   /**
    * Der Klassenname der RoleProcessor-Implementierung, die für dieses Modul definiert ist.
    */
   protected $roleProcessorClass;


   // die definierten Resource-Pfade (werden nicht serialisiert)
   private $definedResourcePaths = array();


   private $logDebug, $logInfo, $logNotice;


   /**
    * Erzeugt ein neues Module.
    *
    * @param string $fileName - Pfad zur Konfigurationsdatei dieses Modules
    * @param string $prefix   - Prefix des Modules
    */
   public function __construct($fileName, $prefix) {
      if (!is_string($fileName)) throw new IllegalTypeException('Illegal type of argument $fileName: '.getType($fileName));
      if (!is_string($prefix))   throw new IllegalTypeException('Illegal type of argument $prefix: '.getType($prefix));

      $loglevel        = Logger ::getLogLevel(__CLASS__);
      $this->logDebug  = ($loglevel <= L_DEBUG);
      $this->logInfo   = ($loglevel <= L_INFO);
      $this->logNotice = ($loglevel <= L_NOTICE);

      $this->configFile = $fileName;
      $this->setPrefix($prefix);

      $xml = $this->loadConfiguration($fileName);
      $this->setResourceBase((string) $xml['resource-base']);

      if ($xml['role-processor'])
         $this->setRoleProcessorClass((string) $xml['role-processor']);

      $this->processForwards($xml);
      $this->processMappings($xml);
      $this->processTiles($xml);
      $this->processErrors($xml);

      $this->checkResourcePaths();
   }


   /**
    * Validiert die angegebene Konfigurationsdatei und wandelt sie in ein XML-Objekt um.
    *
    * @param string $fileName - Pfad zur Konfigurationsdatei
    *
    * @return SimpleXMLElement
    */
   protected function loadConfiguration($fileName) {
      if (!is_file($fileName)) throw new FileNotFoundException('File not found: '.$fileName);
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

      // ins Rootverzeichnis wechseln
      try { chDir($libroot); }
      catch (Exception $ex) { throw new RuntimeException('Could not change working directory to "'.$libroot.'"', $ex); }


      // Konfiguration parsen und validieren ...
      $object = new SimpleXMLElement($content, LIBXML_DTDVALID);


      // zurück ins Ausgangsverzeichnis wechseln
      try { chDir($cwd); }
      catch (Exception $ex) { throw new RuntimeException('Could not change working directory back to "'.$cwd.'"', $ex); }

      return $object;
   }


   /**
    * Verarbeitet die in der Konfiguration definierten globalen ActionForwards.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function processForwards(SimpleXMLElement $xml) {
      if ($xml->{'global-forwards'}) {
         foreach ($xml->{'global-forwards'}->forward as $element) {
            if (sizeOf($element->attributes()) > 2)
               throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for global forward "'.$element['name'].'"');

            $name = (string) $element['name'];
            $forward = null;

            if ($path = (string) $element['include']) {
               $this->definedResourcePaths[] = $path;           // tmp. save all resource paths for checking after reading the tiles configuration
               $forward = new $this->forwardClass($name, $path, false);
            }
            elseif ($path = (string) $element['redirect']) {
               $forward = new $this->forwardClass($name, $path, true);
            }
            elseif ($alias = (string) $element['alias']) {
               $forward = $this->findForward($alias);
               if (!$forward) throw new RuntimeException('No ActionForward found for alias: '.$alias);
            }
            $this->addForward($name, $forward);
         }
      }
   }


   /**
    * Verarbeitet die in der Konfiguration definierten ActionMappings.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function processMappings(SimpleXMLElement $xml) {
      if ($xml->{'action-mappings'}) {
         foreach ($xml->{'action-mappings'}->mapping as $mappingTag) {
            $mapping = new $this->mappingClass($this);
            $mapping->setPath((string) $mappingTag['path']);

            if (isSet($mappingTag['action' ])) $mapping->setAction ((string) $mappingTag['action' ]);
            if (isSet($mappingTag['form'   ])) $mapping->setForm   ((string) $mappingTag['form'   ]);
            if (isSet($mappingTag['forward'])) $mapping->setForward((string) $mappingTag['forward']);
            if (isSet($mappingTag['method' ])) $mapping->setMethod ((string) $mappingTag['method' ]);

            if (isSet($mappingTag['roles'])) {
               if (!$this->roleProcessorClass)
                  throw new RuntimeException('No RoleProcessor configuration found for role: "'.$mappingTag['roles'].'"');
               $mapping->setRoles($roles = (string) $mappingTag['roles']);
            }
            if (isSet($mappingTag['default'])) $mapping->setDefault((string) $mappingTag['default'] == 'true');

            foreach ($mappingTag->forward as $forwardTag) {
               if (sizeOf($forwardTag->attributes()) > 2)
                  throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for local forward "'.$forwardTag['name'].'"');

               $name = (string) $forwardTag['name'];
               $forward = null;

               if ($path = (string) $forwardTag['include']) {
                  $this->definedResourcePaths[] = $path;           // tmp. save all resource paths for checking after reading the tiles configuration
                  $forward = new $this->forwardClass($name, $path, false);
               }
               elseif ($path = (string) $forwardTag['redirect']) {
                  $forward = new $this->forwardClass($name, $path, true);
               }
               elseif ($alias = (string) $forwardTag['alias']) {
                  $forward = $mapping->findForward($alias);
                  if (!$forward) throw new RuntimeException('No ActionForward found for alias: "'.$alias.'"');
               }
               $mapping->addForward($name, $forward);
            }
            $this->addMapping($mapping);
         }
      }
   }


   /**
    * Verarbeitet die in der Konfiguration definierten Tiles.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function processTiles(SimpleXMLElement $xml) {
      if ($xml->{'tiles-definitions'}) {
         foreach ($xml->{'tiles-definitions'}->definition as $d) {
            $this->createTile((string) $d['name'], $xml);
         }
      }
   }


   /**
    * Erzeugt die Tile mit dem angegebenen Namen und gibt sie zurück.
    *
    * @param string           $name - Tile-Name
    * @param SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
    *
    * @return Tile
    */
   private function createTile($name, SimpleXMLElement $xml) {
      // it may already exist
      $tile = $this->findTile($name);
      if ($tile)
         return $tile;

      // no, lookup it's definition
      $definition = $tile = null;
      foreach ($xml->{'tiles-definitions'}->definition as $d) {
         if ((string)$d['name'] != $name)
            continue;
         $definition = $d;
         break;
      }
      if (!$definition)                           throw new RuntimeException('Tiles definition not found: "'.$name.'"');
      if (sizeOf($definition->attributes()) != 2) throw new RuntimeException('Exactly one of "path" or "extends" must be specified for Tiles definition "'.$name.'"');

      // create it
      if (isSet($definition['path'])) {
         // this is a simple tile
         $path = (string) $definition['path'];
         if (!$this->findLocalResource($path)) throw new RuntimeException('Resource of tile definition "'.$name.'" not found: "'.$path.'"');

         $tile = new $this->tilesClass($this);
         $tile->setName((string) $definition['name']);
         $tile->setPath($path);

         foreach ($definition->set as $property) {
            $tile->setProperty((string) $property['name'], (string) $property['value']);
         }
      }
      else {
         // this is an extended tile, so clone and modify it's parent
         $parent = $this->createTile((string) $definition['extends'], $xml);
         $tile = clone $parent;
         $tile->setName((string) $definition['name']);

         foreach ($definition->set as $property) {
            $tile->setProperty((string) $property['name'], (string) $property['value']);
         }
      }

      // save it
      $this->addTile($tile);
      return $tile;
   }


   /**
    * Verarbeitet die in der Konfiguration definierten Error-Einstellungen.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function processErrors(SimpleXMLElement $xml) {
   }


   /**
    * Gibt den Prefix dieses Modules zurück. Anhand dieses Prefix werde die verschiedenen Module der
    * Anwendung unterschieden.
    *
    * @return string
    */
   public function getPrefix() {
      return $this->prefix;
   }


   /**
    * Setzt den Prefix des Modules.
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
    * Gibt das Basisverzeichnis für lokale Resourcen zurück.
    *
    * @return string
    */
   public function getResourceBase() {
      return $this->resourceBase;
   }


   /**
    * Setzt das Basisverzeichnis für lokale Resourcen.
    *
    * @param string $directory - zur Struts-Konfiguration relatives Verzeichnis
    */
   protected function setResourceBase($directory) {
      if ($this->configured)      throw new IllegalStateException('Configuration is frozen');
      if (!is_string($directory)) throw new IllegalTypeException('Illegal type of argument $directory: '.getType($directory));

      $directory = dirName($this->configFile).'/'.trim($directory, '/\\');

      if (!is_dir($directory))
         throw new FileNotFoundException('Directory not found: '.$directory);

      // trailing slash at the end to allow people omitting leading slashs at their resource values
      $this->resourceBase = $directory.'/';
   }


   /**
    * Setzt den Klassennamen der RoleProcessor-Implementierung, die für dieses Module benutzt wird.
    * Diese Klasse muß eine Subklasse von RoleProcessor sein.
    *
    * @param string $className
    */
   protected function setRoleProcessorClass($className) {
      if ($this->configured)                                               throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                          throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::ROLE_PROCESSOR_BASE_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::ROLE_PROCESSOR_BASE_CLASS.': '.$className);

      $this->roleProcessorClass = $className;
   }


   /**
    * Fügt diesem Module einen globalen ActionForward unter dem angegebenen Namen hinzu.  Der angegebene Name kann vom
    * internen Namen des Forwards abweichen, sodaß die Definition von Aliassen möglich ist (ein Forward ist unter mehreren
    * Namen auffindbar).
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
    * Fügt diesem Module ein ActionMapping hinzu.
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
    * Fügt diesem Module eine Tile hinzu.
    *
    * @param Tile $tile
    */
   protected function addTile(Tile $tile) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      $this->tiles[$tile->getName()] = $tile;
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
   protected function setRequestProcessorClass($className) {
      if ($this->configured)                                                     throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                                throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::DEFAULT_REQUEST_PROCESSOR_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_REQUEST_PROCESSOR_CLASS.': '.$className);

      $this->requestProcessorClass = $className;
   }


   /**
    * Gibt den Klassennamen der RequestProcessor-Implementierung zurück.
    *
    * @return string
    */
   public function getRequestProcessorClass() {
      return $this->requestProcessorClass;
   }


   /**
    * Gibt die RoleProcessor-Implementierung dieses Moduls zurück.
    *
    * @return RoleProcessor
    */
   public function getRoleProcessor() {
      static $instance = null;

      if (!$instance && ($class = $this->roleProcessorClass))
         $instance = new $class;

      return $instance;
   }


   /**
    * Setzt den Klassennamen der Tiles-Implementierung, die für dieses Modul benutzt wird.
    * Diese Klasse muß eine Subklasse von Tile sein.
    *
    * @param string $className
    */
   protected function setTilesClass($className) {
      if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                    throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::DEFAULT_TILES_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_TILES_CLASS.': '.$className);

      $this->tilesClass = $className;
   }


   /**
    * Gibt den Klassennamen der Tiles-Implementierung zurück.
    *
    * @return string
    */
   public function getTilesClass() {
      return $this->tilesClass;
   }


   /**
    * Setzt den Klassennamen der ActionMapping-Implementierung, die für dieses Modul benutzt wird.
    * Diese Klasse muß eine Subklasse von ActionMapping sein.
    *
    * @param string $className
    */
   protected function setMappingClass($className) {
      if ($this->configured)                                                  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                             throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::DEFAULT_ACTION_MAPPING_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_ACTION_MAPPING_CLASS.': '.$className);

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
      if ($this->configured)                                                  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                             throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::DEFAULT_ACTION_FORWARD_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_ACTION_FORWARD_CLASS.': '.$className);

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
    */
   public function freeze() {
      if (!$this->configured) {
         foreach ($this->forwards as $forward)
            $forward->freeze();

         foreach ($this->mappings as $mapping)
            $mapping->freeze();

         foreach ($this->tiles as $tile)
            $tile->freeze();

         $this->configured = true;
      }
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


   /**
    * Überprüft alle in der Konfiguartion angegebenen lokalen Resourcen.  Diese Prüfung erfolgt erst nach dem Einlesen der Konfiguration,
    * sodaß Vorwärtsreferenzen innerhalb der Datei möglich sind.
    * z.B.: Ein ActionForward zeigt auf eine Tiles-Definition, die erst später in der Datei definiert ist.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   private function checkResourcePaths() {
      foreach ($this->definedResourcePaths as $path) {
         if ($this->findTile($path))
            continue;
         if (!$this->findLocalResource($path))
            throw new RuntimeException('Resource or definition not found: '.$path);
      }

      // we don't need this anymore
      $this->definedResourcePaths = null;
   }


   /**
    * Sucht und gibt die Tile mit dem angegebenen Namen zurück.
    * Wird keine Tile gefunden, wird NULL zurückgegeben.
    *
    * @param $name - logischer Name der Tile
    *
    * @return Tile
    */
   public function findTile($name) {
      if (isSet($this->tiles[$name]))
         return $this->tiles[$name];

      return null;
   }


   /**
    * Sucht nach einer lokalen Resource mit dem angegebenen Namen und gibt den vollständigen Dateinamen zurück
    * oder NULL, wenn keine Resource gefunden wurde.
    *
    * @param $path - Pfadangabe
    *
    * @return string - Dateiname
    */
   private function findLocalResource($path) {
      // strip query string
      $parts = explode('?', $path, 2);

      if (is_file($this->resourceBase.$parts[0]))
         return $this->resourceBase.join('?', $parts);

      return null;
   }
}
?>
