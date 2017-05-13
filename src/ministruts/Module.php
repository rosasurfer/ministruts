<?php
namespace rosasurfer\ministruts;

use rosasurfer\config\Config;
use rosasurfer\core\Object;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;

use function rosasurfer\is_class;
use function rosasurfer\strContains;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\strLeftTo;
use function rosasurfer\strStartsWith;

use const rosasurfer\WINDOWS;


/**
 * Module
 */
class Module extends Object {


    /**
     * Der Prefix dieses Modules relative zur ROOT_URL der Anwendung.  Die Prefixe innerhalb einer Anwendung
     * sind eindeutig. Das Module mit einem Leerstring als Prefix ist das Default-Module der Anwendung.
     *
     * @var string
     */
    protected $prefix;

    /** @var string[] - imported fully qualified class names  */
    protected $uses;

    /** @var string[] - imported namespaces */
    protected $imports;

    /** @var string[] - Basisverzeichnisse fuer von diesem Modul einzubindende Resourcen */
    protected $resourceLocations = [];

    /** @var ActionForward[] - Die globalen Forwards dieses Moduls. */
    protected $globalForwards = [];

    /** @var ActionMapping[] - Die ActionMappings dieses Moduls. */
    protected $mappings = [];

    /** @var ActionMapping - Das Default-ActionMapping dieses Moduls, wenn eines definiert wurde. */
    protected $defaultMapping;

    /** @var Tile[] - Die Tiles dieses Moduls. */
    protected $tiles = [];

    /** @var string - view helper namespace used by templates and tiles */
    protected $viewNamespace = '';

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

    /** @var string[] - initialization context used to detect circular tiles definition references */
    protected $tilesContext = [];

    /** @var bool - Ob diese Komponente vollstaendig konfiguriert ist. */
    protected $configured = false;


    /**
     * Erzeugt ein neues Modul, liest und parst dessen Konfigurationsdatei.
     *
     * @param  string $fileName - Pfad zur Konfigurationsdatei des Modules
     * @param  string $prefix   - Prefix des Modules
     *
     * @throws StrutsConfigException in case of configuration errors
     *
     * @todo   Module-Encoding entsprechend dem Config-Datei-Encoding implementieren
     */
    public function __construct($fileName, $prefix) {
        if (!is_string($fileName)) throw new IllegalTypeException('Illegal type of parameter $fileName: '.getType($fileName));
        if (!is_string($prefix))   throw new IllegalTypeException('Illegal type of parameter $prefix: '.getType($prefix));

        $xml = $this->loadConfiguration($fileName);

        $this->setPrefix($prefix);
        $this->setNamespace($xml);
        $this->setResourceBase($xml);
        $this->processImports($xml);
        $this->processController($xml);
        $this->processForwards($xml);
        $this->processMappings($xml);
        $this->processTiles($xml);

        $this->tilesContext = [];
    }


    /**
     * Validiert die angegebene Konfigurationsdatei und wandelt sie in ein XML-Objekt um.
     *
     * @param  string $fileName - Pfad zur Konfigurationsdatei
     *
     * @return \SimpleXMLElement
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function loadConfiguration($fileName) {
        if (!is_file($fileName)) throw new StrutsConfigException('Configuration file not found: "'.$fileName.'"');

        $content = file_get_contents($fileName);
        $search  = '<!DOCTYPE struts-config SYSTEM "struts-config.dtd">';
        $offset  = strPos($content, $search);
        $dtd     = str_replace('\\', '/', __DIR__.'/xml/struts-config.dtd');
        $replace = '<!DOCTYPE struts-config SYSTEM "file:///'.$dtd.'">';
        $content = substr_replace($content, $replace, $offset, strLen($search));

        // Konfiguration parsen und validieren
        return new \SimpleXMLElement($content, LIBXML_DTDVALID);
    }


    /**
     * Gibt den Prefix dieses Modules zurueck. Anhand des Prefixes werden die verschiedenen Module der
     * Anwendung unterschieden.
     *
     * @return string
     *
     * @todo   If non-empty the prefix must never start and always end with a slash "/".
     */
    public function getPrefix() {
        return $this->prefix;
    }


    /**
     * Setzt den Prefix des Modules.
     *
     * @param  string prefix
     *
     * @throws StrutsConfigException in case of configuration errors
     *
     * @todo   If non-empty the prefix must never start and always end with a slash "/".
     */
    protected function setPrefix($prefix) {
        if ($this->configured)                  throw new IllegalStateException('Configuration is frozen');
        if (strLen($prefix) && $prefix[0]!='/') throw new StrutsConfigException('Module prefixes must start with a slash "/" character, found "'.$prefix.'"');
        $this->prefix = $prefix;
    }


    /**
     * Set the Module's default namespace.
     *
     * @param  \SimpleXMLElement $xml - configuration instance
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function setNamespace($xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $namespace = '';                    // default is the global namespace

        if ($xml['namespace']) {
            $namespace = trim((string) $xml['namespace']);
            $namespace = str_replace('/', '\\', $namespace);

            if ($namespace == '\\') {
                $namespace = '';
            }
            else if (strLen($namespace)) {
                if (!$this->isValidNamespace($namespace)) throw new StrutsConfigException('<struts-config namespace="'.$xml['namespace'].'": Invalid module namespace');
                if (strStartsWith($namespace, '\\')) $namespace  = subStr($namespace, 1);
                if (!strEndsWith($namespace, '\\'))  $namespace .= '\\';
            }
        }
        $this->imports[strToLower($namespace)] = $namespace;
    }


    /**
     * Setzt das Basisverzeichnis fuer lokale Resourcen.
     *
     * @param  \SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function setResourceBase(\SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $rootDirectory = Config::getDefault()->get('app.dir.root');

        if (!$xml['file-base']) {
            // not specified, apply global configuration
            $location = Config::getDefault()->get('app.dir.view', null);
            if (!$location) throw new StrutsConfigException('Missing view directory configuration: Neither $config[app.dir.view] nor <struts-config file-base="{base-directory}" are specified');

            $relativePath = WINDOWS ? !preg_match('/^[a-z]:/i', $location) : ($location[0]!='/');
            $relativePath && $location = $rootDirectory.DIRECTORY_SEPARATOR.$location;
            if (!is_dir($location)) throw new StrutsConfigException('Resource location $config[app.dir.view]="'.Config::getDefault()->get('app.dir.view').'" not found');

            $this->resourceLocations[] = realPath($location);
            return;
        }

        $locations = explode(',', (string) $xml['file-base']);

        foreach ($locations as $i => $location) {
            $location = trim($location);
            if (!strLen($location)) continue;

            $relativePath = WINDOWS ? !preg_match('/^[a-z]:/i', $location) : ($location[0]!='/');
            $relativePath && $location = $rootDirectory.DIRECTORY_SEPARATOR.$location;

            if (!is_dir($location)) throw new StrutsConfigException('<struts-config file-base="'.$locations[$i].'": Resource location not found');

            $this->resourceLocations[] = realPath($location);
        }
    }


    /**
     * Verarbeitet die in der Konfiguration definierten globalen ActionForwards.
     *
     * @param  \SimpleXMLElement $xml - XML-Konfiguration
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function processForwards(\SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        // process global 'include' and 'redirect' forwards
        $elements = $xml->xPath('/struts-config/global-forwards/forward[@include] | /struts-config/global-forwards/forward[@redirect]') ?: [];

        foreach ($elements as $tag) {
            $name = (string) $tag['name'];
            if (sizeOf($tag->attributes()) > 2) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": Only one of "include", "forward" or "redirect" can be specified.');
            /** @var ActionForward $forward */
            $forward = null;

            if ($include = (string) $tag['include']) {
                $this->tilesContext = [];
                if (!$this->isIncludable($include, $xml)) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'" include="'.$include.'": '.($include[0]=='.' ? 'Tile definition':'File').' not found.');

                if ($this->isTileDefinition($include, $xml)) {
                    $tile = $this->getTile($include, $xml);
                    if ($tile->isAbstract()) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'" include="'.$include.'": The included tile is a template and cannot be used in a "forward" definition.');
                    $forward = new $this->forwardClass($name, $include, false);
                }
                else {
                    $forward = new $this->forwardClass($name, $this->findFile($include), false);
                    $forward->setLabel(subStr($include, 0, strRPos($include, '.')));
                }
            }
            else {
                $redirect = (string) $tag['redirect'];                      // TODO: URL validieren
                $forward = new $this->forwardClass($name, $redirect, true);
            }
            $this->addGlobalForward($forward);
        }

        // process global 'alias-of' forwards
        $elements = $xml->xPath('/struts-config/global-forwards/forward[@alias-of]') ?: [];

        foreach ($elements as $tag) {
            $name = (string) $tag['name'];
            if (sizeOf($tag->attributes()) > 2) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": Only one of "include", "redirect" or "alias-of" can be specified.');

            $alias = (string) $tag['alias-of'];
            $forward = $this->findForward($alias);
            if (!$forward) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": Forward alias "'.$alias.'" not found.');

            $this->addGlobalForward($forward, $name);
        }
    }


    /**
     * Verarbeitet die in der Konfiguration definierten ActionMappings.
     *
     * @param  \SimpleXMLElement $xml - XML-Konfiguration
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function processMappings(\SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $elements = $xml->xPath('/struts-config/action-mappings/mapping') ?: [];

        foreach ($elements as $tag) {
            /** @var ActionMapping $mapping */
            $mapping = new $this->mappingClass($this);

            // attributes
            // ----------
            // process path attribute
            $path = (string) $tag['path'];                      // TODO: URL validieren
            $mapping->setPath($path);

            // process include attribute
            if ($tag['include']) {
                if ($mapping->getForward()) throw new StrutsConfigException('<mapping path="'.$path.'": Only one of "action", "include", "forward" or "redirect" can be specified.');

                $this->tilesContext = [];
                $include = (string) $tag['include'];
                if (!$this->isIncludable($include, $xml)) throw new StrutsConfigException('<mapping path="'.$path.'" include="'.$include.'": '.($include[0]=='.' ? 'Tile definition':'File').' not found.');

                /** @var ActionForward $forward */
                $forward = null;

                if ($this->isTileDefinition($include, $xml)) {
                    $tile = $this->getTile($include, $xml);
                    if ($tile->isAbstract()) throw new StrutsConfigException('<mapping path="'.$path.'" include="'.$include.'": The included tile is a template and cannot be used in a "mapping" definition.');
                    $forward = new $this->forwardClass('generic', $include, false);
                }
                else {
                    $forward = new $this->forwardClass('generic', $this->findFile($include), false);
                    $forward->setLabel(subStr($include, 0, strRPos($include, '.')));
                }
                $mapping->setForward($forward);
            }


            // process redirect attribute
            if ($tag['redirect']) {
                if ($mapping->getForward()) throw new StrutsConfigException('<mapping path="'.$path.'": Only one of "action", "include", "forward" or "redirect" can be specified');
                $redirect = (string) $tag['redirect'];          // TODO: URL validieren
                /** @var ActionForward $forward */
                $forward = new $this->forwardClass('generic', $redirect, true);
                $mapping->setForward($forward);
            }


            // process forward attribute
            if ($tag['forward']) {
                if ($mapping->getForward()) throw new StrutsConfigException('<mapping path="'.$path.'": Only one of "action", "include", "forward" or "redirect" can be specified.');
                $forwardAttr = (string) $tag['forward'];
                /** @var ActionForward $forward */
                $forward = $this->findForward($forwardAttr);
                if (!$forward)              throw new StrutsConfigException('<mapping path="'.$path.'": Forward "'.$forwardAttr.'" not found.');
                $mapping->setForward($forward);
            }
            if ($mapping->getForward() && sizeOf($tag->xPath('./forward'))) throw new StrutsConfigException('<mapping path="'.$path.'": Only an "include", "forward" or "redirect" attribute *or* nested <forward> elements must be specified.');


            // process action attribute
            if ($tag['action']) {
                if ($mapping->getForward())  throw new StrutsConfigException('<mapping path="'.$path.'": Only one of "action", "include", "forward" or "redirect" can be specified.');
                $name       = trim((string) $tag['action']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            throw new StrutsConfigException('<mapping path="'.$path.'" action="'.$tag['action'].'": Class not found.');
                if (sizeOf($classNames) > 1) throw new StrutsConfigException('<mapping path="'.$path.'" action="'.$tag['action'].'": Ambiguous class name, found "'.join('", "', $classNames).'".');
                $mapping->setActionClassName($classNames[0]);
            }


            // process form attribute
            if ($tag['form']) {
                $name       = trim((string) $tag['form']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            throw new StrutsConfigException('<mapping path="'.$path.'" form="'.$tag['form'].'": Class not found.');
                if (sizeOf($classNames) > 1) throw new StrutsConfigException('<mapping path="'.$path.'" form="'.$tag['form'].'": Ambiguous class name, found "'.join('", "', $classNames).'".');
                $mapping->setFormClassName($classNames[0]);
            }


            // process form-scope attribute
            if ($tag['form-scope']) {
                $mapping->setFormScope((string) $tag['form-scope']);
            }


            // process form-validate-first attribute
            $formValidateFirst = false;
            if ($mapping->getFormClassName()) {
                $action = $mapping->getActionClassName();
                if ($action || $mapping->getForward()) {
                    $formValidateFirst = $tag['form-validate-first'] ? ($tag['form-validate-first']=='true') : !$action;
                }
                else {
                    if ($tag['form-validate-first']=='false') throw new StrutsConfigException('<mapping path="'.$path.'": An "action", "include", "redirect" or "forward" attribute is required if "form-validate-first" is set to "false"');
                    $formValidateFirst = true;
                    // Pruefung auf 'success' und 'error' Forward erfolgt in ActionMapping:freeze()
                }
            }
            elseif ($tag['form-validate-first'] == 'true') {
                throw new StrutsConfigException('<mapping path="'.$path.'": A "form" attribute must be specified if "form-validate-first" is set to "true"');
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
                if (!$this->roleProcessorClass) throw new StrutsConfigException('<mapping path="'.$path.'" roles="'.$tag['roles'].'": RoleProcessor configuration not found');
                $mapping->setRoles((string) $tag['roles']);
            }


            // process default attribute
            if ($tag['default']) {
                $mapping->setDefault((string) $tag['default'] == 'true');
            }


            // child nodes
            // -----------
            // process local 'include' and 'redirect' forwards
            $subElements = $tag->xPath('./forward[@include] | ./forward[@redirect]') ?: [];

            foreach ($subElements as $forwardTag) {
                $name = (string) $forwardTag['name'];
                if (sizeOf($forwardTag->attributes()) > 2) throw new StrutsConfigException('<mapping path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect" or "alias-of" can be specified.');
                /** @var ActionForward $forward */
                $forward = null;

                if ($include = (string) $forwardTag['include']) {
                    $this->tilesContext = [];
                    if (!$this->isIncludable($include, $xml)) throw new StrutsConfigException('<mapping path="'.$path.'"> <forward name="'.$name.'" include="'.$include.'": '.($include[0]=='.' ? 'Tiles definition':'File').' not found.');

                    if ($this->isTileDefinition($include, $xml)) {
                        $tile = $this->getTile($include, $xml);
                        if ($tile->isAbstract()) throw new StrutsConfigException('<mapping path="'.$path.'"> <forward name="'.$name.'" include="'.$include.'": The included tile is a template and cannot be used in a "forward" definition.');
                        $forward = new $this->forwardClass($name, $include, false);
                    }
                    else {
                        $forward = new $this->forwardClass($name, $this->findFile($include), false);
                        $forward->setLabely(subStr($include, 0, strRPos($include, '.')));
                    }
                }
                else {
                    $redirect = (string) $forwardTag['redirect'];
                    // TODO: URL validieren
                    $forward = new $this->forwardClass($name, $redirect, true);
                }
                $mapping->addForward($forward, $name);
            }

            // process local 'alias-of' forwards
            $subElements = $tag->xPath('./forward[@alias-of]') ?: [];

            foreach ($subElements as $forwardTag) {
                $name = (string) $forwardTag['name'];
                if (sizeOf($forwardTag->attributes()) > 2) throw new StrutsConfigException('<mapping path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect" or "alias-of" can be specified.');

                $alias = (string) $forwardTag['alias-of'];
                if ($alias == ActionForward::__SELF) throw new StrutsConfigException('<mapping path="'.$path.'"> <forward name="'.$name.'" alias-of="'.$alias.'": Can not use reserved word "'.$alias.'" as attribute value.');

                $forward = $mapping->findForward($alias);
                if (!$forward) throw new StrutsConfigException('<mapping path="'.$path.'"> <forward name="'.$name.'" alias-of="'.$alias.'": Alias forward not found');

                $mapping->addForward($forward, $name);
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
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function processTiles(\SimpleXMLElement $xml) {
        $namespace = '';                                            // default is the global namespace

        if ($tiles = $xml->xPath('/struts-config/tiles') ?: []) {
            $tiles = $tiles[0];
            if ($tiles['namespace']) {
                $namespace = trim((string) $tiles['namespace']);
                $namespace = str_replace('/', '\\', $namespace);

                if ($namespace == '\\') {
                    $namespace = '';
                }
                else if (strLen($namespace)) {
                    if (!$this->isValidNamespace($namespace)) throw new StrutsConfigException('<tiles namespace="'.$tiles['namespace'].'": Invalid namespace');
                    if (strStartsWith($namespace, '\\')) $namespace  = subStr($namespace, 1);
                    if (!strEndsWith($namespace, '\\'))  $namespace .= '\\';
                }
            }
        }
        $this->viewNamespace = $namespace;

        $elements = $xml->xPath('/struts-config/tiles/tile') ?: [];

        foreach ($elements as $tag) {
            $this->tilesContext = [];
            $name = (string) $tag['name'];
            $this->getTile($name, $xml);
        }
    }


    /**
     * Return the initialized tile with the specified name.
     *
     * @param  string            $name - tile name
     * @param  \SimpleXMLElement $xml  - XML object of the Struts configuration
     *
     * @return Tile
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    private function getTile($name, \SimpleXMLElement $xml) {
        // if the tile is already registered return it
        if (isSet($this->tiles[$name]))
            return $this->tiles[$name];

        // detect and block circular tile references
        if (in_array($name, $this->tilesContext)) {
            $this->tilesContext[] = $name;
            throw new StrutsConfigException('Circular tile reference detected: "'.join('" -> "', $this->tilesContext).'"');
        }
        $this->tilesContext[] = $name;

        // find it's definition ...
        $nodes = $xml->xPath("/struts-config/tiles/tile[@name='".$name."']");
        if (!$nodes)            throw new StrutsConfigException('Tile named "'.$name.'" not found');
        if (sizeOf($nodes) > 1) throw new StrutsConfigException('Multiple tiles named "'.$name.'" found');

        $tag = $nodes[0];
        if (sizeOf($tag->attributes()) != 2) throw new StrutsConfigException('<tile name="'.$name.'": Only one of "file", "extends-tile" or "alias-of" can be specified.');

        // check for an alias
        if ($tag['alias-of']) {                                 // 'alias-of' given
            $alias = (string) $tag['alias-of'];
            $tile  = $this->getTile($alias, $xml);
            $this->addTile($tile, $name);
            return $tile;
        }

        // create a new instance ...
        if ($tag['file']) {                                     // 'file' given
            $fileAttr = (string) $tag['file'];
            /** @var string $filePath */
            $filePath = $this->findFile($fileAttr);
            if (!$filePath) throw new StrutsConfigException('<tile name="'.$name.'" file="'.$fileAttr.'": File not found.');

            /** @var Tile $tile */
            $tile = new $this->tilesClass($this);
            $tile->setFileName($filePath);
        }
        else {                                                  // 'extends-tile' given
            $other    = (string) $tag['extends-tile'];
            $extended = $this->getTile($other, $xml);           // clone the one to extend
            $tile     = clone $extended;
        }
        $tile->setName($name);

        // process it's child nodes ...
        $this->processTileProperties($tile, $tag);

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
     * @throws StrutsConfigException in case of configuration errors
     */
    private function processTileProperties(Tile $tile, \SimpleXMLElement $xml) {
        // process <include> elements
        foreach ($xml->{'include'} as $tag) {
            $name  = (string) $tag['name'];
            $nodes = $xml->xPath("/struts-config/tiles/tile[@name='".$tile->getName()."']/include[@name='".$name."']");
            if (sizeOf($nodes) > 1) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <include name="'.$name.'">: Multiple elements with the same name found.');

            if ($value=(string) $tag['value']) {                        // 'value' specified
                if (!$this->isIncludable($value, $xml)) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <include name="'.$name.'" value="'.$value.'": '.($value[0]=='.' ? 'Tile definition':'File').' not found.');

                if ($this->isTileDefinition($value, $xml)) {
                    $nestedTile = $this->getTile($value, $xml);
                    if ($nestedTile->isAbstract()) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <include name="'.$name.'" value="'.$value.'": A tiles template or layout cannot be used in an "include" definition.');
                }
                else {
                    /** @var string $file */
                    $file = $this->findFile($value);
                    /** @var Tile $nestedTile */
                    $nestedTile = new $this->tilesClass($this, $tile);  // generische Tile erzeugen, damit render() existiert
                    $nestedTile->setName(Tile::GENERIC_NAME)
                               ->setFileName($file);
                }
            }
            else {
                $nestedTile = null;                                     // resource not yet defined, it's an abstract template
            }
            $tile->setNestedTile($name, $nestedTile);
        }

        // process <set> elements
        foreach ($xml->{'set'} as $tag) {
            $name  = (string) $tag['name'];
            $nodes = $xml->xPath("/struts-config/tiles/tile[@name='".$tile->getName()."']/set[@name='".$name."']");
            if (sizeOf($nodes) > 1) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <set name="'.$name.'": Multiple elements with the same name found.');

            if ($tag['value']) {                            // value ist im Attribut angegeben
                if (strLen($tag) > 0) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <set name="'.$name.'": Only one of attribute value or tag body value can be specified.');
                $value = (string) $tag['value'];
            }
            else {                                          // value ist im Body angegeben
                $value = trim((string) $tag);
            }
            // TODO: Var-Type nicht nur casten, sondern validieren
            switch (((string)$tag['type']) ?: 'string') {
                case 'bool' : $value =  (bool) $value; break;
                case 'int'  : $value =   (int) $value; break;
                case 'float': $value = (float) $value; break;
            }
            $tile->setProperty($name, $value);
        }
    }


    /**
     * Fuegt diesem Module einen globalen ActionForward. Ist ein Alias angegeben, wird er unter dem Alias-Namen registriert.
     *
     * @param  ActionForward $forward
     * @param  string|null   $alias   - alias name of the forward (default: none)
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function addGlobalForward(ActionForward $forward, $alias=null) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $name = is_null($alias) ? $forward->getName() : $alias;

        if (isSet($this->globalForwards[$name])) throw new StrutsConfigException('Non-unique name detected for global ActionForward "'.$name.'"');
        $this->globalForwards[$name] = $forward;
    }


    /**
     * Fuegt diesem Module ein ActionMapping hinzu.
     *
     * @param  ActionMapping $mapping
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function addMapping(ActionMapping $mapping) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if ($mapping->isDefault()) {
            if ($this->defaultMapping) throw new StrutsConfigException('Only one ActionMapping can be marked as "default" within a module.');
            $this->defaultMapping = $mapping;
        }

        $path = $mapping->getPath();
        if (!strEndsWith($path, '/'))
            $path .= '/';

        if (isSet($this->mappings[$path])) throw new StrutsConfigException('All action mappings must have unique path attributes, non-unique path: "'.$mapping->getPath().'"');

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
        if (!strEndsWith($path, '/'))
            $path .= '/';
        // $path: /
        // $path: /action/
        // $path: /controller/action/
        // $path: /controller/action/parameter/

        $pattern = $path;
        while (strLen($pattern)) {
            if (isSet($this->mappings[$pattern]))       // mapping keys start and end with a slash "/"
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
     * Process the configured import settings.
     *
     * @param  \SimpleXMLElement $xml - configuration instance
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function processImports(\SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $imports = $xml->xPath('/struts-config/imports/import') ?: [];

        foreach ($imports as $import) {
            $value = trim((string)$import['value']);
            $value = str_replace('/', '\\', $value);

            if (strEndsWith($value, '\\*')) {           // imported namespace
                $value = strLeft($value, -1);
                if (!$this->isValidNamespace($value)) throw new StrutsConfigException('<imports> <import value="'.$import['value'].'": Invalid value (neither a class nor a namespace).');
                if (strStartsWith($value, '\\')) $value  = subStr($value, 1);
                if (!strEndsWith($value, '\\'))  $value .= '\\';
                $this->imports[strToLower($value)] = $value;
                continue;
            }

            if (is_class($value)) {                     // imported class
                if (strStartsWith($value, '\\')) $value = subStr($value, 1);
                $simpleName = strToLower(baseName($value));
                if (isSet($this->uses[$simpleName])) throw new StrutsConfigException('<imports> <import value="'.$import['value'].'": Duplicate value.');
                $this->uses[$simpleName] = $value;
                continue;
            }
            throw new StrutsConfigException('<imports> <import value="'.$import['value'].'": Invalid value (neither a class nor a namespace).');
        }
    }


    /**
     * Verarbeitet Controller-Einstellungen.
     *
     * @param  \SimpleXMLElement $xml - XML-Objekt mit der Konfiguration
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function processController(\SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $elements = $xml->xPath('/struts-config/controller') ?: [];

        foreach ($elements as $controller) {
            $attr = 'request-processor';
            if ($controller[$attr]) {
                $name       = trim((string) $controller[$attr]);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            throw new StrutsConfigException('<controller '.$attr.'="'.$controller[$attr].'": Class not found.');
                if (sizeOf($classNames) > 1) throw new StrutsConfigException('<controller '.$attr.'="'.$controller[$attr].'": Ambiguous class name, found "'.join('", "', $classNames).'"');
                $this->setRequestProcessorClass($classNames[0]);
            }
            $attr = 'role-processor';
            if ($controller[$attr]) {
                $name       = trim((string) $controller[$attr]);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            throw new StrutsConfigException('<controller '.$attr.'="'.$controller[$attr].'": Class not found.');
                if (sizeOf($classNames) > 1) throw new StrutsConfigException('<controller '.$attr.'="'.$controller[$attr].'": Ambiguous class name, found "'.join('", "', $classNames).'"');
                $this->setRoleProcessorClass($classNames[0]);
            }
        }
    }


    /**
     * Setzt den Klassennamen der RequestProcessor-Implementierung, die fuer dieses Module benutzt wird.
     * Diese Klasse muss eine Subklasse von RequestProcessor sein.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function setRequestProcessorClass($className) {
        if ($this->configured)                                            throw new IllegalStateException('Configuration is frozen');
        if (!is_subclass_of($className, DEFAULT_REQUEST_PROCESSOR_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_REQUEST_PROCESSOR_CLASS.': '.$className);
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
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function setRoleProcessorClass($className) {
        if ($this->configured)                                      throw new IllegalStateException('Configuration is frozen');
        if (!is_subclass_of($className, ROLE_PROCESSOR_BASE_CLASS)) throw new StrutsConfigException('Not a subclass of '.ROLE_PROCESSOR_BASE_CLASS.': '.$className);
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
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function setTilesClass($className) {
        if ($this->configured)                                throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                            throw new StrutsConfigException('Class '.$className.' not found');
        if (!is_subclass_of($className, DEFAULT_TILES_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_TILES_CLASS.': '.$className);

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
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function setMappingClass($className) {
        if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                                     throw new StrutsConfigException('Class '.$className.' not found');
        if (!is_subclass_of($className, DEFAULT_ACTION_MAPPING_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_ACTION_MAPPING_CLASS.': '.$className);

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
     * @throws StrutsConfigException in case of configuration errors
     */
    protected function setForwardClass($className) {
        if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                                     throw new StrutsConfigException('Class '.$className.' not found');
        if (!is_subclass_of($className, DEFAULT_ACTION_FORWARD_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_ACTION_FORWARD_CLASS.': '.$className);

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
     * Return the view helper namespace used by templates and tiles.
     *
     * @return string
     */
    public function getViewNamespace() {
        return $this->viewNamespace;
    }


    /**
     * Friert die Konfiguration ein, sodass sie nicht mehr geaendert werden kann.
     *
     * @return $this
     */
    public function freeze() {
        if (!$this->configured) {
            foreach ($this->mappings as $mapping) $mapping->freeze();

            foreach ($this->tiles as $i => $tile) {
                if ($tile->isAbstract()) unset($this->tiles[$i]);
                else                     $tile->freeze();
            }

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
        if (isSet($this->globalForwards[$name]))
            return $this->globalForwards[$name];
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
        return $this->isTileDefinition($name, $xml) || $this->isFile($name);
    }


    /**
     * Ob unter dem angegebenen Namen eine Tile definiert ist.
     *
     * @param  string            $name - Name der Tile
     * @param  \SimpleXMLElement $xml  - XML-Objekt mit der Konfiguration
     *
     * @return bool
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    private function isTileDefinition($name, \SimpleXMLElement $xml) {
        $nodes = $xml->xPath("/struts-config/tiles/tile[@name='".$name."']") ?: [];
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


    /**
     * Whether or not a string represents a valid namespace.
     *
     * @param  string $value
     *
     * @return bool
     */
    private function isValidNamespace($value) {
        $pattern = '/^\\\\?[a-z_][a-z0-9_]*(\\\\[a-z_][a-z0-9_]*)*\\\\?$/i';
        return (bool) preg_match($pattern, $value);
    }


    /**
     * Resolves a simple class name and returns all found fully qualified class names.
     *
     * @param  string $name
     *
     * @return string[] - found class names or an empty array if the class name cannot be resolved
     */
    private function resolveClassName($name) {
        $name = str_replace('/', '\\', trim($name));

        // no need to resolve a qualified name
        if (strContains($name, '\\'))
            return is_class($name) ? [$name] : [];

        // unqualified name, check "use" declarations
        $lowerName = strToLower($name);
        if (isSet($this->uses[$lowerName]))
            return [$this->uses[$lowerName]];

        // unqualified name, check imported namespaces
        $results = [];
        foreach ($this->imports as $namespace) {
            $class = $namespace.$name;
            if (is_class($class))
                $results[] = $class;
        }
        return $results;
    }
}
