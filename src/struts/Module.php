<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\config\ConfigInterface;
use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\file\xml\SimpleXMLElement;
use rosasurfer\ministruts\net\http\HttpResponse;

use function rosasurfer\ministruts\first;
use function rosasurfer\ministruts\isRelativePath;
use function rosasurfer\ministruts\realpath;
use function rosasurfer\ministruts\simpleClassName;
use function rosasurfer\ministruts\strCompareI;
use function rosasurfer\ministruts\strContains;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strLeft;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\NL;

/**
 * Module
 *
 * Struts modules allow separation (splitting) of web applications into multiple logical sections. Each Module (or section) is defined
 * by a unique base path (the module prefix), e.g. "/admin/", "/backend/" or "/shop/". Each Module has its own Struts configuration and
 * can be configured and managed separately from other modules.
 *
 * The main Module of an application has module prefix "" (an empty string), its base path is "/" and its Struts configuration is stored
 * in "struts-config.xml". Additional modules of the application have a non-empty prefix, their base path is "/{prefix}/" and their Struts
 * configurations are stored in separate files named "struts-config-{prefix}.xml". Modules can be nested, and a full Module can be moved
 * in the application by only changing its module prefix.
 *
 * The full URI of a route to a specific module's {@link ActionMapping} is "/{app-base-path}/{module-prefix}/{mapping-path}".
 */
class Module extends CObject {

    /**
     * Module prefix relative to the base URI of the web application. All module prefixes in an application are unique.
     * The module with the prefix "" (empty string) is the main module of an application.
     *
     * @var string
     */
    protected string $prefix;

    /** @var string[] - imported class names (configurable) */
    protected array $uses = [];

    /** @var string[] - imported namespaces (configurable) */
    protected array $imports;

    /** @var string[] - base directory for file resources used by the module (configurable) */
    protected array $resourceLocations = [];

    /** @var ActionForward[] - global forwards of the module (configurable) */
    protected array $globalForwards = [];

    /** @var ActionMapping[][] - action mappings of the module (configurable) */
    protected array $mappings = [
        'names' => [],
        'paths' => [],
    ];

    /**
     * Default action mapping of the module or NULL if undefined. Used when a request does not match any other action mapping (configurable).
     *
     * @var ?ActionMapping
     */
    protected ?ActionMapping $defaultMapping = null;

    /** @var Tile[] - all tiles of the module (configurable) */
    protected array $tiles = [];

    /** @var string - default view namespace for file resources and tiles (configurable) */
    protected string $viewNamespace = '';

    /**
     * @var         string - classname of the {@link RequestProcessor} implementation used by the module (configurable)
     * @phpstan-var class-string<RequestProcessor>
     */
    protected string $requestProcessorClass = RequestProcessor::class;

    /**
     * @var         string - classname of the {@link ActionForward} implementation used by the module (configurable)
     * @phpstan-var class-string<ActionForward>
     */
    protected string $forwardClass = ActionForward::class;

    /**
     * @var         string - classname of the {@link ActionMapping} implementation used by the module (configurable)
     * @phpstan-var class-string<ActionMapping>
     */
    protected string $mappingClass = ActionMapping::class;

    /**
     * @var         string - classname of the {@link Tile} implementation used by the module (configurable)
     * @phpstan-var class-string<Tile>
     */
    protected string $tilesClass = Tile::class;

    /**
     * @var         ?string - classname of the {@link RoleProcessor} implementation used by the module (configurable)
     * @phpstan-var ?class-string<RoleProcessor>
     */
    protected ?string $roleProcessorClass = null;

    /** @var ?RoleProcessor - the RoleProcessor instance used by the module */
    protected ?RoleProcessor $roleProcessor = null;

    /** @var string[] - initialization context for detecting circular tile references */
    protected array $tilesContext = [];

    /** @var bool - whether this component is fully configured */
    protected bool $configured = false;


    /**
     * Constructor
     *
     * Creates a new instance from the specified configuration file ("struts-config.xml").
     *
     * @param  string $fileName - full name of the module's configuration file
     * @param  string $prefix   - module prefix
     *
     * TODO: check/handle different config file encodings
     */
    public function __construct(string $fileName, string $prefix) {
        $xml = $this->loadConfiguration($fileName);

        $this->setPrefix($prefix);
        $this->setNamespace($xml);
        $this->setResourceBase($xml);
        $this->processImports($xml);
        $this->processController($xml);
        $this->processGlobalForwards($xml);
        $this->processMappings($xml);
        $this->processTiles($xml);

        $this->tilesContext = [];
    }


    /**
     * Load the module configuration ("struts-config.xml") and convert it to an {@link SimpleXMLElement} instance.
     *
     * @param  string $fileName - full filename
     *
     * @return SimpleXMLElement
     */
    protected function loadConfiguration(string $fileName): SimpleXMLElement {
        if (!is_file($fileName)) Struts::configError('configuration file not found: "'.$fileName.'"');

        $content = file_get_contents($fileName);
        $search = '<!DOCTYPE struts-config SYSTEM "struts-config.dtd">';
        $offset = strpos($content, $search);

        if ($offset !== false) {
            $dtd = str_replace('\\', '/', __DIR__.'/dtd/struts-config.dtd');
            $replace = "<!DOCTYPE struts-config SYSTEM \"file:///$dtd\">";
            $content = substr_replace($content, $replace, $offset, strlen($search));
        }

        return SimpleXMLElement::from($content, LIBXML_DTDVALID|LIBXML_NONET);
    }


    /**
     * Return the prefix of this module. All module prefixes of an application are unique.
     *
     * @return string - an empty string for the main module;
     *                  a path fragment not starting but ending with a slash "/" for a non-main module
     */
    public function getPrefix(): string {
        return $this->prefix;
    }


    /**
     * Set the module prefix.
     *
     * @param  string $prefix - the prefix of the main module is an empty string;
     *                          prefixes of non-main modules must not start but must end with a slash "/"
     * @return $this
     */
    protected function setPrefix(string $prefix): self {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        if ($len=strlen($prefix)) {
            if ($prefix[     0] == '/') Struts::configError('non-main module prefixes must not start with a slash "/" character, found: "'.$prefix.'"');
            if ($prefix[$len-1] != '/') Struts::configError('non-main module prefixes must end with a slash "/" character, found: "'.$prefix.'"');
        }
        $this->prefix = $prefix;
        return $this;
    }


    /**
     * Set the default namespace used by the Module when looking up relative classnames.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @return $this
     */
    protected function setNamespace(SimpleXMLElement $xml): self {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        // default is the global namespace
        $namespace = '';

        if (isset($xml['namespace'])) {
            $namespace = trim((string) $xml['namespace']);
            $namespace = str_replace('/', '\\', $namespace);

            if ($namespace == '\\') {           // that's again the global namespace
                $namespace = '';
            }
            elseif (strlen($namespace)) {
                if (!$this->isValidNamespace($namespace)) Struts::configError('<struts-config namespace="'.$xml['namespace'].'": invalid module namespace');
                if (strStartsWith($namespace, '\\')) $namespace  = substr($namespace, 1);
                if (!strEndsWith($namespace, '\\'))  $namespace .= '\\';
            }
        }
        $this->imports[strtolower($namespace)] = $namespace;
        return $this;
    }


    /**
     * Set the base directory used by the module when looking up file resources.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @return $this
     */
    protected function setResourceBase(SimpleXMLElement $xml): self {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        /** @var ConfigInterface $config */
        $config = $this->di('config');

        if (!isset($xml['file-base'])) {
            // not specified, apply global configuration
            /** @var string $viewDir */
            $viewDir = $config->get('app.dir.view', null) ?? Struts::configError(
                'Missing view directory configuration: Neither $config[app.dir.view] nor <struts-config file-base="{base-directory}" are specified'.NL
                .'config: '.NL
                .$config->dump(),
            );
            if (!is_dir($viewDir)) Struts::configError("View directory \$config[app.dir.view]=\"$viewDir\" not found");

            $this->resourceLocations[] = $viewDir;
            return $this;
        }

        $locations = explode(',', (string) $xml['file-base']);
        $appRoot = null;

        foreach ($locations as $i => $location) {
            $location = trim($location);
            if (!strlen($location)) continue;
            if (isRelativePath($location)) {
                $appRoot ??= $config->getString('app.dir.root');
                $location = $appRoot.DIRECTORY_SEPARATOR.$location;
            }
            if (!is_dir($location)) Struts::configError("<struts-config file-base=\"$locations[$i]\": Resource location not found");

            $this->resourceLocations[] = realpath($location);
        }
        return $this;
    }


    /**
     * Process all configured global {@link ActionForward}s.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @return void
     */
    protected function processGlobalForwards(SimpleXMLElement $xml): void {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        // process all global forwards having no "forward" attribute
        $elements = $xml->xpath('/struts-config/global-forwards/forward[not(@forward)]') ?: [];
        foreach ($elements as $tag) {
            $name         = (string) $tag['name'];
            $include      = isset($tag['include'      ]) ? (string)$tag['include'      ] : null;
            $redirect     = isset($tag['redirect'     ]) ? (string)$tag['redirect'     ] : null;
            $redirectType = isset($tag['redirect-type']) ? (string)$tag['redirect-type'] : 'temporary';
            $mapping      = isset($tag['mapping'      ]) ? (string)$tag['mapping'      ] : null;
            $alias        = isset($tag['forward'      ]) ? (string)$tag['forward'      ] : null;

            /** @var ActionForward|null $forward */
            $forward = null;

            if (isset($include)) {
                if (isset($redirect) || isset($mapping) || isset($alias)) Struts::configError('<global-forwards> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');

                $this->tilesContext = [];
                if (!$this->isIncludable($include, $xml))                 Struts::configError('<global-forwards> <forward name="'.$name.'" include="'.$include.'": '.(strStartsWith($include, '.') ? 'Tile definition':'File').' not found.');

                if ($this->isTileDefinition($include, $xml)) {
                    $tile = $this->getTile($include, $xml);
                    if ($tile->isAbstract())                              Struts::configError('<global-forwards> <forward name="'.$name.'" include="'.$include.'": The included tile is a template and cannot be used as a forward resource.');
                    $forward = new $this->forwardClass($name, $include, false);
                }
                else {
                    $forward = new $this->forwardClass($name, (string) $this->findFile($include), false);
                }
            }

            if (isset($redirect)) {
                if (isset($include) || isset($mapping) || isset($alias))  Struts::configError('<global-forwards> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');
                $redirectType = $redirectType=='temporary' ? HttpResponse::SC_MOVED_TEMPORARILY : HttpResponse::SC_MOVED_PERMANENTLY;
                $forward = new $this->forwardClass($name, $redirect, true, $redirectType);  // TODO: validate URL
            }
            elseif (isset($tag['redirect-type']))                         Struts::configError('<global-forwards> <forward name="'.$name.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

            if (isset($mapping)) {
                if (isset($include) || isset($redirect) || isset($alias)) Struts::configError('<global-forwards> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');
                $tag = first($xml->xpath('/struts-config/action-mappings/mapping[@name="'.$mapping.'"]') ?: []);
                if (!$tag)                                                Struts::configError('<global-forwards> <forward name="'.$name.'": Referenced action mapping "'.$mapping.'" not found.');
                $path = (string)$tag['path'];
                $forward = new $this->forwardClass($name, $path, true);
            }

            if (!$forward) Struts::configError('<global-forwards> <forward name="'.$name.'": One of "include", "redirect", "mapping" or "forward" must be specified.');
            $this->addGlobalForward($forward);
        }

        // process all global forwards having a "forward" attribute (parsed after the others to be able to find them)
        $elements = $xml->xpath('/struts-config/global-forwards/forward[@forward]') ?: [];

        foreach ($elements as $tag) {
            $name  = (string)$tag['name'   ];
            $alias = (string)$tag['forward'];
            if ($name == $alias)                                   Struts::configError('<global-forwards> <forward name="'.$name.'": The attribute forward="'.$alias.'" must not be self-referencing.');

            if (isset($tag['include']) || isset($tag['redirect'])) Struts::configError('<global-forwards> <forward name="'.$name.'": Only one of "include", "redirect" or "forward" can be specified.');
            if (isset($tag['redirect-type']))                      Struts::configError('<global-forwards> <forward name="'.$name.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

            $forward = $this->findForward($alias);
            if (!$forward) Struts::configError('<global-forwards> <forward name="'.$name.'": Referenced forward "'.$alias.'" not found.');

            $this->addGlobalForward($forward, $name);
        }
    }


    /**
     * Process all configured {@link ActionMapping}s.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @return void
     */
    protected function processMappings(SimpleXMLElement $xml): void {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $elements = $xml->xpath('/struts-config/action-mappings/mapping') ?: [];

        foreach ($elements as $tag) {
            /** @var ActionMapping $mapping */
            $mapping = new $this->mappingClass($this);

            // process attributes                           // final logical validation is done in ActionMapping::freeze()
            // ------------------
            // attribute path="%RequestPath" #REQUIRED
            $path = (string) $tag['path'];                  // TODO: validate URI
            $mapping->setPath($path);

            // attribute name="%LogicalName" #IMPLIED
            $sName = '';
            if (isset($tag['name'])) {
                $name = (string) $tag['name'];
                $sName = " name=\"$name\"";
                $mapping->setName($name);
            }

            // attribute include="%ResourcePath" #IMPLIED
            if (isset($tag['include'])) {
                if ($mapping->getForward()) Struts::configError('<mapping'.$sName.' path="'.$path.'": Only one of "action", "include", "redirect" or "forward" can be specified.');

                $this->tilesContext = [];
                $include = (string) $tag['include'];
                if (!$this->isIncludable($include, $xml)) Struts::configError('<mapping'.$sName.' path="'.$path.'" include="'.$include.'": '.(strStartsWith($include, '.') ? 'Tile definition':'File').' not found.');

                if ($this->isTileDefinition($include, $xml)) {
                    $tile = $this->getTile($include, $xml);
                    if ($tile->isAbstract()) Struts::configError('<mapping'.$sName.' path="'.$path.'" include="'.$include.'": The included tile is a template and cannot be used in a "mapping" definition.');
                    /** @var ActionForward $forward */
                    $forward = new $this->forwardClass('generic', $include, false);
                }
                else {
                    /** @var ActionForward $forward */
                    $forward = new $this->forwardClass('generic', (string) $this->findFile($include), false);
                }
                $mapping->setForward($forward);
            }

            // attributes redirect="%RequestPath" #IMPLIED and redirect-type="[temporary|permanent]" "temporary"
            if (isset($tag['redirect'])) {
                if ($mapping->getForward()) Struts::configError('<mapping'.$sName.' path="'.$path.'": Only one of "action", "include", "redirect" or "forward" can be specified');
                $redirect     = (string) $tag['redirect'];      // TODO: validate URI
                $redirectType = isset($tag['redirect-type']) ? (string) $tag['redirect-type'] : 'temporary';
                $redirectType = $redirectType=='temporary' ? HttpResponse::SC_MOVED_TEMPORARILY : HttpResponse::SC_MOVED_PERMANENTLY;

                /** @var ActionForward $forward */
                $forward = new $this->forwardClass('generic', $redirect, true, $redirectType);
                $mapping->setForward($forward);
            }
            elseif (isset($tag['redirect-type'])) Struts::configError('<mapping'.$sName.' path="'.$path.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

            // attribute forward="%LogicalName" #IMPLIED
            if (isset($tag['forward'])) {
                if ($mapping->getForward()) Struts::configError('<mapping'.$sName.' path="'.$path.'": Only one of "action", "include", "redirect", "forward" can be specified.');
                $forwardAttr = (string) $tag['forward'];

                /** @var ?ActionForward $forward */
                $forward = $this->findForward($forwardAttr);
                if (!$forward)              Struts::configError('<mapping'.$sName.' path="'.$path.'": Forward "'.$forwardAttr.'" not found.');
                $mapping->setForward($forward);
            }
            if ($mapping->getForward() && sizeof($tag->xpath('./forward') ?: [])) Struts::configError('<mapping'.$sName.' path="'.$path.'": Only an "include", "redirect" or "forward" attribute *or* nested <forward> elements can be specified.');

            // attribute action="%ClassName" #IMPLIED
            if (isset($tag['action'])) {
                if ($mapping->getForward())  Struts::configError('<mapping'.$sName.' path="'.$path.'": Only one of "action", "include", "redirect" or "forward" can be specified.');
                $name = trim((string) $tag['action']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            Struts::configError('<mapping'.$sName.' path="'.$path.'" action="'.$tag['action'].'": Class not found.');
                if (sizeof($classNames) > 1) Struts::configError('<mapping'.$sName.' path="'.$path.'" action="'.$tag['action'].'": Ambiguous class name, found "'.join('", "', $classNames).'".');
                $mapping->setActionClass($classNames[0]);
            }

            // attribute form="%ClassName" #IMPLIED
            if (isset($tag['form'])) {
                $name = trim((string) $tag['form']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            Struts::configError('<mapping'.$sName.' path="'.$path.'" form="'.$tag['form'].'": Class not found.');
                if (sizeof($classNames) > 1) Struts::configError('<mapping'.$sName.' path="'.$path.'" form="'.$tag['form'].'": Ambiguous class name, found "'.join('", "', $classNames).'".');
                $mapping->setFormClass($classNames[0]);
            }

            // attribute form-validate-first="%Boolean" "true"
            $formValidateFirst = false;
            if ($mapping->getFormClass()) {
                $action = $mapping->getActionClass();
                if ($action || $mapping->getForward()) {
                    $formValidateFirst = isset($tag['form-validate-first']) ? ($tag['form-validate-first']=='true') : !$action;
                }
                else {
                    if ($tag['form-validate-first']=='false') Struts::configError('<mapping'.$sName.' path="'.$path.'": An "action", "include", "redirect" or "forward" attribute must be defined if "form-validate-first" is set to "false"');
                    $formValidateFirst = true;
                    // checking existence of "success" und "error" takes place in ActionMapping:freeze()
                }
            }
            elseif ($tag['form-validate-first'] == 'true') {
                Struts::configError('<mapping'.$sName.' path="'.$path.'": A "form" attribute must be specified if "form-validate-first" is set to "true"');
            }
            $mapping->setFormValidateFirst($formValidateFirst);

            // attribute methods="CDATA" "get"
            if (isset($tag['methods'])) {
                $methods = explode(',', (string) $tag['methods']);
                foreach ($methods as $method) {
                    $mapping->setMethod(trim($method));
                }
            }
            else {
                $mapping->setMethod('GET');
            }

            // attribute roles="CDATA" #IMPLIED
            if (isset($tag['roles'])) {
                if (!$this->roleProcessorClass) Struts::configError("<mapping$sName path=\"$path\" roles=\"$tag[roles]\": RoleProcessor configuration not found");
                $mapping->setRoles((string) $tag['roles']);
            }

            // attribute default="%Boolean" "false"
            if ($tag['default']) {
                $mapping->setDefault($tag['default']=='true');
            }

            // process child nodes
            // -------------------
            // process all local forwards having no "forward" attribute
            $subElements = $tag->xpath('./forward[not(@forward)]') ?: [];

            foreach ($subElements as $forwardTag) {
                $name = (string) $forwardTag['name'];
                if (strCompareI($name, ActionForward::SELF)) Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Can not use reserved name "'.$name.'".');

                $include      = isset($forwardTag['include'      ]) ? (string)$forwardTag['include'      ] : null;
                $redirect     = isset($forwardTag['redirect'     ]) ? (string)$forwardTag['redirect'     ] : null;
                $redirectType = isset($forwardTag['redirect-type']) ? (string)$forwardTag['redirect-type'] : 'temporary';
                $mappingAttr  = isset($forwardTag['mapping'      ]) ? (string)$forwardTag['mapping'      ] : null;
                $alias        = isset($forwardTag['forward'      ]) ? (string)$forwardTag['forward'      ] : null;

                /** @var ActionForward|null $forward */
                $forward = null;

                if (isset($include)) {
                    if (isset($redirect) || isset($mappingAttr) || isset($alias)) Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');

                    $this->tilesContext = [];
                    if (!$this->isIncludable($include, $xml))                     Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'" include="'.$include.'": '.(strStartsWith($include, '.') ? 'Tiles definition':'File').' not found.');

                    if ($this->isTileDefinition($include, $xml)) {
                        $tile = $this->getTile($include, $xml);
                        if ($tile->isAbstract())                                  Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'" include="'.$include.'": The included tile is a template and cannot be used as a forward resource.');
                        $forward = new $this->forwardClass($name, $include, false);
                    }
                    else {
                        $forward = new $this->forwardClass($name, (string) $this->findFile($include), false);
                    }
                }

                if (isset($redirect)) {
                    if (isset($include) || isset($mappingAttr) || isset($alias)) Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');
                    $redirectType = $redirectType=='temporary' ? HttpResponse::SC_MOVED_TEMPORARILY : HttpResponse::SC_MOVED_PERMANENTLY;
                    $forward = new $this->forwardClass($name, $redirect, true, $redirectType);  // TODO: URL validieren
                }
                elseif (isset($forwardTag['redirect-type']))                  Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

                if (isset($mappingAttr)) {
                    if (isset($include) || isset($redirect) || isset($alias)) Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');
                    $mappingTag = first($xml->xpath('/struts-config/action-mappings/mapping[@name="'.$mappingAttr.'"]') ?: []);
                    if (!$mappingTag)                                         Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Referenced action mapping "'.$mapping.'" not found.');
                    $mappingPath = (string)$mappingTag['path'];
                    $forward = new $this->forwardClass($name, $mappingPath, true);
                }

                if (!$forward) Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": One of "include", "redirect", "mapping" or "forward" must be specified.');
                $mapping->addForward($name, $forward);
            }

            // process all local forwards having a "forward" attribute (parsed after the others to be able to find them)
            $subElements = $tag->xpath('./forward[@forward]') ?: [];

            foreach ($subElements as $forwardTag) {
                $name  = (string) $forwardTag['name'   ];
                $alias = (string) $forwardTag['forward'];
                if ($name == $alias)                                                 Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": The attribute forward="'.$alias.'" must not be self-referencing.');
                if (isset($forwardTag['include']) || isset($forwardTag['redirect'])) Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect" or "forward" can be specified.');
                if (isset($forwardTag['redirect-type']))                             Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

                $forward = $mapping->findForward($alias);
                if (!$forward) Struts::configError('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Referenced forward "'.$alias.'" not found.');

                $mapping->addForward($name, $forward);
            }

            // done
            $this->addMapping($mapping);
        }
    }


    /**
     * Process all configured {@link Tile}s.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @return void
     */
    protected function processTiles(SimpleXMLElement $xml): void {
        $namespace = '';                                            // default is the global namespace

        if ($tiles = $xml->xpath('/struts-config/tiles') ?: []) {
            $tiles = $tiles[0];

            // attribute class="%ClassName" #IMPLIED
            if (isset($tiles['class'])) {
                $class   = trim((string) $tiles['class']);
                $classes = $this->resolveClassName($class);
                if (!$classes)            Struts::configError('<tiles class="'.$tiles['class'].'": Class not found.');
                if (sizeof($classes) > 1) Struts::configError('<tiles class="'.$tiles['class'].'": Ambiguous class name, found "'.join('", "', $classes).'".');
                $this->setTilesClass($classes[0]);
            }

            // attribute namespace="%ResourcePath" #IMPLIED
            if (isset($tiles['namespace'])) {
                $namespace = trim((string) $tiles['namespace']);
                $namespace = str_replace('/', '\\', $namespace);

                if ($namespace == '\\') {
                    $namespace = '';
                }
                elseif (strlen($namespace)) {
                    if (!$this->isValidNamespace($namespace)) Struts::configError('<tiles namespace="'.$tiles['namespace'].'": Invalid namespace');
                    if (strStartsWith($namespace, '\\')) $namespace  = substr($namespace, 1);
                    if (!strEndsWith($namespace, '\\'))  $namespace .= '\\';
                }
            }
        }
        $this->viewNamespace = $namespace;

        $elements = $xml->xpath('/struts-config/tiles/tile') ?: [];

        foreach ($elements as $tag) {
            $this->tilesContext = [];
            $name = (string) $tag['name'];
            $this->getTile($name, $xml);
        }
    }


    /**
     * Return the initialized {@link Tile} with the specified name.
     *
     * @param  string           $name - tile name
     * @param  SimpleXMLElement $xml  - module configuration
     *
     * @return Tile
     */
    private function getTile(string $name, SimpleXMLElement $xml): Tile {
        // if the tile is already registered return it
        if (isset($this->tiles[$name])) {
            return $this->tiles[$name];
        }

        // detect and block circular tile references
        if (\in_array($name, $this->tilesContext, true)) {
            $this->tilesContext[] = $name;
            Struts::configError('Circular tile reference detected: "'.join('" -> "', $this->tilesContext).'"');
        }
        $this->tilesContext[] = $name;

        // find the tile definition...
        $nodes = $xml->xpath("/struts-config/tiles/tile[@name=\"$name\"]") ?: [];
        if (!$nodes)            Struts::configError("Tile named \"$name\" not found");
        if (sizeof($nodes) > 1) Struts::configError("Multiple tiles named \"$name\" found");

        $tag = $nodes[0];

        $file    = isset($tag['file'   ]) ? (string)$tag['file'   ] : null;
        $extends = isset($tag['extends']) ? (string)$tag['extends'] : null;
        $alias   = isset($tag['alias'  ]) ? (string)$tag['alias'  ] : null;
        $push    = isset($tag['push'   ]) ? (string)$tag['push'   ] : null;

        // attribute "alias" %LogicalName; #IMPLIED
        if (is_string($alias)) {
            if (is_string($file) || is_string($extends)) Struts::configError("<tile name=\"$name\": Only one of \"file\", \"extends\" or \"alias\" can be specified.");
            if (is_string($push))                        Struts::configError("<tile name=\"$name\" alias=\"$alias\" push=\"$push\": The \"alias\" and \"push\" attributes cannot be combined.");
            $tile = $this->getTile($alias, $xml);
            $this->addTile($tile, $name);
            return $tile;
        }

        // attribute "file" %ResourcePath; #IMPLIED
        if (is_string($file)) {
            if (is_string($extends)) Struts::configError("<tile name=\"$name\": Only one of \"file\", \"extends\" or \"alias\" can be specified.");

            $filePath = $this->findFile($file);
            if (!$filePath) Struts::configError("<tile name=\"$name\" file=\"$file\": File not found.");

            /** @var Tile $tile */
            $tile = new $this->tilesClass($this);
            $tile->setName($name);
            $tile->setFileName($filePath);
        }

        // attribute "extends" %LogicalName; #IMPLIED
        elseif (is_string($extends)) {
            $extended = $this->getTile($extends, $xml);
            $tile = clone $extended;                    // clone the extended tile
            $tile->setName($name);
        }
        else {
            Struts::configError("<tile name=\"$name\": One of \"file\", \"extends\" or \"alias\" must be specified.");
        }

        // attribute "push" %Boolean; "false"
        if (is_string($push)) {
            $tile->setPushModelSupport($push == 'true');
        }

        // process its child nodes
        $this->processTileProperties($tile, $tag);

        // finally save the tile
        $this->addTile($tile);
        return $tile;
    }


    /**
     * Process the child nodes of a {@link Tile} definition.
     *
     * @param  Tile             $tile
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @return void
     */
    private function processTileProperties(Tile $tile, SimpleXMLElement $xml): void {
        // process <include> elements
        foreach ($xml->{'include'} as $tag) {
            $name = (string) $tag['name'];
            $nodes = $xml->xpath("/struts-config/tiles/tile[@name='".$tile->getName()."']/include[@name='$name']") ?: [];
            if (sizeof($nodes) > 1)                     Struts::configError('<tile name="'.$tile->getName()."\"> <include name=\"$name\">: Multiple elements with the same name found.");

            if (isset($tag['value'])) {                                 // "value" is specified
                $value = (string) $tag['value'];
                if (!$this->isIncludable($value, $xml)) Struts::configError('<tile name="'.$tile->getName()."\"> <include name=\"$name\" value=\"$value\": ".(strStartsWith($value, '.') ? 'Tile definition':'File').' not found.');

                if ($this->isTileDefinition($value, $xml)) {
                    $nestedTile = $this->getTile($value, $xml);
                    if ($nestedTile->isAbstract())      Struts::configError('<tile name="'.$tile->getName().'"> <include name="'.$name.'" value="'.$value.'": A tiles template or layout cannot be used in an "include" definition.');
                }
                else {
                    /** @var string $file */
                    $file = $this->findFile($value);
                    /** @var Tile $nestedTile */
                    $nestedTile = new $this->tilesClass($this, $tile);  // create a generic Tile, thereby render() exists
                    $nestedTile->setName(Tile::GENERIC_NAME)
                               ->setFileName($file);
                }
                if ($tile->isPushModelSupport()!==null && $nestedTile->isPushModelSupport()===null)
                    $nestedTile->setPushModelSupport($tile->isPushModelSupport());
            }
            else {
                $nestedTile = null;                                     // resource not yet defined, it's an abstract template
            }
            $tile->setNestedTile($name, $nestedTile);
        }

        // process <set> elements
        foreach ($xml->{'set'} as $tag) {
            $name = (string) $tag['name'];
            $nodes = $xml->xpath("/struts-config/tiles/tile[@name='".$tile->getName()."']/set[@name='".$name."']") ?: [];
            if (sizeof($nodes) > 1)            Struts::configError('<tile name="'.$tile->getName().'"> <set name="'.$name.'": Multiple elements with the same name found.');

            if (isset($tag['value'])) {                                 // value is specified as attribute
                if (strlen((string) $tag) > 0) Struts::configError('<tile name="'.$tile->getName().'"> <set name="'.$name.'": Only one of attribute value or tag body value can be specified.');
                $value = (string) $tag['value'];
            }
            else {                                                      // value is specified as tag content
                $value = trim((string) $tag);
            }

            // TODO: check that $value matches the specified type
            switch ((string) $tag['type'] ?: 'string') {
                case 'bool' : $value =  (bool) $value; break;
                case 'int'  : $value =   (int) $value; break;
                case 'float': $value = (float) $value; break;
            }
            $tile->setProperty($name, $value);
        }
    }


    /**
     * Add a global {@link ActionForward} to this module. If an alias is specified the forward is stored under that name
     * instead of the forward's own name.
     *
     * @param  ActionForward $forward
     * @param  ?string       $alias [optional] - alias name of the forward
     *
     * @return void
     */
    protected function addGlobalForward(ActionForward $forward, ?string $alias = null): void {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $name = $alias ?? $forward->getName();

        if (isset($this->globalForwards[$name])) Struts::configError('Non-unique name detected for global ActionForward "'.$name.'"');
        $this->globalForwards[$name] = $forward;
    }


    /**
     * Add an {@link ActionMapping} to this module.
     *
     * @param  ActionMapping $mapping
     *
     * @return void
     */
    protected function addMapping(ActionMapping $mapping): void {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if ($mapping->isDefault()) {
            if ($this->defaultMapping) Struts::configError('Only one action mapping can be marked as "default" within a module.');
            $this->defaultMapping = $mapping;
        }

        $name = $mapping->getName();
        if (strlen($name)) {
            if (isset($this->mappings['names'][$name])) Struts::configError('All action mappings must have unique name attributes, non-unique name: "'.$name.'"');
            $this->mappings['names'][$name] = $mapping;
        }

        $path = $mapping->getPath();
        if (!strEndsWith($path, '/'))
            $path .= '/';
        if (isset($this->mappings['paths'][$path])) Struts::configError('All action mappings must have unique path attributes, non-unique path: "'.$mapping->getPath().'"');
        $this->mappings['paths'][$path] = $mapping;
    }


    /**
     * Add a {@link Tile} to this module. If an alias is specified the tile is stored under both names (alias and tile name).
     *
     * @param  Tile    $tile
     * @param  ?string $alias [optional] - alias name of the tile
     *
     * @return void
     */
    protected function addTile(Tile $tile, ?string $alias = null): void {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $name = $tile->getName();
        $this->tiles[$name] = $tile;

        if (isset($alias)) {
            $this->tiles[$alias] = $tile;
        }
    }


    /**
     * Lookup the {@link ActionMapping} responsible for processing the given request path.
     *
     * @param  string $path
     *
     * @return ?ActionMapping - mapping or NULL if no such mapping was found
     */
    public function findMapping(string $path): ?ActionMapping {
        if (!strEndsWith($path, '/')) {
            $path .= '/';
        }
        // $path: /
        // $path: /action/
        // $path: /controller/action/
        // $path: /controller/action/parameter/

        $pattern = $path;
        while (strlen($pattern)) {
            if (isset($this->mappings['paths'][$pattern])) {    // path keys start and end with a slash "/"
                return $this->mappings['paths'][$pattern];
            }
            $pattern = strLeftTo($pattern, '/', -2, true);
            if ($pattern == '/') {
                break;
            }
        }
        return null;
    }


    /**
     * Return the {@link ActionMapping} with the given name.
     *
     * @param  string $name
     *
     * @return ?ActionMapping - mapping or NULL if no such mapping exists
     */
    public function getMapping(string $name): ?ActionMapping {
        return $this->mappings['names'][$name] ?? null;
    }


    /**
     * Return the module's default {@link ActionMapping} (if configured).
     *
     * @return ?ActionMapping - instance or NULL if no default mapping is configured
     */
    public function getDefaultMapping(): ?ActionMapping {
        return $this->defaultMapping;
    }


    /**
     * Process the configured import settings.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @return void
     */
    protected function processImports(SimpleXMLElement $xml): void {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $imports = $xml->xpath('/struts-config/imports/import') ?: [];

        foreach ($imports as $import) {
            $value = trim((string)$import['value']);
            $value = str_replace('/', '\\', $value);

            if (strEndsWith($value, '\\*')) {           // imported namespace
                $value = strLeft($value, -1);
                if (!$this->isValidNamespace($value)) Struts::configError('<imports> <import value="'.$import['value'].'": Invalid value (neither a class nor a namespace).');
                if (strStartsWith($value, '\\')) $value  = substr($value, 1);
                if (!strEndsWith($value, '\\'))  $value .= '\\';
                $this->imports[strtolower($value)] = $value;
                continue;
            }

            if (class_exists($value)) {                 // imported class
                if (strStartsWith($value, '\\')) $value = substr($value, 1);
                $simpleName = simpleClassName($value);
                if (isset($this->uses[$simpleName])) Struts::configError('<imports> <import value="'.$import['value'].'": Duplicate value.');
                $this->uses[$simpleName] = $value;
                continue;
            }
            Struts::configError('<imports> <import value="'.$import['value'].'": Invalid value (neither a class nor a namespace).');
        }
    }


    /**
     * Process the configured controller settings.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @return void
     */
    protected function processController(SimpleXMLElement $xml): void {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $elements = $xml->xpath('/struts-config/controller') ?: [];

        foreach ($elements as $tag) {
            if (isset($tag['request-processor'])) {
                $name = trim((string) $tag['request-processor']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            Struts::configError('<controller request-processor="'.$tag['request-processor'].'": Class not found.');
                if (sizeof($classNames) > 1) Struts::configError('<controller request-processor="'.$tag['request-processor'].'": Ambiguous class name, found "'.join('", "', $classNames).'"');
                $this->setRequestProcessorClass($classNames[0]);
            }

            if (isset($tag['role-processor'])) {
                $name = trim((string) $tag['role-processor']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            Struts::configError('<controller role-processor="'.$tag['role-processor'].'": Class not found.');
                if (sizeof($classNames) > 1) Struts::configError('<controller role-processor="'.$tag['role-processor'].'": Ambiguous class name, found "'.join('", "', $classNames).'"');
                $this->setRoleProcessorClass($classNames[0]);
            }
        }
    }


    /**
     * Set the classname of the {@link RequestProcessor} implementation to be used by the module.
     * The class must be a subclass {@link RequestProcessor}.
     *
     * @param  string $className
     *
     * @return $this
     */
    protected function setRequestProcessorClass(string $className): self {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if (!is_subclass_of($className, RequestProcessor::class)) {
            Struts::configError("Invalid request processor class: $className (not a subclass of ".RequestProcessor::class.")");
        }
        $this->requestProcessorClass = $className;
        return $this;
    }


    /**
     * Return the classname of the {@link RequestProcessor} implementation used by the module.
     *
     * @return         string
     * @phpstan-return class-string<RequestProcessor>
     */
    public function getRequestProcessorClass(): string {
        return $this->requestProcessorClass;
    }


    /**
     * Set the classname of the {@link RoleProcessor} implementation to be used by the module.
     * The class must be a subclass of {@link RoleProcessor}.
     *
     * @param  string $className
     *
     * @return $this
     */
    protected function setRoleProcessorClass(string $className): self {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if (!is_subclass_of($className, RoleProcessor::class)) {
            Struts::configError("Invalid role processor class: $className (not a subclass of ".RoleProcessor::class.")");
        }
        $this->roleProcessorClass = $className;
        return $this;
    }


    /**
     * Return the {@link RoleProcessor} intance of the module.
     *
     * @return ?RoleProcessor - instance or NULL if no RoleProcessor is configured for the module
     */
    public function getRoleProcessor(): ?RoleProcessor {
        if (!$this->roleProcessor) {
            $class = $this->roleProcessorClass;
            if (isset($class)) {
                $this->roleProcessor = new $class();
            }
        }
        return $this->roleProcessor;
    }


    /**
     * Set the classname of the {@link Tile} implementation to be used by the module.
     * The class must be a subclass of {@link Tile}.
     *
     * @param  string $className
     *
     * @return $this
     */
    protected function setTilesClass(string $className): self {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if (!is_subclass_of($className, Tile::class)) {
            Struts::configError("Invalid tiles class: $className (not a subclass of ".Tile::class.")");
        }

        $this->tilesClass = $className;
        return $this;
    }


    /**
     * Return the classname of the {@link Tile} implementation used by the module.
     *
     * @return         string
     * @phpstan-return class-string<Tile>
     */
    public function getTilesClass(): string {
        return $this->tilesClass;
    }


    /**
     * Set the classname of the {@link ActionMapping} implementation to be used by the module.
     * The class must be a subclass of {@link ActionMapping}.
     *
     * @param  string $className
     *
     * @return $this
     */
    protected function setMappingClass(string $className): self {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if (!is_subclass_of($className, ActionMapping::class)) {
            Struts::configError("Invalid action mapping class: $className (not a subclass of ".ActionMapping::class.")");
        }
        $this->mappingClass = $className;
        return $this;
    }


    /**
     * Return the classname of the {@link ActionMapping} implementation used by the module.
     *
     * @return         string
     * @phpstan-return class-string<ActionMapping>
     */
    public function getMappingClass(): string {
        return $this->mappingClass;
    }


    /**
     * Set the classname of the {@link ActionForward} implementation to be used by the module.
     * The class must be a subclass of {@link ActionForward}.
     *
     * @param  string $className
     *
     * @return $this
     */
    protected function setForwardClass(string $className): self {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if (!is_subclass_of($className, ActionForward::class)) {
            Struts::configError("Invalid action forward class: $className (not a subclass of ".ActionForward::class.")");
        }
        $this->forwardClass = $className;
        return $this;
    }


    /**
     * Return the classname of the {@link ActionForward} implementation used by the module.
     *
     * @return         string
     * @phpstan-return class-string<ActionForward>
     */
    public function getForwardClass(): string {
        return $this->forwardClass;
    }


    /**
     * Return the default view namespace used by file resources and tiles.
     *
     * @return string
     */
    public function getViewNamespace(): string {
        return $this->viewNamespace;
    }


    /**
     * Lock the configuration of this component. After the method returned modifications of the component will trigger
     * an exception.
     *
     * @return $this
     */
    public function freeze(): self {
        if (!$this->configured) {
            foreach ($this->mappings['paths'] as $mapping) {        // $mappings['paths'] contains all mapping, incl.
                $mapping->freeze();                                 // those in $mappings['names']
            }
            foreach ($this->tiles as $i => $tile) {
                if ($tile->isAbstract()) unset($this->tiles[$i]);
                else                     $tile->freeze();
            }
            $this->configured = true;
        }
        return $this;
    }


    /**
     * Find and return the global {@link ActionForward} with the specified name.
     *
     * @param  string $name - forward name
     *
     * @return ?ActionForward - instance or NULL if no such forward was found
     */
    public function findForward(string $name): ?ActionForward {
        return $this->globalForwards[$name] ?? null;
    }


    /**
     * Find and return the {@link Tile} with the specified name.
     *
     * @param  string $name - tile name
     *
     * @return ?Tile - instance or NULL if no such tile was found
     */
    public function findTile(string $name): ?Tile {
        return $this->tiles[$name] ?? null;
    }


    /**
     * Whether a file resource or a {@link Tile} exists under the specified name.
     *
     * @param  string           $name - resource name
     * @param  SimpleXMLElement $xml  - module configuration
     *
     * @return bool
     */
    private function isIncludable(string $name, SimpleXMLElement $xml): bool {
        return $this->isTileDefinition($name, $xml) || $this->isFile($name);
    }


    /**
     * Whether a {@link Tile} with the specified name is defined in the module's configuration.
     *
     * @param  string           $name - tile name
     * @param  SimpleXMLElement $xml  - module configuration
     *
     * @return bool
     */
    private function isTileDefinition(string $name, SimpleXMLElement $xml): bool {
        $nodes = $xml->xpath("/struts-config/tiles/tile[@name='".$name."']") ?: [];
        return (bool) sizeof($nodes);
    }


    /**
     * Whether a file resource with the specified name exists in the module's configured resource locations.
     *
     * @param  string $path - filename
     *
     * @return bool
     */
    private function isFile(string $path): bool {
        $filename = $this->findFile($path);
        return $filename !== null;
    }


    /**
     * Find a file with the specified name in the module's configured resource locations, and return its full name.
     *
     * @param  string $name - relative name
     *
     * @return ?string - full filename or NULL if no such file was found
     */
    private function findFile(string $name): ?string {
        // strip a potential query string
        $parts = explode('?', $name, 2);

        foreach ($this->resourceLocations as $location) {
            if (is_file($location.DIRECTORY_SEPARATOR.$parts[0])) {
                $name = realpath($location.DIRECTORY_SEPARATOR.\array_shift($parts));
                if ($parts)
                    $name .= '?'.$parts[1];     // re-attach a stripped query string
                return $name;
            }
        }
        return null;
    }


    /**
     * Whether a string represents a valid namespace.
     *
     * @param  string $value
     *
     * @return bool
     */
    private function isValidNamespace(string $value): bool {
        $pattern = '/^\\\\?[a-z_][a-z0-9_]*(\\\\[a-z_][a-z0-9_]*)*\\\\?$/i';
        return (bool) preg_match($pattern, $value);
    }


    /**
     * Resolve a simple class name and return all found fully qualified class names.
     *
     * @param  string $name
     *
     * @return string[] - found class names or an empty array if the class name cannot be resolved
     */
    private function resolveClassName(string $name): array {
        $name = str_replace('/', '\\', trim($name));

        // no need to resolve a qualified name
        if (strContains($name, '\\')) {
            return class_exists($name) ? [$name] : [];
        }

        // unqualified name, check "use" declarations
        $nameL = strtolower($name);
        if (isset($this->uses[$nameL])) {
            return [$this->uses[$nameL]];
        }

        // unqualified name, check imported namespaces
        $results = [];
        foreach ($this->imports as $namespace) {
            $class = $namespace.$name;
            if (class_exists($class)) {
                $results[] = $class;
            }
        }
        return $results;
    }
}
