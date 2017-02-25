<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\log\Logger;

use function rosasurfer\is_class;
use function rosasurfer\strLeftTo;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;


/**
 * Module
 */
class Module extends Object {


    /** @var bool */
    private static $logDebug;

    /** @var bool */
    private static $logInfo;

    /** @var bool */
    private static $logNotice;

    /** @var bool - Ob diese Komponente vollstaendig konfiguriert ist. */
    protected $configured = false;

    /**
     * Der Prefix dieses Modules relative zur ROOT_URL der Anwendung.  Die Prefixe innerhalb einer Anwendung
     * sind eindeutig. Das Module mit einem Leerstring als Prefix ist das Default-Module der Anwendung.
     *
     * @var string
     */
    protected $prefix;

    /** @var string[] - Basisverzeichnisse fuer von diesem Modul einzubindende Resourcen */
    protected $resourceLocations = [];

    /** @var ActionForward[] - Die globalen Forwards dieses Moduls. */
    protected $forwards = [];

    /** @var ActionMapping[] - Die ActionMappings dieses Moduls. */
    protected $mappings = [];

    /** @var ActionMapping|null - Das Default-ActionMapping dieses Moduls, wenn eines definiert wurde. */
    protected $defaultMapping;

    /** @var Tile[] - Die Tiles dieses Moduls. */
    protected $tiles = [];

    /** @var string - Der Klassenname der RequestProcessor-Implementierung, die fuer dieses Modul definiert ist. */
    protected $requestProcessorClass = DEFAULT_REQUEST_PROCESSOR_CLASS;

    /** @var string - Der Klassenname der ActionForward-Implementierung, die fuer dieses Modul definiert ist. */
    protected $forwardClass = DEFAULT_ACTION_FORWARD_CLASS;

    /** @var string - Der Klassenname der ActionMapping-Implementierung, die fuer dieses Modul definiert ist. */
    protected $mappingClass = DEFAULT_ACTION_MAPPING_CLASS;

    /** @var string - Der Klassenname der Tiles-Implementierung, die fuer dieses Modul definiert ist. */
    protected $tilesClass = DEFAULT_TILES_CLASS;

    /** @var string - Der Klassenname der RoleProcessor-Implementierung, die fuer dieses Modul definiert ist. */
    protected $roleProcessorClass;

    /** @var RoleProcessor - Die RoleProcessor-Implementierung, die fuer dieses Modul definiert ist. */
    protected $roleProcessor;


    /**
     * Erzeugt ein neues Modul, liest und parst dessen Konfigurationsdatei.
     *
     * @param  string $fileName - Pfad zur Konfigurationsdatei des Modules
     * @param  string $prefix   - Prefix des Modules
     *
     * @throws StrutsConfigException on configuration errors
     *
     * TODO: Module-Encoding entsprechend dem Config-Datei-Encoding implementieren
     */
    public function __construct($fileName, $prefix) {
        if (!is_string($fileName)) throw new IllegalTypeException('Illegal type of parameter $fileName: '.getType($fileName));
        if (!is_string($prefix))   throw new IllegalTypeException('Illegal type of parameter $prefix: '.getType($prefix));

        $loglevel        = Logger::getLogLevel(__CLASS__);
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
     * @return \SimpleXMLElement
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function loadConfiguration($fileName) {
        if (!is_file($fileName)) throw new StrutsConfigException('Configuration file not found: "'.$fileName.'"');
        $content = file_get_contents($fileName, false);

        /**
         * TODO: struts-config.xml ohne Verzeichniswechsel validieren
         *
         * @see  http://xmlwriter.net/xml_guide/doctype_declaration.shtml
         * @see  DTD to XML schema  https://www.w3.org/2000/04/schema_hack/
         * @see  DTD to XML schema  http://www.xmlutilities.net/
         */

        // ins DTD-Verzeichnis wechseln: "./xml"
        $workingDir = getCwd();
        $dtdDir     = __DIR__.'/xml';
        chDir($dtdDir);

        // Konfiguration parsen und validieren
        $xml = new \SimpleXMLElement($content, LIBXML_DTDVALID);

        // zurueck ins Ausgangsverzeichnis wechseln
        chDir($workingDir);

        return $xml;
    }


    /**
     * Gibt den Prefix dieses Modules zurueck. Anhand des Prefixes werden die verschiedenen Module der
     * Anwendung unterschieden.
     *
     * @return string
     *
     * TODO: If non-empty the prefix must never start and always end with a slash "/".
     */
    public function getPrefix() {
        return $this->prefix;
    }


    /**
     * Setzt den Prefix des Modules.
     *
     * @param  string prefix
     *
     * TODO: If non-empty the prefix must never start and always end with a slash "/".
     */
    protected function setPrefix($prefix) {
        if ($this->configured)   throw new IllegalStateException('Configuration is frozen');
        if (!is_string($prefix)) throw new IllegalTypeException('Illegal type of parameter $prefix: '.getType($prefix));
        if ($prefix!=='' && $prefix{0}!='/')
            throw new IllegalTypeException('Module prefixes must start with a slash "/" character, found "'.$prefix.'"');

        $this->prefix = $prefix;
    }


    /**
     * Setzt das Basisverzeichnis fuer lokale Resourcen.
     *
     * @param  \SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setResourceBase(\SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if (!$xml['file-base']) {
            // not specified, apply default settings
            $this->resourceLocations[] = APPLICATION_ROOT.'/app/view';
            return;
        }
        $locations = explode(',', (string) $xml['file-base']);

        foreach ($locations as $i => $location) {
            $location = trim($location);
            if (!strLen($location)) continue;

            $relativePath = WINDOWS ? !preg_match('/^[a-z]:/i', $location) : ($location[0]!='/');
            $relativePath && $location=APPLICATION_ROOT.DIRECTORY_SEPARATOR.$location;

            if (!is_dir($location)) throw new StrutsConfigException('Resource location <struts-config file-base="'.$locations[$i].'" not found');

            $this->resourceLocations[] = realPath($location);
        }
    }


    /**
     * Verarbeitet die in der Konfiguration definierten globalen ActionForwards.
     *
     * @param  \SimpleXMLElement $xml - XML-Konfiguration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function processForwards(\SimpleXMLElement $xml) {
        // process global 'include' and 'redirect' forwards
        $elements = $xml->xPath('/struts-config/global-forwards/forward[@include] | /struts-config/global-forwards/forward[@redirect]') ?: [];

        foreach ($elements as $tag) {
            $name = (string) $tag['name'];
            if (sizeOf($tag->attributes()) > 2) throw new StrutsConfigException('Global forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

            if ($path = (string) $tag['include']) {
                if (!$this->isIncludable($path, $xml)) throw new StrutsConfigException('Global forward "'.$name.'", attribute "include": '.($path[0]=='.' ? 'Tiles definition':'File').' "'.$path.'" not found');

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

        // process global 'forward' forwards (fragwuerdig, aber moeglich)
        $elements = $xml->xPath('/struts-config/global-forwards/forward[@forward]') ?: [];

        foreach ($elements as $tag) {
            $name = (string) $tag['name'];
            if (sizeOf($tag->attributes()) > 2) throw new StrutsConfigException('Global forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

            $alias = (string) $tag['forward'];
            $forward = $this->findForward($alias);
            if (!$forward) throw new StrutsConfigException('Global forward "'.$name.'", attribute "forward": Forward "'.$alias.'" not found');

            $this->addForward($name, $forward);
        }
    }


    /**
     * Verarbeitet die in der Konfiguration definierten ActionMappings.
     *
     * @param  \SimpleXMLElement $xml - XML-Konfiguration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function processMappings(\SimpleXMLElement $xml) {
        $elements = $xml->xPath('/struts-config/action-mappings/mapping') ?: [];

        foreach ($elements as $tag) {
            $mapping = new $this->mappingClass($this);

            // attributes
            // ----------
            // process path attribute
            $path = (string) $tag['path'];
            $mapping->setPath($path);


            // process include attribute
            if ($tag['include']) {
                if ($mapping->getForward()) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
                $path = (string) $tag['include'];
                if (!$this->isIncludable($path, $xml)) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'", attribute "include": '.($path{0}=='.' ? 'Tiles definition':'File').' "'.$path.'" not found');

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
                if ($mapping->getForward()) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
                $path = (string) $tag['redirect'];
                // TODO: URL validieren
                $forward = new $this->forwardClass('generic', $path, true);
                $mapping->setForward($forward);
            }


            // process forward attribute
            if ($tag['forward']) {
                if ($mapping->getForward()) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
                $path = (string) $tag['forward'];
                $forward = $this->findForward($path);
                if (!$forward) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'", attribute "forward": Forward "'.$path.'" not found');
                $mapping->setForward($forward);
            }
            if ($mapping->getForward() && sizeOf($tag->xPath('./forward'))) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'": Only an "include", "redirect" or "forward" attribute *or* nested <forward> elements must be specified');


            // process action attribute
            if ($tag['action']) {
                if ($mapping->getForward()) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
                $action = (string) $tag['action'];
                // TODO: URL validieren
                $mapping->setActionClassName($action);
            }


            // process form attribute
            if ($tag['form'])
                $mapping->setFormClassName((string) $tag['form']);


            // process form-scope attribute
            if ($tag['form-scope'])
                $mapping->setFormScope((string) $tag['form-scope']);


            // process form-validate-first attribute
            if ($mapping->getFormClassName()) {
                $action = $mapping->getActionClassName();
                if ($action || $mapping->getForward()) {
                    $formValidateFirst = $tag['form-validate-first'] ? ($tag['form-validate-first']=='true') : !$action;
                }
                else {
                    if ($tag['form-validate-first']=='false') throw new StrutsConfigException('Mapping "'.$mapping->getPath().'": An "action", "include", "redirect" or "forward" attribute is required when "form-validate-first" attribute is set to "false"');
                    $formValidateFirst = true;
                    // Pruefung auf 'success' und 'error' Forward erfolgt in ActionMapping:freeze()
                }
            }
            elseif ($formValidateFirst = $tag['form-validate-first']=='true') {
                throw new StrutsConfigException('Mapping "'.$mapping->getPath().'": A "form" attribute must be specified when the "form-validate-first" attribute is set to "true"');
            }
            $mapping->setFormValidateFirst($formValidateFirst);


            // process method attributes
            if ($tag['http-methods' ]) {
                $methods = explode(',', (string) $tag['http-methods']);
                foreach ($methods as $method) {
                    $mapping->setMethod(trim($method));
                }
            }
            else {
                $mapping->setMethod('GET');   // default: GET
            }


            // process roles attribute
            if ($tag['roles']) {
                if (!$this->roleProcessorClass) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'", attribute "roles": RoleProcessor configuration not found');
                $mapping->setRoles((string) $tag['roles']);
            }


            // process default attribute
            if ($tag['default'])
                $mapping->setDefault((string) $tag['default'] == 'true');


            // child nodes
            // -----------
            // process local 'include' and 'redirect' forwards
            $subElements = $tag->xPath('./forward[@include] | ./forward[@redirect]') ?: [];

            foreach ($subElements as $forwardTag) {
                $name = (string) $forwardTag['name'];
                if (sizeOf($forwardTag->attributes()) > 2) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'", forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

                if ($path = (string) $forwardTag['include']) {
                    if (!$this->isIncludable($path, $xml)) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'", forward "'.$name.'", attribute "include": '.($path{0}=='.' ? 'Tiles definition':'File').' "'.$path.'" not found');

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
            $subElements = $tag->xPath('./forward[@forward]') ?: [];

            foreach ($subElements as $forwardTag) {
                $name = (string) $forwardTag['name'];
                if (sizeOf($forwardTag->attributes()) > 2) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'", forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

                $alias = (string) $forwardTag['forward'];
                if ($alias == ActionForward::__SELF) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'", forward "'.$name.'", attribute "forward": Can not use magic keyword "'.$alias.'" as attribute value');

                $forward = $mapping->findForward($alias);
                if (!$forward) throw new StrutsConfigException('Mapping "'.$mapping->getPath().'", forward "'.$name.'", attribute "forward": Forward "'.$alias.'" not found');

                $mapping->addForward($name, $forward);
            }

            // done
            // ----
            $this->addMapping($mapping);
        }
    }


    /**
     * Durchlaeuft alle konfigurierten Tiles.
     *
     * @param  \SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function processTiles(\SimpleXMLElement $xml) {
        $elements = $xml->xPath('/struts-config/tiles/tile') ?: [];

        foreach ($elements as $tag) {
            $name = (string) $tag['name'];
            $tile = $this->getDefinedTile($name, $xml);     // throws exception on tile configuration errors
        }
    }


    /**
     * Sucht die Tilesdefinition mit dem angegebenen Namen und gibt die entsprechende Instanz zurueck.
     *
     * @param  string            $name - Name der Tile
     * @param  \SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration der Tile
     *
     * @return Tile
     *
     * @throws StrutsConfigException on configuration errors
     */
    private function getDefinedTile($name, \SimpleXMLElement $xml) {
        // if the tile already exists return it
        if (isSet($this->tiles[$name]))
            return $this->tiles[$name];

        // TODO: zirkulare Tiles-Referenzen abfangen

        // find it's definition ...
        $nodes = $xml->xPath("/struts-config/tiles/tile[@name='".$name."']");
        if (!$nodes)            throw new StrutsConfigException('Tile named "'.$name.'" not found');
        if (sizeOf($nodes) > 1) throw new StrutsConfigException('Multiple tiles named "'.$name.'" found');

        $tag = $nodes[0];
        if (sizeOf($tag->attributes()) != 2) throw new StrutsConfigException('<tile name="'.$name.'": exactly one attribute of "file", "extends-tile" or "alias" must be specified');

        // check for an alias
        if ($tag['alias']) {
            $alias = (string) $tag['alias'];
            $tile  = $this->getDefinedTile($alias, $xml);
            $this->addTile($tile, $name);
            return $tile;
        }

        // create a new instance ...
        if ($tag['file']) {                 // 'file' given
            $fileAttr = (string) $tag['file'];
            $filePath = $this->findFile($fileAttr);
            if (!$filePath) throw new StrutsConfigException('<tile name="'.$name.'" file="'.$fileAttr.'": file not found');

            $tile = new $this->tilesClass($this);
            $tile->setFileName($filePath);
        }
        else {                           // 'file' not given, it's extending another tile (get and clone it)
            $other = $this->getDefinedTile((string) $tag['extends-tile'], $xml);
            $tile = clone $other;
        }
        $tile->setName($name);

        // process it's child nodes ...
        $this->processTileChildNodes($tile, $tag);

        // ... and finally save it
        $this->addTile($tile);
        return $tile;
    }


    /**
     * Verarbeitet die in einer Tiles-Definition angegebenen Child-Nodes.
     *
     * @param  Tile              $tile - Tile-Instanz
     * @param  \SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
     *
     * @throws StrutsConfigException on configuration errors
     */
    private function processTileChildNodes(Tile $tile, \SimpleXMLElement $xml) {
        // process <set> elements
        foreach ($xml->{'set'} as $tag) {
            $name  = (string) $tag['name'];
            $nodes = $xml->xPath("/struts-config/tiles/tile[@name='".$tile->getName()."']/set[@name='".$name."']");
            if (sizeOf($nodes) > 1) throw new StrutsConfigException('<tile name="'.$tile->getName().'"... multiple childnodes <set name="'.$name.'" found');
            if (sizeOf($tag->attributes()) > 2) throw new StrutsConfigException('<tile name="'.$tile->getName().'"... childnode <set name="'.$name.'": exactly one attribute of "file" or "tile" must be specified');

            $nestedTile = null;

            if ($tag['file']) {                             // attribute 'file' specified
                $fileAttr = (string) $tag['file'];
                $filePath = $this->findFile($fileAttr);
                if (!$filePath) throw new StrutsConfigException('<tile name="'.$tile->getName().'"... childnode <set name="'.$name.'" file="'.$fileAttr.'": file not found');
                // generische Tile erzeugen, damit render() existiert
                $nestedTile = new $this->tilesClass($this, $tile);
                $nestedTile->setName(Tile::GENERIC_NAME)
                           ->setFileName($filePath);
            }
            else if ($tag['tile']) {                        // attribute 'tile' specified
                $tileAttr   = (string) $tag['tile'];
                $nestedTile = $this->getDefinedTile($tileAttr, $xml);
            }
            else {                                          // not yet defined, it's a template
                $nestedTile = null;
            }
            $tile->setNestedTile($name, $nestedTile);
        }

        // process <set-property> elements
        foreach ($xml->{'set-property'} as $tag) {
            // TODO: Property-Namen auf Eindeutigkeit ueberpruefen
            // TODO: Typuebereinstimmung ueberladener Properties mit der Extended-Tile pruefen
            //$tile->setProperty($name, $value);
        }
    }


    /**
     * Verarbeitet die in der Konfiguration definierten Error-Einstellungen.
     *
     * @param  \SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function processErrors(\SimpleXMLElement $xml) {
    }


    /**
     * Fuegt diesem Module einen globalen ActionForward unter dem angegebenen Namen hinzu.  Der angegebene
     * Name kann vom internen Namen des Forwards abweichen, sodass die Definition von Aliassen moeglich ist
     * (ein Forward ist unter mehreren Namen auffindbar).
     *
     * @param  string        $name
     * @param  ActionForward $forward
     */
    protected function addForward($name, ActionForward $forward) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));

        if (isSet($this->forwards[$name]))
            throw new RuntimeException('Non-unique name detected for global ActionForward "'.$name.'"');

        $this->forwards[$name] = $forward;
    }


    /**
     * Fuegt diesem Module ein ActionMapping hinzu.
     *
     * @param  ActionMapping $mapping
     */
    protected function addMapping(ActionMapping $mapping) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if ($mapping->isDefault()) {
            if ($this->defaultMapping) throw new RuntimeException('Only one ActionMapping can be marked as "default" within a module.');
            $this->defaultMapping = $mapping;
        }

        $path = $mapping->getPath();
        if (isSet($this->mappings[$path]))
            throw new RuntimeException('All action mappings must have unique path attributes, non-unique path: "'.$path.'"');

        $this->mappings[$path] = $mapping;
    }


    /**
     * Fuegt diesem Module eine Tile hinzu.
     *
     * @param  Tile        $tile
     * @param  string|null $alias - alias name of the tile (default: none)
     */
    protected function addTile(Tile $tile, $alias=null) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $name = $tile->getName();
        $this->tiles[$name] = $tile;

        if (!is_null($alias))
            $this->tiles[$alias] = $tile;
    }


    /**
     * Gibt das ActionMapping fuer den angegebenen Pfad zurueck.
     *
     * @param  string $path
     *
     * @return ActionMapping|null - Mapping oder NULL, wenn kein Mapping gefunden wurde
     */
    public function findMapping($path) {
        // $path: /
        // $path: /action/
        // $path: /controller/action/
        // $path: /controller/action/parameter/

        $pattern = $path;
        while (strLen($pattern)) {                      // mappings start and end with a slash "/"
            if (isSet($this->mappings[$pattern]))
                return $this->mappings[$pattern];
            $pattern = strLeftTo($pattern, '/', $count=-2, $includeLimiter=true);
            if ($pattern == '/')
                break;
        }
        return null;
    }


    /**
     * Gibt das Default-ActionMapping dieses Moduls zurueck.
     *
     * @return ActionMapping|null - Mapping oder NULL, wenn kein Default-Mapping definiert ist
     */
    public function getDefaultMapping() {
        return $this->defaultMapping;
    }


    /**
     * Verarbeitet Controller-Einstellungen.
     *
     * @param  \SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function processController(\SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $elements = $xml->xPath('/struts-config/controller') ?: [];

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
     * Setzt den Klassennamen der RequestProcessor-Implementierung, die fuer dieses Module benutzt wird.
     * Diese Klasse muss eine Subklasse von RequestProcessor sein.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setRequestProcessorClass($className) {
        if ($this->configured)                                            throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                                        throw new StrutsConfigException('Class "'.$className.'" not found');
        if (!is_subclass_of($className, DEFAULT_REQUEST_PROCESSOR_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_REQUEST_PROCESSOR_CLASS.': "'.$className.'"');

        $this->requestProcessorClass = $className;
    }


    /**
     * Gibt den Klassennamen der RequestProcessor-Implementierung zurueck.
     *
     * @return string
     */
    public function getRequestProcessorClass() {
        return $this->requestProcessorClass;
    }


    /**
     * Setzt den Klassennamen der RoleProcessor-Implementierung, die fuer dieses Module benutzt wird.
     * Diese Klasse muss eine Subklasse von RoleProcessor sein.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setRoleProcessorClass($className) {
        if ($this->configured)                                      throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                                  throw new StrutsConfigException('Class "'.$className.'" not found');
        if (!is_subclass_of($className, ROLE_PROCESSOR_BASE_CLASS)) throw new StrutsConfigException('Not a subclass of '.ROLE_PROCESSOR_BASE_CLASS.': "'.$className.'"');

        $this->roleProcessorClass = $className;
    }


    /**
     * Gibt die RoleProcessor-Implementierung dieses Moduls zurueck.
     *
     * @return RoleProcessor
     */
    public function getRoleProcessor() {
        if (!$this->roleProcessor) {
            $class = $this->roleProcessorClass;
            $this->roleProcessor = new $class();
        }
        return $this->roleProcessor;
    }


    /**
     * Setzt den Klassennamen der Tiles-Implementierung, die fuer dieses Modul benutzt wird.
     * Diese Klasse muss eine Subklasse von Tile sein.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setTilesClass($className) {
        if ($this->configured)                                throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                            throw new StrutsConfigException('Class "'.$className.'" not found');
        if (!is_subclass_of($className, DEFAULT_TILES_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_TILES_CLASS.': "'.$className.'"');

        $this->tilesClass = $className;
    }


    /**
     * Gibt den Klassennamen der Tiles-Implementierung zurueck.
     *
     * @return string
     */
    public function getTilesClass() {
        return $this->tilesClass;
    }


    /**
     * Setzt den Klassennamen der ActionMapping-Implementierung, die fuer dieses Modul benutzt wird.
     * Diese Klasse muss eine Subklasse von ActionMapping sein.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setMappingClass($className) {
        if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                                     throw new StrutsConfigException('Class "'.$className.'" not found');
        if (!is_subclass_of($className, DEFAULT_ACTION_MAPPING_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_ACTION_MAPPING_CLASS.': "'.$className.'"');

        $this->mappingClass = $className;
    }


    /**
     * Gibt den Klassennamen der ActionMapping-Implementierung zurueck.
     *
     * @return string
     */
    public function getMappingClass() {
        return $this->mappingClass;
    }


    /**
     * Setzt den Klassennamen der ActionForward-Implementierung, die fuer dieses Modul benutzt wird.
     * Diese Klasse muss eine Subklasse von ActionForward sein.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setForwardClass($className) {
        if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                                     throw new StrutsConfigException('Class "'.$className.'" not found');
        if (!is_subclass_of($className, DEFAULT_ACTION_FORWARD_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_ACTION_FORWARD_CLASS.': "'.$className.'"');

        $this->forwardClass = $className;
    }


    /**
     * Gibt den Klassennamen der ActionForward-Implementierung zurueck.
     *
     * @return string
     */
    public function getForwardClass() {
        return $this->forwardClass;
    }


    /**
     * Friert die Konfiguration ein, sodass sie nicht mehr geaendert werden kann.
     *
     * @return self
     */
    public function freeze() {
        if (!$this->configured) {
            foreach ($this->forwards as $forward) $forward->freeze();
            foreach ($this->mappings as $mapping) $mapping->freeze();
            foreach ($this->tiles    as $tile)    $tile->freeze();

            $this->configured = true;
        }
        return $this;
    }


    /**
     * Sucht und gibt den globalen ActionForward mit dem angegebenen Namen zurueck.
     * Wird kein Forward gefunden, wird NULL zurueckgegeben.
     *
     * @param  string $name - logischer Name des ActionForwards
     *
     * @return ActionForward|null
     */
    public function findForward($name) {
        if (isSet($this->forwards[$name]))
            return $this->forwards[$name];

        return null;
    }


    /**
     * Gibt die Tile mit dem angegebenen Namen zurueck oder NULL, wenn keine Tile mit diesem Namen
     * gefunden wurde.
     *
     * @param  string $name - logischer Name der Tile
     *
     * @return Tile|null
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
     * @param  string            $name - Name der Resource
     * @param  \SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
     *
     * @return bool
     */
    private function isIncludable($name, \SimpleXMLElement $xml) {
        return $this->isTile($name, $xml) || $this->isFile($name);
    }


    /**
     * Ob unter dem angegebenen Namen eine Tile definiert ist.
     *
     * @param  string            $name - Name der Tile
     * @param  \SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
     *
     * @return bool
     *
     * @throws StrutsConfigException on configuration errors
     */
    private function isTile($name, \SimpleXMLElement $xml) {
        $nodes = $xml->xPath("/struts-config/tiles/tile[@name='$name']") ?: [];
        if (sizeOf($nodes) > 1) throw new StrutsConfigException('Non-unique tiles definition name "'.$name.'"');
        return !empty($nodes);
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
     * und gibt den vollstaendigen Dateinamen zurueck, oder NULL, wenn keine Datei mit diesem Namen
     * gefunden wurde.
     *
     * @param  string $name - relativer Dateiname
     *
     * @return string|null - Dateiname
     */
    private function findFile($name) {
        // strip query string
        $parts = explode('?', $name, 2);

        foreach ($this->resourceLocations as $location) {
            if (is_file($location.DIRECTORY_SEPARATOR.$parts[0])) {
                $name = realPath($location.DIRECTORY_SEPARATOR.array_shift($parts));
                if ($parts)
                    $name .= '?'.$parts[0];
                return $name;
            }
        }
        return null;
    }
}
