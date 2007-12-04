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
    * Der Prefix dieses Modules relative zur ROOT_URL der Anwendung.  Die Prefixe innerhalb einer Anwendung
    * sind eindeutig. Das Module mit einem Leerstring als Prefix ist das Default-Module der Anwendung.
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
   protected $forwardClass = Struts ::DEFAULT_ACTION_FORWARD_CLASS;


   /**
    * Der Klassenname der ActionMapping-Implementierung, die für dieses Modul definiert ist.
    */
   protected $mappingClass = Struts ::DEFAULT_ACTION_MAPPING_CLASS;


   /**
    * Der Klassenname der Tiles-Implementierung, die für dieses Modul definiert ist.
    */
   protected $tilesClass = Struts ::DEFAULT_TILES_CLASS;


   /**
    * Der Klassenname der RoleProcessor-Implementierung, die für dieses Modul definiert ist.
    */
   protected $roleProcessorClass;

   // Logstatus
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

      $loglevel = Logger ::getLogLevel(__CLASS__);

      $this->logDebug  = ($loglevel <= L_DEBUG);
      $this->logInfo   = ($loglevel <= L_INFO);
      $this->logNotice = ($loglevel <= L_NOTICE);

      $xml = $this->loadConfiguration($fileName);

      $this->setPrefix($prefix);
      $this->setResourceBase($xml);
      $this->setRoleProcessorClass($xml);
      $this->processForwards($xml);
      $this->processMappings($xml);
      $this->processTiles($xml);
      $this->processErrors($xml);
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
      if (sizeOf($dirs) == 0) throw new RuntimeException('Could not resolve root path of library, giving up.');
      $libroot = join(DIRECTORY_SEPARATOR, $dirs);

      $cwd = getCwd();

      // ins Rootverzeichnis wechseln
      try { chDir($libroot); }
      catch (Exception $ex) { throw new RuntimeException('Could not change working directory to "'.$libroot.'"', $ex); }


      // Konfiguration parsen, validieren und Dateinamen hinterlegen
      $xml = new SimpleXMLElement($content, LIBXML_DTDVALID);
      $xml['config-file'] = $fileName;


      // zurück ins Ausgangsverzeichnis wechseln
      try { chDir($cwd); }
      catch (Exception $ex) { throw new RuntimeException('Could not change working directory back to "'.$cwd.'"', $ex); }

      return $xml;
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
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function setResourceBase(SimpleXMLElement $xml) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');

      $baseDirectory = dirName((string) $xml['config-file']);
      if ($xml['doc-base']) {
         $baseDirectory = realPath($baseDirectory.DIRECTORY_SEPARATOR.trim($xml['doc-base'], '/\\'));
      }
      if (!is_dir($baseDirectory)) throw new FileNotFoundException('Directory not found: '.$baseDirectory);

      // trailing slash at the end to allow people omitting the leading slash at their resources
      $this->resourceBase = $baseDirectory.'/';
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
    * Setzt den Klassennamen der RoleProcessor-Implementierung, die für dieses Module benutzt wird.
    * Diese Klasse muß eine Subklasse von RoleProcessor sein.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function setRoleProcessorClass(SimpleXMLElement $xml) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!$xml['role-processor'])
         return;

      $className = (string) $xml['role-processor'];
      if (!is_subclass_of($className, Struts ::ROLE_PROCESSOR_BASE_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::ROLE_PROCESSOR_BASE_CLASS.': '.$className);
      $this->roleProcessorClass = $className;
   }


   /**
    * Erzeugt die in der Konfiguration definierten globalen ActionForwards.
    *
    * @param SimpleXMLElement $xml - XML-Konfiguration
    */
   protected function processForwards(SimpleXMLElement $xml) {
      // process global 'include' and 'redirect' forwards
      foreach ($xml->xPath('/struts-config/global-forwards/forward[@include] | /struts-config/global-forwards/forward[@redirect]') as $tag) {
         $name = (string) $tag['name'];
         if (sizeOf($tag->attributes()) > 2) throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for global forward "'.$name.'"');

         if ($path = (string) $tag['include']) {
            if (!$this->isResource($path, $xml)) throw new RuntimeException('Resource "'.$path.'" not found for attribute "include" of global forward "'.$name.'"');
            $forward = new $this->forwardClass($name, $path, false);
         }
         else {
            $redirect = (string) $tag['redirect'];
            // TODO: URL validieren
            $forward = new $this->forwardClass($name, $redirect, true);
         }
         $this->addForward($name, $forward);
      }

      // process global 'alias' forwards
      foreach ($xml->xPath('/struts-config/global-forwards/forward[@alias]') as $tag) {
         $name = (string) $tag['name'];
         if (sizeOf($tag->attributes()) > 2) throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for global forward "'.$name.'"');

         $alias = (string) $tag['alias'];
         $forward = $this->findForward($alias);
         if (!$forward) throw new RuntimeException('Forward "'.$alias.'" not found for attribute "alias" attribute of global forward "'.$name.'"');

         $this->addForward($name, $forward);
      }
   }


   /**
    * Erzeugt die in der Konfiguration definierten ActionMappings.
    *
    * @param SimpleXMLElement $xml - XML-Konfiguration
    */
   protected function processMappings(SimpleXMLElement $xml) {
      foreach ($xml->xPath('/struts-config/action-mappings/mapping') as $tag) {
         $mapping = new $this->mappingClass($this);
         $mapping->setPath((string) $tag['path']);

         // attributes
         // ----------
         // process internal forward attribute
         if ($tag['forward']) {
            if ($tag['action']) throw new RuntimeException('Only one of "action" or "forward" can be specified for mapping "'.$mapping->getPath().'"');
            $path = (string) $tag['forward'];

            if ($this->isResource($path, $xml)) {
               $forward = new $this->forwardClass('generic', $path, false);
            }
            else {
               $forward = $this->findForward($path);
               if (!$forward) throw new RuntimeException('Resource or forward "'.$path.'" not found for attribute "forward" of mapping "'.$mapping->getPath().'"');
            }
            $mapping->setForward($forward);
         }

         // process action attribute
         if ($tag['action']) {
            $mapping->setAction((string) $tag['action']);
         }
         elseif (!$tag['forward']) {
            $className = ucFirst(baseName($mapping->getPath(), '.php')).'Action';
            if (!is_class($className)) throw new RuntimeException('Either an "action" or "forward" attribute must be specified for mapping "'.$mapping->getPath().'"');
            $mapping->setAction($className);
        }

         // process form attribute
         if ($tag['form']) {
            $mapping->setForm((string) $tag['form']);
         }
         elseif (($action = $mapping->getAction()) && is_class($action.'Form')) {
            $mapping->setForm($action.'Form');
         }

         // process form-error attribute
         if ($tag['form-error']) {
            if (!$mapping->getForm()) throw new RuntimeException('A "form" must be specified for the "form-error" attribute in mapping "'.$mapping->getPath().'"');
            $path = (string) $tag['form-error'];

            if ($this->isResource($path, $xml)) {
               $forward = new $this->forwardClass('generic', $path, false);
            }
            else {
               $forward = $this->findForward($path);
               // TODO: es muß eine Mapping-URL als Forward möglich sein
               if (!$forward) throw new RuntimeException('Resource or forward "'.$path.'" not found for attribute "form-error" of mapping "'.$mapping->getPath().'"');
            }
            $mapping->setFormErrorForward($forward);
         }

         // process method attribute
         if ($tag['method' ]) $mapping->setMethod ((string) $tag['method' ]);

         // process roles attribute
         if ($tag['roles']) {
            if (!$this->roleProcessorClass) throw new RuntimeException('RoleProcessor configuration not found for "roles" attribute "'.$tag['roles'].'" of mapping "'.$mapping->getPath().'"');
            $mapping->setRoles((string) $tag['roles']);
         }

         // process default attribute
         if ($tag['default']) $mapping->setDefault((string) $tag['default'] == 'true');


         // child nodes
         // -----------
         // process local 'include' and 'redirect' forwards
         foreach ($tag->xPath('./forward[@include] | ./forward[@redirect]') as $forwardTag) {
            $name = (string) $forwardTag['name'];
            if (sizeOf($forwardTag->attributes()) > 2) throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for forward "'.$name.'" of mapping "'.$mapping->getPath().'"');

            if ($path = (string) $forwardTag['include']) {
               if (!$this->isResource($path, $xml)) throw new RuntimeException('Resource "'.$path.'" not found for attribute "include" of forward "'.$name.'" of mapping "'.$mapping->getPath().'"');
               $forward = new $this->forwardClass($name, $path, false);
            }
            else {
               $redirect = (string) $forwardTag['redirect'];
               // TODO: URL validieren
               $forward = new $this->forwardClass($name, $redirect, true);
            }
            $mapping->addForward($name, $forward);
         }

         // process local 'alias' forwards
         foreach ($tag->xPath('./forward[@alias]') as $forwardTag) {
            $name = (string) $forwardTag['name'];
            if (sizeOf($forwardTag->attributes()) > 2) throw new RuntimeException('Only one of "include", "redirect" or "alias" must be specified for forward "'.$name.'" of mapping "'.$mapping->getPath().'"');

            $alias = (string) $forwardTag['alias'];
            if ($alias == ActionForward ::__SELF) throw new RuntimeException('Can not use protected keyword "'.$alias.'" as "alias" attribute value for forward "'.$name.'" of mapping "'.$mapping->getPath().'"');

            $forward = $mapping->findForward($alias);
            if (!$forward) throw new RuntimeException('Forward "'.$alias.'" not found for attribute "alias" of forward "'.$name.'" of mapping "'.$mapping->getPath().'"');

            $mapping->addForward($name, $forward);
         }

         // done
         // ----
         $this->addMapping($mapping);
      }
   }


   /**
    * Durchläuft alle konfigurierten Tiles.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function processTiles(SimpleXMLElement $xml) {
      foreach ($xml->xPath('/struts-config/tiles-definitions/definition') as $tag) {
         $name = (string) $tag['name'];
         $tile = $this->getDefinedTile($name, $xml);
      }
      // TODO: rekursive Tiles-Definitionen abfangen
   }


   /**
    * Sucht die Tilesdefinition mit dem angegebenen Namen und gibt die entsprechende Instanz zurück.
    *
    * @param string           $name - Name der Tile
    * @param SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration der Tile
    *
    * @return Tile instance
    */
   private function getDefinedTile($name, SimpleXMLElement $xml) {
      // if the tile already exists return it
      if (isSet($this->tiles[$name]))
         return $this->tiles[$name];

      // find it's definition ...
      $nodes = $xml->xPath("/struts-config/tiles-definitions/definition[@name='$name']");
      if (!$nodes)            throw new RuntimeException('Tiles definition not found: "'.$name.'"'); // false oder leeres Array
      if (sizeOf($nodes) > 1) throw new RuntimeException('Non-unique "name" attribute detected for tiles definition "'.$name.'"');


      $tag = $nodes[0];
      if (sizeOf($tag->attributes()) != 2) throw new RuntimeException('Exactly one of "path" or "extends" must be specified for tiles definition "'.$name.'"');

      // create a new instance ...
      if ($tag['path']) {                    // 'path' given, it's a simple tile
         $path = (string) $tag['path'];
         if (!$this->isLocalResource($path)) throw new RuntimeException('File "'.$path.'" not found in tile definition "'.$name.'"');

         $tile = new $this->tilesClass($this);
         $tile->setPath($path);
      }
      else {                                 // 'path' not given, it's an extended tile (get and clone it's parent)
         $parent = $this->getDefinedTile((string) $tag['extends'], $xml);
         $tile = clone $parent;
      }
      $tile->setName($name);

      // process it's properties ...
      $this->processTileProperties($tile, $tag);

      // ... and finally save it
      $this->addTile($tile);
      return $tile;
   }


   /**
    * Verarbeitet die in einer Tiles-Definition angegebenen zusätzlichen Properties.
    *
    * @param Tile             $tile - Tile-Instanz
    * @param SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
    */
   private function processTileProperties(Tile $tile, SimpleXMLElement $xml) {
      foreach ($xml->set as $tag) {
         $name  = (string) $tag['name'];
         // TODO: Name-Value von <set> wird nicht auf Eindeutigkeit überprüft

         if ($tag['value']) {    // value im Attribut
            if (strLen($tag)) throw new RuntimeException('Exactly one of "value" attribute or body value must be specified in set "'.$name.'" of tiles definition "'.$tile->getName().'"');
            $value = (string) $tag['value'];

            if ($tag['type']) {
               $type = (string) $tag['type'];
               if ($type == Tile ::PROP_TYPE_RESOURCE)
                  if (!$this->isResource($value, $xml)) throw new RuntimeException('Resource "'.$value.'" not found for attribute "value" in set "'.$name.'" of tiles definition "'.$tile->getName().'"');
            }
            else {
               if (String ::startsWith($value, 'layouts/') || String ::startsWith($value, 'tiles/')) {
                  $type = Tile ::PROP_TYPE_RESOURCE;
                  if (!$this->isResource($value, $xml)) throw new RuntimeException('Resource "'.$value.'" not found for attribute "value" in set "'.$name.'" of tiles definition "'.$tile->getName().'"');
               }
               else {
                  $type = $this->isResource($value, $xml) ? Tile ::PROP_TYPE_RESOURCE : Tile ::PROP_TYPE_STRING;
               }
            }
         }
         else {                  // value im Body
            $value = trim((string) $tag);

            $type = ($tag['type']) ? (string) $tag['type'] : Tile ::PROP_TYPE_STRING;
            if ($type == Tile ::PROP_TYPE_RESOURCE) throw new RuntimeException('A "value" attribute must be specified for type "resource" in set "'.$name.'" of tiles definition "'.$tile->getName().'"');
         }

         // TODO: bei extended Tiles Typübereinstimmung überladener Properties prüfen
         $tile->setProperty($name, $type, $value);
      }
   }


   /**
    * Verarbeitet die in der Konfiguration definierten Error-Einstellungen.
    *
    * @param SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function processErrors(SimpleXMLElement $xml) {
   }


   /**
    * Fügt diesem Module einen globalen ActionForward unter dem angegebenen Namen hinzu.  Der angegebene
    * Name kann vom internen Namen des Forwards abweichen, sodaß die Definition von Aliassen möglich ist
    * (ein Forward ist unter mehreren Namen auffindbar).
    *
    * @param string        $name
    * @param ActionForward $forward
    */
   protected function addForward($name, ActionForward $forward) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));

      if (isSet($this->forwards[$name]))
         throw new RuntimeException('Non-unique identifier detected for global ActionForward "'.$name.'"');

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
         if ($this->defaultMapping) throw new RuntimeException('Only one ActionMapping can be marked as "default" within a module.');
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
    * Gibt das ActionMapping für den angegebenen Pfad zurück. Zuerst wird nach einer genauen
    * Übereinstimmung gesucht und danach, wenn keines gefunden wurde, nach einem Default-ActionMapping.
    *
    * @param string $path
    *
    * @return ActionMapping
    */
   public function findMapping($path) {
      if (isSet($this->mappings[$path]))
         return $this->mappings[$path];

      // TODO: NULL statt defaultMapping zurückgeben
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
    *
    * @return Module
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


   /**
    * Sucht und gibt die Tile mit dem angegebenen Namen zurück, oder NULL, wenn keine Tile mit diesem
    * Namen gefunden wurde.
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
    * Ob unter dem angegebenen Namen eine Resource existiert. Eine gültige Resource kann eine
    * Tilesdefiniton oder eine lokale Datei mit dem angegebenen Namen sein.
    *
    * @param string           $name - Bezeichner der Resource
    * @param SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
    *
    * @return boolean
    */
   private function isResource($name, SimpleXMLElement $xml) {
      $nodes = $xml->xPath("/struts-config/tiles-definitions/definition[@name='$name']");

      if ($nodes) {
         if (sizeOf($nodes) > 1)
            throw new RuntimeException('Non-unique identifier detected for tiles definition: "'.$name.'"');
         return true;
      }

      // $nodes: false oder leeres Array
      return $this->isLocalResource($name);

      throw new RuntimeException('Resource or definition not found: "'.$name.'"');
   }


   /**
    * Ob unter dem angegebenen Namen eine lokale Resource existiert.
    *
    * @param $path - Pfadangabe
    *
    * @return boolean
    */
   private function isLocalResource($path) {
      $filename = $this->findLocalResource($path);
      return ($filename !== null);
   }


   /**
    * Sucht nach einer lokalen Resource mit dem angegebenen Namen und gibt den vollständigen Dateinamen
    * zurück, oder NULL, wenn keine Resource mit diesem Namen gefunden wurde.
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
