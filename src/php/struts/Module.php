<?php
/**
 * Module
 */
class Module extends Object {


   private static /*bool*/ $logDebug, $logInfo, $logNotice;


   /**
    * Ob diese Komponente vollständig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder Versuch,
    * die Komponente zu ändern, eine IllegalStateException.
    */
   protected /*bool*/ $configured = false;


   /**
    * Der Prefix dieses Modules relative zur ROOT_URL der Anwendung.  Die Prefixe innerhalb einer Anwendung
    * sind eindeutig. Das Module mit einem Leerstring als Prefix ist das Default-Module der Anwendung.
    */
   protected /*string*/ $prefix;


   /**
    * Basisverzeichnisse für von diesem Modul einzubindende Resourcen
    */
   protected /*string[]*/ $resourceDirectories = array();


   /**
    * Die globalen Forwards dieses Moduls.
    */
   protected /*ActionForward[]*/ $forwards = array();


   /**
    * Die ActionMappings dieses Moduls.
    */
   protected /*ActionMapping[]*/ $mappings = array();


   /**
    * Das Default-ActionMapping dieses Moduls, wenn eines definiert wurde.
    */
   protected /*ActionMapping*/ $defaultMapping;


   /**
    * Die Tiles dieses Moduls.
    */
   protected /*Tile[]*/ $tiles = array();


   /**
    * Der Klassenname der RequestProcessor-Implementierung, die für dieses Modul definiert ist.
    */
   protected /*string*/ $requestProcessorClass = Struts ::DEFAULT_REQUEST_PROCESSOR_CLASS;


   /**
    * Der Klassenname der ActionForward-Implementierung, die für dieses Modul definiert ist.
    */
   protected /*string*/ $forwardClass = Struts ::DEFAULT_ACTION_FORWARD_CLASS;


   /**
    * Der Klassenname der ActionMapping-Implementierung, die für dieses Modul definiert ist.
    */
   protected /*string*/ $mappingClass = Struts ::DEFAULT_ACTION_MAPPING_CLASS;


   /**
    * Der Klassenname der Tiles-Implementierung, die für dieses Modul definiert ist.
    */
   protected /*string*/ $tilesClass = Struts ::DEFAULT_TILES_CLASS;


   /**
    * Der Klassenname der RoleProcessor-Implementierung, die für dieses Modul definiert ist.
    */
   protected /*string*/ $roleProcessorClass;


   /**
    * Erzeugt ein neues Modul, liest und parst dessen Konfigurationsdatei.
    *
    * @param  string $fileName - Pfad zur Konfigurationsdatei des Modules
    * @param  string $prefix   - Prefix des Modules
    *
    * TODO: Module-Encoding entsprechend dem Config-Datei-Encoding implementieren
    */
   public function __construct($fileName, $prefix) {
      if (!is_string($fileName)) throw new IllegalTypeException('Illegal type of parameter $fileName: '.getType($fileName));
      if (!is_string($prefix))   throw new IllegalTypeException('Illegal type of parameter $prefix: '.getType($prefix));

      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG);
      self::$logInfo   = ($loglevel <= L_INFO);
      self::$logNotice = ($loglevel <= L_NOTICE);

      $xml = $this->loadConfiguration($fileName);

      $this->setPrefix($prefix);
      $this->setResourceBase($xml);
      $this->processController($xml);
      $this->processForwards($xml);
      $this->processMappings($xml);
      $this->processTiles($xml);
      $this->processErrors($xml);
   }


   /**
    * Validiert die angegebene Konfigurationsdatei und wandelt sie in ein XML-Objekt um.
    *
    * @param  string $fileName - Pfad zur Konfigurationsdatei
    *
    * @return SimpleXMLElement
    */
   protected function loadConfiguration($fileName) {
      if (!is_file($fileName)) throw new FileNotFoundException('File not found: '.$fileName);
      $content = file_get_contents($fileName, false);

      // die DTD liegt im Struts-Package-Verzeichnis (src/php/struts)
      $currentDir = getCwd();                         // typically dirName(APP_ROOT.'/www/index.php');
      $packageDir = dirName(__FILE__);

      /**
       * TODO: XML ohne Verzeichniswechsel validieren
       *
       * @see  http://xmlwriter.net/xml_guide/doctype_declaration.shtml
       * @see  DTD to XML schema  https://www.w3.org/2000/04/schema_hack/
       * @see  DTD to XML schema  http://www.xmlutilities.net/
       */

      // ins Packageverzeichnis wechseln
      try { chDir($packageDir); }
      catch (Exception $ex) { throw new plRuntimeException('Could not change working directory to "'.$packageDir.'"', $ex); }

      // Konfiguration parsen und validieren
      $xml = new SimpleXMLElement($content, LIBXML_DTDVALID);

      // zurück ins Ausgangsverzeichnis wechseln
      try { chDir($currentDir); }
      catch (Exception $ex) { throw new plRuntimeException('Could not change working directory back to "'.$currentDir.'"', $ex); }

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
    * @param  string prefix
    */
   protected function setPrefix($prefix) {
      if ($this->configured)   throw new IllegalStateException('Configuration is frozen');
      if (!is_string($prefix)) throw new IllegalTypeException('Illegal type of parameter $prefix: '.getType($prefix));
      if ($prefix!=='' && $prefix{0}!='/')
         throw new IllegalTypeException('Module prefixes must start with a slash "/" character, found "'.$prefix.'"');

      $this->prefix = $prefix;
   }


   /**
    * Setzt das Basisverzeichnis für lokale Resourcen.
    *
    * @param  SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function setResourceBase(SimpleXMLElement $xml) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');

      if (!$xml['view-base']) {
         $this->resourceDirectories[] = APPLICATION_ROOT.'/app/view';
         return;
      }

      $directories = explode(',', (string) $xml['view-base']);

      foreach ($directories as $directory) {
         $dir = str_replace('\\', '/', trim($directory));

         if ($dir{0} == '/') $dir = realPath($dir);
         else                $dir = realPath(APPLICATION_ROOT.'/'.$dir);

         if (!is_dir($dir)) throw new FileNotFoundException('Directory not found: "'.$directory.'"');
         $this->resourceDirectories[] = $dir;
      }
   }


   /**
    * Verarbeitet die in der Konfiguration definierten globalen ActionForwards.
    *
    * @param  SimpleXMLElement $xml - XML-Konfiguration
    */
   protected function processForwards(SimpleXMLElement $xml) {
      // process global 'include' and 'redirect' forwards
      $elements = $xml->xPath('/struts-config/global-forwards/forward[@include] | /struts-config/global-forwards/forward[@redirect]');
      if ($elements === false)
         $elements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurück

      foreach ($elements as $tag) {
         $name = (string) $tag['name'];
         if (sizeOf($tag->attributes()) > 2) throw new plRuntimeException('Global forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

         if ($path = (string) $tag['include']) {
            if (!$this->isIncludable($path, $xml)) throw new plRuntimeException('Global forward "'.$name.'", attribute "include": '.($path{0}=='.' ? 'Tiles definition':'File').' "'.$path.'" not found');

            if ($this->isTile($path, $xml)) {
               $forward = new $this->forwardClass($name, $path, false);
            }
            else {
               $forward = new $this->forwardClass($name, $this->findFile($path), false);
               $forward->setLabel(subStr($path, 0, strRPos($path, '.')));
            }
         }
         else {
            $redirect = (string) $tag['redirect'];
            // TODO: URL validieren
            $forward = new $this->forwardClass($name, $redirect, true);
         }
         $this->addForward($name, $forward);
      }

      // process global 'forward' forwards (fragwürdig, aber möglich)
      $elements = $xml->xPath('/struts-config/global-forwards/forward[@forward]');
      if ($elements === false)
         $elements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurück

      foreach ($elements as $tag) {
         $name = (string) $tag['name'];
         if (sizeOf($tag->attributes()) > 2) throw new plRuntimeException('Global forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

         $alias = (string) $tag['forward'];
         $forward = $this->findForward($alias);
         if (!$forward) throw new plRuntimeException('Global forward "'.$name.'", attribute "forward": Forward "'.$alias.'" not found');

         $this->addForward($name, $forward);
      }
   }


   /**
    * Verarbeitet die in der Konfiguration definierten ActionMappings.
    *
    * @param  SimpleXMLElement $xml - XML-Konfiguration
    */
   protected function processMappings(SimpleXMLElement $xml) {
      $elements = $xml->xPath('/struts-config/action-mappings/mapping');
      if ($elements === false)
         $elements = array(); // xPath() gibt entgegen der Dokumentation *NICHT* immer ein Array zurück

      foreach ($elements as $tag) {
         $mapping = new $this->mappingClass($this);

         // attributes
         // ----------
         // process path attribute
         // TODO: die konfigurierten Pfade werden nicht auf Eindeutigkeit geprüft, mehrfache Definitionen derselben URL abfangen
         $path = String ::decodeUtf8((string) $tag['path']);
         $mapping->setPath($path);


         // process include attribute
         if ($tag['include']) {
            if ($mapping->getForward()) throw new plRuntimeException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
            $path = (string) $tag['include'];
            if (!$this->isIncludable($path, $xml)) throw new plRuntimeException('Mapping "'.$mapping->getPath().'", attribute "include": '.($path{0}=='.' ? 'Tiles definition':'File').' "'.$path.'" not found');

            if ($this->isTile($path, $xml)) {
               $forward = new $this->forwardClass('generic', $path, false);
            }
            else {
               $forward = new $this->forwardClass('generic', $this->findFile($path), false);
               $forward->setLabel(subStr($path, 0, strRPos($path, '.')));
            }
            $mapping->setForward($forward);
         }


         // process redirect attribute
         if ($tag['redirect']) {
            if ($mapping->getForward()) throw new plRuntimeException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
            $path = (string) $tag['redirect'];
            // TODO: URL validieren
            $forward = new $this->forwardClass('generic', $path, true);
            $mapping->setForward($forward);
         }


         // process forward attribute
         if ($tag['forward']) {
            if ($mapping->getForward()) throw new plRuntimeException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
            $path = (string) $tag['forward'];
            $forward = $this->findForward($path);
            if (!$forward) throw new plRuntimeException('Mapping "'.$mapping->getPath().'", attribute "forward": Forward "'.$path.'" not found');
            $mapping->setForward($forward);
         }
         if ($mapping->getForward() && sizeOf($tag->xPath('./forward'))) throw new plRuntimeException('Mapping "'.$mapping->getPath().'": Only an "include", "redirect" or "forward" attribute *or* nested <forward> elements must be specified');


         // process action attribute
         if ($tag['action']) {
            if ($mapping->getForward()) throw new plRuntimeException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
            $action = (string) $tag['action'];
            // TODO: URL validieren
            $mapping->setActionClassName($action);
         }


         // process form attribute
         if ($tag['form'])
            $mapping->setFormClassName((string) $tag['form']);


         // process scope attribute
         if ($tag['scope'])
            $mapping->setScope((string) $tag['scope']);


         // process validate attribute
         if ($mapping->getFormClassName()) {
            $action = $mapping->getActionClassName();
            if ($action || $mapping->getForward()) {
               $validate = $tag['validate'] ? ($tag['validate'] == 'true') : !$action;
            }
            else {
               if ($tag['validate'] == 'false') throw new plRuntimeException('Mapping "'.$mapping->getPath().'": An "action", "include", "redirect" or "forward" attribute is required when "validate" attribute is set to "false"');
               $validate = true;
               // Prüfung auf 'success' und 'error' Forward erfolgt in ActionMapping:freeze()
            }
         }
         elseif ($validate = $tag['validate'] == 'true') {
            throw new plRuntimeException('Mapping "'.$mapping->getPath().'": A "form" attribute must be specified when the "validate" attribute is set to "true"');
         }
         $mapping->setValidate($validate);


         // process method attributes
         if ($tag['methods' ]) {
            $methods = explode(',', (string) $tag['methods']);
            foreach ($methods as $method) {
               $mapping->setMethod(trim($method));
            }
         }
         else {
            $mapping->setMethod('GET');   // default: GET
         }


         // process roles attribute
         if ($tag['roles']) {
            if (!$this->roleProcessorClass) throw new plRuntimeException('Mapping "'.$mapping->getPath().'", attribute "roles": RoleProcessor configuration not found');
            $mapping->setRoles((string) $tag['roles']);
         }


         // process default attribute
         if ($tag['default'])
            $mapping->setDefault((string) $tag['default'] == 'true');


         // child nodes
         // -----------
         // process local 'include' and 'redirect' forwards
         $subElements = $tag->xPath('./forward[@include] | ./forward[@redirect]');
         if ($subElements === false)
            $subElements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurück

         foreach ($subElements as $forwardTag) {
            $name = (string) $forwardTag['name'];
            if (sizeOf($forwardTag->attributes()) > 2) throw new plRuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

            if ($path = (string) $forwardTag['include']) {
               if (!$this->isIncludable($path, $xml)) throw new plRuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'", attribute "include": '.($path{0}=='.' ? 'Tiles definition':'File').' "'.$path.'" not found');

               if ($this->isTile($path, $xml)) {
                  $forward = new $this->forwardClass($name, $path, false);
               }
               else {
                  $forward = new $this->forwardClass($name, $this->findFile($path), false);
                  $forward->setLabel(subStr($path, 0, strRPos($path, '.')));
               }
            }
            else {
               $redirect = (string) $forwardTag['redirect'];
               // TODO: URL validieren
               $forward = new $this->forwardClass($name, $redirect, true);
            }
            $mapping->addForward($name, $forward);
         }

         // process local 'forward' forwards
         $subElements = $tag->xPath('./forward[@forward]');
         if ($subElements === false)
            $subElements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurück

         foreach ($subElements as $forwardTag) {
            $name = (string) $forwardTag['name'];
            if (sizeOf($forwardTag->attributes()) > 2) throw new plRuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

            $alias = (string) $forwardTag['forward'];
            if ($alias == ActionForward ::__SELF) throw new plRuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'", attribute "forward": Can not use magic keyword "'.$alias.'" as attribute value');

            $forward = $mapping->findForward($alias);
            if (!$forward) throw new plRuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'", attribute "forward": Forward "'.$alias.'" not found');

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
    * @param  SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function processTiles(SimpleXMLElement $xml) {
      $elements = $xml->xPath('/struts-config/tiles/tile');
      if ($elements === false)
         $elements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurück

      foreach ($elements as $tag) {
         $name = (string) $tag['name'];
         $tile = $this->getDefinedTile($name, $xml);
      }
      // TODO: rekursive Tiles-Definitionen abfangen
   }


   /**
    * Sucht die Tilesdefinition mit dem angegebenen Namen und gibt die entsprechende Instanz zurück.
    *
    * @param  string           $name - Name der Tile
    * @param  SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration der Tile
    *
    * @return Tile instance
    */
   private function getDefinedTile($name, SimpleXMLElement $xml) {
      // if the tile already exists return it
      if (isSet($this->tiles[$name]))
         return $this->tiles[$name];

      // find it's definition ...
      $nodes = $xml->xPath("/struts-config/tiles/tile[@name='$name']");
      if (!$nodes)            throw new plRuntimeException('Tiles definition "'.$name.'" not found'); // FALSE oder leeres Array
      if (sizeOf($nodes) > 1) throw new plRuntimeException('Non-unique "name" attribute detected for tiles definition "'.$name.'"');


      $tag = $nodes[0];
      if (sizeOf($tag->attributes()) != 2) throw new plRuntimeException('Tile "'.$name.'": Exactly one attribute of "path" or "extends" must be specified');

      // create a new instance ...
      if ($tag['path']) {              // 'path' given, it's a simple tile
         $path = (string) $tag['path'];
         $file = $this->findFile($path);
         if (!$file) throw new FileNotFoundException('Tile "'.$name.'", attribute "path": File "'.$path.'" not found');

         $tile = new $this->tilesClass($this);
         $tile->setPath($file)
              ->setLabel(subStr($path, 0, strRPos($path, '.')));
      }
      else {                           // 'path' not given, it's an extended tile (get and clone it's parent)
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
    * @param  Tile             $tile - Tile-Instanz
    * @param  SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
    */
   private function processTileProperties(Tile $tile, SimpleXMLElement $xml) {
      foreach ($xml->set as $tag) {
         $name  = (string) $tag['name'];
         // TODO: Name-Value von <set> wird nicht auf Eindeutigkeit überprüft

         if ($tag['value']) { // value ist im Attribut angegeben
            if (strLen($tag) > 0) throw new plRuntimeException('Tile "'.$tile->getName().'", set "'.$name.'": Only a "value" attribute *or* a body value must be specified');
            $value = (string) $tag['value'];

            if ($tag['type']) {
               $type = (string) $tag['type'];
            }
            else if ($this->isIncludable($value, $xml)) {
               $type = Tile ::PROP_TYPE_RESOURCE;
            }
            else if (strEndsWithI($value, '.htm') || strEndsWithI($value, '.html')) {
               throw new plRuntimeException('Tile "'.$tile->getName().'", set "'.$name.'": specify a type="string|resource" for ambiguous attribute value="'.$value.'" (looks like a filename but file not found)');
            }
            else {
               $type = Tile ::PROP_TYPE_STRING;
            }
         }
         else {               // value ist im Body angegeben
            $value = trim((string) $tag);
            $type = ($tag['type']) ? (string) $tag['type'] : Tile ::PROP_TYPE_STRING;
            if ($type == Tile ::PROP_TYPE_RESOURCE) throw new plRuntimeException('Tile "'.$tile->getName().'", set "'.$name.'": A "value" attribute must be specified when attribute type is set to "resource"');
         }


         // Ist value eine Tile, diese initialisieren.
         if ($type == Tile ::PROP_TYPE_RESOURCE) {
            if ($this->isTile($value, $xml)) {
               $nestedTile = $this->getDefinedTile($value, $xml);
            }
            elseif ($this->isFile($value)) {       // einfache Tile erzeugen, damit render() existiert
               $nestedTile = new $this->tilesClass($this, $tile);
               $nestedTile->setName('generic')
                          ->setPath($this->findFile($value))
                          ->setLabel(subStr($value, 0, strRPos($value, '.')));
            }
            else {
               throw new plRuntimeException('Tile "'.$tile->getName().'", set "'.$name.'", attribute "value": '.($value{0}=='.' ? 'Tiles definition':'File').' "'.$value.'" not found');
            }
            $value = $nestedTile;
         }

         // TODO: bei extended Tiles Typübereinstimmung überladener Properties prüfen
         $tile->setProperty($name, $value);
      }
   }


   /**
    * Verarbeitet die in der Konfiguration definierten Error-Einstellungen.
    *
    * @param  SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function processErrors(SimpleXMLElement $xml) {
   }


   /**
    * Fügt diesem Module einen globalen ActionForward unter dem angegebenen Namen hinzu.  Der angegebene
    * Name kann vom internen Namen des Forwards abweichen, sodaß die Definition von Aliassen möglich ist
    * (ein Forward ist unter mehreren Namen auffindbar).
    *
    * @param  string        $name
    * @param  ActionForward $forward
    */
   protected function addForward($name, ActionForward $forward) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));

      if (isSet($this->forwards[$name]))
         throw new plRuntimeException('Non-unique name detected for global ActionForward "'.$name.'"');

      $this->forwards[$name] = $forward;
   }


   /**
    * Fügt diesem Module ein ActionMapping hinzu.
    *
    * @param  ActionMapping $mapping
    */
   protected function addMapping(ActionMapping $mapping) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');

      if ($mapping->isDefault()) {
         if ($this->defaultMapping) throw new plRuntimeException('Only one ActionMapping can be marked as "default" within a module.');
         $this->defaultMapping = $mapping;
      }

      $this->mappings[$mapping->getPath()] = $mapping;
   }


   /**
    * Fügt diesem Module eine Tile hinzu.
    *
    * @param  Tile $tile
    */
   protected function addTile(Tile $tile) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      $this->tiles[$tile->getName()] = $tile;
   }


   /**
    * Gibt das ActionMapping für den angegebenen Pfad zurück.
    *
    * @param  string $path
    *
    * @return ActionMapping - Mapping oder NULL, wenn kein Mapping gefunden wurde
    */
   public function findMapping($path) {
      // $path = "/"                                      oder
      // $path = "/test/uploadAccountHistory.php/info"    oder
      // $path = "/test/uploadAccountHistory.php/info/"
      $segments = explode('/', $path);
      array_shift($segments);

      $test = '';
      while ($segments) {
         $test = $test.'/'.array_shift($segments);
         if (isSet($this->mappings[$test]))
            return $this->mappings[$test];
      }
      return null;
   }


   /**
    * Gibt das Default-ActionMapping dieses Moduls zurück.
    *
    * @return ActionMapping - Mapping oder NULL, wenn kein Default-Mapping definiert ist
    */
   public function getDefaultMapping() {
      return $this->defaultMapping;
   }


   /**
    * Verarbeite Controller-Einstellungen.
    *
    * @param  SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
    */
   protected function processController(SimpleXMLElement $xml) {
      if ($this->configured)
         throw new IllegalStateException('Configuration is frozen');

      $elements = $xml->xPath('/struts-config/controller');
      if ($elements === false)
         $elements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurück

      foreach ($elements as $controller) {
         if ($controller['request-processor']) {
            $this->setRequestProcessorClass((string) $controller['request-processor']);
         }

         if ($controller['role-processor']) {
            $this->setRoleProcessorClass((string) $controller['role-processor']);
         }
      }
   }


   /**
    * Setzt den Klassennamen der RequestProcessor-Implementierung, die für dieses Module benutzt wird.
    * Diese Klasse muß eine Subklasse von RequestProcessor sein.
    *
    * @param  string $className
    */
   protected function setRequestProcessorClass($className) {
      if ($this->configured)                                                     throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                                throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
      if (!is_class($className))                                                 throw new ClassNotFoundException("Undefined class '$className'");
      if (!is_subclass_of($className, Struts ::DEFAULT_REQUEST_PROCESSOR_CLASS)) throw new plInvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_REQUEST_PROCESSOR_CLASS.': '.$className);

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
    * Setzt den Klassennamen der RoleProcessor-Implementierung, die für dieses Module benutzt wird.
    * Diese Klasse muß eine Subklasse von RoleProcessor sein.
    *
    * @param  string $className
    */
   protected function setRoleProcessorClass($className) {
      if ($this->configured)                                               throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                          throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
      if (!is_class($className))                                           throw new ClassNotFoundException("Undefined class '$className'");
      if (!is_subclass_of($className, Struts ::ROLE_PROCESSOR_BASE_CLASS)) throw new plInvalidArgumentException('Not a subclass of '.Struts ::ROLE_PROCESSOR_BASE_CLASS.': '.$className);

      $this->roleProcessorClass = $className;
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
    * @param  string $className
    */
   protected function setTilesClass($className) {
      if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                    throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
      if (!is_class($className))                                     throw new ClassNotFoundException("Undefined class '$className'");
      if (!is_subclass_of($className, Struts ::DEFAULT_TILES_CLASS)) throw new plInvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_TILES_CLASS.': '.$className);

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
    * @param  string $className
    */
   protected function setMappingClass($className) {
      if ($this->configured)                                                  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                             throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
      if (!is_class($className))                                              throw new ClassNotFoundException("Undefined class '$className'");
      if (!is_subclass_of($className, Struts ::DEFAULT_ACTION_MAPPING_CLASS)) throw new plInvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_ACTION_MAPPING_CLASS.': '.$className);

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
    * @param  string $className
    */
   protected function setForwardClass($className) {
      if ($this->configured)                                                  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                             throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
      if (!is_class($className))                                              throw new ClassNotFoundException("Undefined class '$className'");
      if (!is_subclass_of($className, Struts ::DEFAULT_ACTION_FORWARD_CLASS)) throw new plInvalidArgumentException('Not a subclass of '.Struts ::DEFAULT_ACTION_FORWARD_CLASS.': '.$className);

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
    * @param  string $name - logischer Name des ActionForwards
    *
    * @return ActionForward
    */
   public function findForward($name) {
      if (isSet($this->forwards[$name]))
         return $this->forwards[$name];

      return null;
   }


   /**
    * Gibt die Tile mit dem angegebenen Namen zurück oder NULL, wenn keine Tile mit diesem Namen
    * gefunden wurde.
    *
    * @param  string $name - logischer Name der Tile
    *
    * @return Tile
    */
   public function findTile($name) {
      if (isSet($this->tiles[$name]))
         return $this->tiles[$name];

      return null;
   }


   /**
    * Ob unter dem angegebenen Namen eine inkludierbare Resource existiert. Dies kann entweder eine
    * Tiles-Definition oder eine Datei sein.
    *
    * @param  string           $name - Name der Resource
    * @param  SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
    *
    * @return bool
    */
   private function isIncludable($name, SimpleXMLElement $xml) {
      return $this->isTile($name, $xml) || $this->isFile($name);
   }


   /**
    * Ob unter dem angegebenen Namen eine Tile definiert ist.
    *
    * @param  string           $name - Name der Tile
    * @param  SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
    *
    * @return bool
    */
   private function isTile($name, SimpleXMLElement $xml) {
      $nodes = $xml->xPath("/struts-config/tiles/tile[@name='$name']");

      if ($nodes) {                 // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurück
         if (sizeOf($nodes) > 1)
            throw new plRuntimeException('Non-unique tiles definition name "'.$name.'"');
         return true;
      }
      return false;
   }


   /**
    * Ob in den Resource-Verzeichnissen dieses Modules unter dem angegebenen Namen eine Datei existiert.
    *
    * @param  string $path - Pfadangabe
    *
    * @return bool
    */
   private function isFile($path) {
      $filename = $this->findFile($path);
      return ($filename !== null);
   }


   /**
    * Sucht in den Resource-Verzeichnissen dieses Modules nach einer Datei mit dem angegebenen Namen
    * und gibt den vollständigen Dateinamen zurück, oder NULL, wenn keine Datei mit diesem Namen
    * gefunden wurde.
    *
    * @param  string $name - relativer Dateiname
    *
    * @return string - Dateiname
    */
   private function findFile($name) {
      // strip query string
      $parts = explode('?', $name, 2);

      foreach ($this->resourceDirectories as $directory) {
         if (is_file($directory.'/'.$parts[0])) {
            $name = realPath($directory.'/'.array_shift($parts));
            if ($parts)
               $name .= '?'.$parts[0];
            return $name;
         }
      }
      return null;
   }
}
