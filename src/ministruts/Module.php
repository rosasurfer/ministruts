<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;

use rosasurfer\exception\ClassNotFoundException;
use rosasurfer\exception\FileNotFoundException;
use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\log\Logger;

use function rosasurfer\is_class;
use function rosasurfer\strEndsWithI;
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
    protected $resourceDirectories = [];

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
     */
    protected function loadConfiguration($fileName) {
        if (!is_file($fileName)) throw new FileNotFoundException('File not found: '.$fileName);
        $content = file_get_contents($fileName, false);

        /**
         * TODO: struts-config.xml ohne Verzeichniswechsel validieren
         *
         * @see  http://xmlwriter.net/xml_guide/doctype_declaration.shtml
         * @see  DTD to XML schema  https://www.w3.org/2000/04/schema_hack/
         * @see  DTD to XML schema  http://www.xmlutilities.net/
         */
        $currentDir = getCwd();                         // typically dirName(APP_ROOT.'/www/index.php');

        // ins DTD-Verzeichnis wechseln: src/ministruts/xml
        $dtdDir = __DIR__.'/xml';
        try { chDir($dtdDir); }
        catch (\Exception $ex) { throw new RuntimeException('Could not change working directory to "'.$dtdDir.'"', null, $ex); }

        // Konfiguration parsen und validieren
        $xml = new \SimpleXMLElement($content, LIBXML_DTDVALID);

        // zurueck ins Ausgangsverzeichnis wechseln
        try { chDir($currentDir); }
        catch (\Exception $ex) { throw new RuntimeException('Could not change working directory back to "'.$currentDir.'"', null, $ex); }

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
     */
    protected function setResourceBase(\SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if (!$xml['view-base']) {
            $this->resourceDirectories[] = APPLICATION_ROOT.'/app/view';
            return;
        }

        $directories = explode(',', (string) $xml['view-base']);

        foreach ($directories as $directory) {
            $dir = str_replace('\\', '/', trim($directory));

            if ($dir[0] == '/') $dir = realPath($dir);
            else                $dir = realPath(APPLICATION_ROOT.'/'.$dir);

            if (!is_dir($dir)) throw new FileNotFoundException('Directory not found: "'.$directory.'"');
            $this->resourceDirectories[] = $dir;
        }
    }


    /**
     * Verarbeitet die in der Konfiguration definierten globalen ActionForwards.
     *
     * @param  \SimpleXMLElement $xml - XML-Konfiguration
     */
    protected function processForwards(\SimpleXMLElement $xml) {
        // process global 'include' and 'redirect' forwards
        $elements = $xml->xPath('/struts-config/global-forwards/forward[@include] | /struts-config/global-forwards/forward[@redirect]');
        if ($elements === false)
            $elements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurueck

        foreach ($elements as $tag) {
            $name = (string) $tag['name'];
            if (sizeOf($tag->attributes()) > 2) throw new RuntimeException('Global forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

            if ($path = (string) $tag['include']) {
                if (!$this->isIncludable($path, $xml)) throw new RuntimeException('Global forward "'.$name.'", attribute "include": '.($path{0}=='.' ? 'Tiles definition':'File').' "'.$path.'" not found');

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
        $elements = $xml->xPath('/struts-config/global-forwards/forward[@forward]');
        if ($elements === false)
            $elements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurueck

        foreach ($elements as $tag) {
            $name = (string) $tag['name'];
            if (sizeOf($tag->attributes()) > 2) throw new RuntimeException('Global forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

            $alias = (string) $tag['forward'];
            $forward = $this->findForward($alias);
            if (!$forward) throw new RuntimeException('Global forward "'.$name.'", attribute "forward": Forward "'.$alias.'" not found');

            $this->addForward($name, $forward);
        }
    }


    /**
     * Verarbeitet die in der Konfiguration definierten ActionMappings.
     *
     * @param  \SimpleXMLElement $xml - XML-Konfiguration
     */
    protected function processMappings(\SimpleXMLElement $xml) {
        $elements = $xml->xPath('/struts-config/action-mappings/mapping');
        if ($elements === false)
            $elements = [];      // xPath() gibt entgegen der Dokumentation *NICHT* immer ein Array zurueck

        foreach ($elements as $tag) {
            $mapping = new $this->mappingClass($this);

            // attributes
            // ----------
            // process path attribute
            $path = (string) $tag['path'];
            $mapping->setPath($path);


            // process include attribute
            if ($tag['include']) {
                if ($mapping->getForward()) throw new RuntimeException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
                $path = (string) $tag['include'];
                if (!$this->isIncludable($path, $xml)) throw new RuntimeException('Mapping "'.$mapping->getPath().'", attribute "include": '.($path{0}=='.' ? 'Tiles definition':'File').' "'.$path.'" not found');

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
                if ($mapping->getForward()) throw new RuntimeException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
                $path = (string) $tag['redirect'];
                // TODO: URL validieren
                $forward = new $this->forwardClass('generic', $path, true);
                $mapping->setForward($forward);
            }


            // process forward attribute
            if ($tag['forward']) {
                if ($mapping->getForward()) throw new RuntimeException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
                $path = (string) $tag['forward'];
                $forward = $this->findForward($path);
                if (!$forward) throw new RuntimeException('Mapping "'.$mapping->getPath().'", attribute "forward": Forward "'.$path.'" not found');
                $mapping->setForward($forward);
            }
            if ($mapping->getForward() && sizeOf($tag->xPath('./forward'))) throw new RuntimeException('Mapping "'.$mapping->getPath().'": Only an "include", "redirect" or "forward" attribute *or* nested <forward> elements must be specified');


            // process action attribute
            if ($tag['action']) {
                if ($mapping->getForward()) throw new RuntimeException('Mapping "'.$mapping->getPath().'": Only one attribute of "action", "include", "redirect" or "forward" must be specified');
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
                    if ($tag['form-validate-first']=='false') throw new RuntimeException('Mapping "'.$mapping->getPath().'": An "action", "include", "redirect" or "forward" attribute is required when "form-validate-first" attribute is set to "false"');
                    $formValidateFirst = true;
                    // Pruefung auf 'success' und 'error' Forward erfolgt in ActionMapping:freeze()
                }
            }
            elseif ($formValidateFirst = $tag['form-validate-first']=='true') {
                throw new RuntimeException('Mapping "'.$mapping->getPath().'": A "form" attribute must be specified when the "form-validate-first" attribute is set to "true"');
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
                if (!$this->roleProcessorClass) throw new RuntimeException('Mapping "'.$mapping->getPath().'", attribute "roles": RoleProcessor configuration not found');
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
                $subElements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurueck

            foreach ($subElements as $forwardTag) {
                $name = (string) $forwardTag['name'];
                if (sizeOf($forwardTag->attributes()) > 2) throw new RuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

                if ($path = (string) $forwardTag['include']) {
                    if (!$this->isIncludable($path, $xml)) throw new RuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'", attribute "include": '.($path{0}=='.' ? 'Tiles definition':'File').' "'.$path.'" not found');

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
                $subElements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurueck

            foreach ($subElements as $forwardTag) {
                $name = (string) $forwardTag['name'];
                if (sizeOf($forwardTag->attributes()) > 2) throw new RuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'": Only one attribute of "include", "redirect" or "forward" must be specified');

                $alias = (string) $forwardTag['forward'];
                if ($alias == ActionForward ::__SELF) throw new RuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'", attribute "forward": Can not use magic keyword "'.$alias.'" as attribute value');

                $forward = $mapping->findForward($alias);
                if (!$forward) throw new RuntimeException('Mapping "'.$mapping->getPath().'", forward "'.$name.'", attribute "forward": Forward "'.$alias.'" not found');

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
     */
    protected function processTiles(\SimpleXMLElement $xml) {
        $elements = $xml->xPath('/struts-config/tiles/tile');
        if ($elements === false)
            $elements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurueck

        foreach ($elements as $tag) {
            $name = (string) $tag['name'];
            $tile = $this->getDefinedTile($name, $xml);
        }
        // TODO: rekursive Tiles-Definitionen abfangen
    }


    /**
     * Sucht die Tilesdefinition mit dem angegebenen Namen und gibt die entsprechende Instanz zurueck.
     *
     * @param  string            $name - Name der Tile
     * @param  \SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration der Tile
     *
     * @return Tile
     */
    private function getDefinedTile($name, \SimpleXMLElement $xml) {
        // if the tile already exists return it
        if (isSet($this->tiles[$name]))
            return $this->tiles[$name];

        // find it's definition ...
        $nodes = $xml->xPath("/struts-config/tiles/tile[@name='$name']");
        if (!$nodes)            throw new RuntimeException('Tiles definition "'.$name.'" not found'); // FALSE oder leeres Array
        if (sizeOf($nodes) > 1) throw new RuntimeException('Non-unique "name" attribute detected for tiles definition "'.$name.'"');


        $tag = $nodes[0];
        if (sizeOf($tag->attributes()) != 2) throw new RuntimeException('Tile "'.$name.'": Exactly one attribute of "file" or "extends-tile" must be specified');

        // create a new instance ...
        if ($tag['file']) {                 // 'file' given
            $fileAttr = (string) $tag['file'];
            $filePath = $this->findFile($fileAttr);
            if (!$filePath) throw new FileNotFoundException('Tile "'.$name.'", attribute "file": File "'.$fileAttr.'" not found');

            $tile = new $this->tilesClass($this);
            $tile->setFileName($filePath);
        }
        else {                           // 'file' not given, it's an extended tile (get and clone it's parent)
            $parent = $this->getDefinedTile((string) $tag['extends-tile'], $xml);
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
     * Verarbeitet die in einer Tiles-Definition angegebenen zusaetzlichen Properties.
     *
     * @param  Tile              $tile - Tile-Instanz
     * @param  \SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
     */
    private function processTileProperties(Tile $tile, \SimpleXMLElement $xml) {
        foreach ($xml->set as $tag) {
            $name  = (string) $tag['name'];
            // TODO: Name-Value von <set> wird nicht auf Eindeutigkeit ueberprueft

            if ($tag['value']) { // value ist im Attribut angegeben
                if (strLen($tag) > 0) throw new RuntimeException('Tile "'.$tile->getName().'", set "'.$name.'": Only a "value" attribute *or* a body value must be specified');
                $value = (string) $tag['value'];

                if ($tag['type']) {
                    $type = (string) $tag['type'];
                }
                elseif ($this->isIncludable($value, $xml)) {
                    $type = Tile::PROPERTY_TYPE_RESOURCE;
                }
                elseif (strEndsWithI($value, '.htm') || strEndsWithI($value, '.html')) {
                    throw new RuntimeException('Tile "'.$tile->getName().'", set "'.$name.'": specify a type="string|resource" for ambiguous attribute value="'.$value.'" (looks like a filename but file not found)');
                }
                else {
                    $type = Tile::PROPERTY_TYPE_STRING;
                }
            }
            else {               // value ist im Body angegeben
                $value = trim((string) $tag);
                $type = ($tag['type']) ? (string) $tag['type'] : Tile::PROPERTY_TYPE_STRING;
                if ($type == Tile::PROPERTY_TYPE_RESOURCE) throw new RuntimeException('Tile "'.$tile->getName().'", set "'.$name.'": A "value" attribute must be specified when attribute type is set to "resource"');
            }


            // Ist value eine Tile, diese initialisieren.
            if ($type == Tile::PROPERTY_TYPE_RESOURCE) {
                if ($this->isTile($value, $xml)) {
                    $nestedTile = $this->getDefinedTile($value, $xml);
                }
                elseif ($this->isFile($value)) {       // generische Tile erzeugen, damit render() existiert
                    $nestedTile = new $this->tilesClass($this, $tile);
                    $nestedTile->setName(Tile::GENERIC_NAME)
                          ->setFileName($this->findFile($value));
                }
                else {
                    throw new RuntimeException('Tile "'.$tile->getName().'", set "'.$name.'", attribute "value": '.($value{0}=='.' ? 'Tiles definition':'File').' "'.$value.'" not found');
                }
                $value = $nestedTile;
            }

            // TODO: bei extended Tiles Typuebereinstimmung ueberladener Properties pruefen
            $tile->setProperty($name, $value);
        }
    }


    /**
     * Verarbeitet die in der Konfiguration definierten Error-Einstellungen.
     *
     * @param  \SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
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
     * @param  Tile $tile
     */
    protected function addTile(Tile $tile) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $this->tiles[$tile->getName()] = $tile;
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
     */
    protected function processController(\SimpleXMLElement $xml) {
        if ($this->configured)
            throw new IllegalStateException('Configuration is frozen');

        $elements = $xml->xPath('/struts-config/controller');
        if ($elements === false)
            $elements = array(); // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurueck

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
     */
    protected function setRequestProcessorClass($className) {
        if ($this->configured)                                            throw new IllegalStateException('Configuration is frozen');
        if (!is_string($className))                                       throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
        if (!is_class($className))                                        throw new ClassNotFoundException('Undefined class "'.$className.'"');
        if (!is_subclass_of($className, DEFAULT_REQUEST_PROCESSOR_CLASS)) throw new InvalidArgumentException('Not a subclass of '.DEFAULT_REQUEST_PROCESSOR_CLASS.': '.$className);

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
     */
    protected function setRoleProcessorClass($className) {
        if ($this->configured)                                      throw new IllegalStateException('Configuration is frozen');
        if (!is_string($className))                                 throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
        if (!is_class($className))                                  throw new ClassNotFoundException('Undefined class "'.$className.'"');
        if (!is_subclass_of($className, ROLE_PROCESSOR_BASE_CLASS)) throw new InvalidArgumentException('Not a subclass of '.ROLE_PROCESSOR_BASE_CLASS.': '.$className);

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
     */
    protected function setTilesClass($className) {
        if ($this->configured)                                throw new IllegalStateException('Configuration is frozen');
        if (!is_string($className))                           throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
        if (!is_class($className))                            throw new ClassNotFoundException('Undefined class "'.$className.'"');
        if (!is_subclass_of($className, DEFAULT_TILES_CLASS)) throw new InvalidArgumentException('Not a subclass of '.DEFAULT_TILES_CLASS.': '.$className);

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
     */
    protected function setMappingClass($className) {
        if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
        if (!is_string($className))                                    throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
        if (!is_class($className))                                     throw new ClassNotFoundException("Undefined class '$className'");
        if (!is_subclass_of($className, DEFAULT_ACTION_MAPPING_CLASS)) throw new InvalidArgumentException('Not a subclass of '.DEFAULT_ACTION_MAPPING_CLASS.': '.$className);

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
     */
    protected function setForwardClass($className) {
        if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
        if (!is_string($className))                                    throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
        if (!is_class($className))                                     throw new ClassNotFoundException('Undefined class "'.$className.'"');
        if (!is_subclass_of($className, DEFAULT_ACTION_FORWARD_CLASS)) throw new InvalidArgumentException('Not a subclass of '.DEFAULT_ACTION_FORWARD_CLASS.': '.$className);

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
     */
    private function isTile($name, \SimpleXMLElement $xml) {
        $nodes = $xml->xPath("/struts-config/tiles/tile[@name='$name']");

        if ($nodes) {                 // xPath() gibt entgegen der Dokumentation NICHT immer ein Array zurueck
            if (sizeOf($nodes) > 1)
                throw new RuntimeException('Non-unique tiles definition name "'.$name.'"');
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
     * und gibt den vollstaendigen Dateinamen zurueck, oder NULL, wenn keine Datei mit diesem Namen
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
