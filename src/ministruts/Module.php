<?php
namespace rosasurfer\ministruts;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\IllegalStateException;
use rosasurfer\file\xml\SimpleXMLElement;
use rosasurfer\net\http\HttpResponse;

use function rosasurfer\first;
use function rosasurfer\is_class;
use function rosasurfer\isRelativePath;
use function rosasurfer\simpleClassName;
use function rosasurfer\strCompareI;
use function rosasurfer\strContains;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\strLeftTo;
use function rosasurfer\strStartsWith;

use const rosasurfer\NL;


/**
 * Module
 *
 * Struts modules allow separation (splitting) of a web application into multiple logical sections. Each Module (or section)
 * is defined by a unique base path (aka the Module prefix), e.g. "/admin/", "/backend/" or "/shop/". Each Module has its
 * own Struts configuration and can be configured and managed entirely separate from all other modules.
 *
 * The main Module of an application has the module prefix "" (an empty string), its base path is "/" and its Struts
 * configuration is stored in "struts-config.xml". Other modules of the application have a non-empty prefix, their base path
 * is "/{prefix}/" and their Struts configurations are stored in files named "struts-config-{prefix}.xml". Modules can be
 * nested, and a full module can be moved in the application by only changing the module's prefix.
 *
 * The full URI of a route to a specific module's {@link ActionMapping} is "/{app-base-path}/{module-prefix}/{mapping-path}".
 */
class Module extends CObject {


    /**
     * Module prefix relative to the base URI of the web application. All module prefixes in an application are unique.
     * The module with the prefix "" (empty string) is the main module of an application.
     *
     * @var string (configurable)
     */
    protected $prefix;

    /** @var string[] - imported fully qualified class names (configurable) */
    protected $uses;

    /** @var string[] - imported namespaces (configurable) */
    protected $imports;

    /** @var string[] - base directory for file resources used by the module (configurable) */
    protected $resourceLocations = [];

    /** @var ActionForward[] - all global forwards of the module (configurable) */
    protected $globalForwards = [];

    /** @var ActionMapping[][] - all action mappings of the module (configurable) */
    protected $mappings = [
        'names' => [],
        'paths' => [],
    ];

    /**
     * Default action mapping of the module or NULL if undefined. Used when a request does not match any other action mapping
     * (configurable).
     *
     * @var ActionMapping
     */
    protected $defaultMapping;

    /** @var Tile[] - all tiles of the module (configurable) */
    protected $tiles = [];

    /** @var string - default view namespace for file resources and tiles (configurable) */
    protected $viewNamespace = '';

    /** @var string - classname of the {@link RequestProcessor} implementation used by the module (configurable) */
    protected $requestProcessorClass = DEFAULT_REQUEST_PROCESSOR_CLASS;

    /** @var string - default classname of the {@link ActionForward} implementation used by the module (configurable) */
    protected $forwardClass = DEFAULT_ACTION_FORWARD_CLASS;

    /** @var string - default classname of the {@link ActionMapping} implementation used by the module (configurable) */
    protected $mappingClass = DEFAULT_ACTION_MAPPING_CLASS;

    /** @var string - default classname of the {@link Tile} implementation used by the module (configurable) */
    protected $tilesClass = Tile::class;

    /** @var string - classname of the {@link RoleProcessor} implementation used by the module (configurable) */
    protected $roleProcessorClass;

    /** @var RoleProcessor - the RoleProcessor instance used by the module */
    protected $roleProcessor;

    /** @var string[] - module initialization context for detecting circular tile references */
    protected $tilesContext = [];

    /** @var bool - whether this component is fully configured */
    protected $configured = false;


    /**
     * Create a new instance, read and parse the module's XML configuration (struts-config.xml).
     *
     * @param  string $fileName - full name of the module's configuration file
     * @param  string $prefix   - module prefix
     *
     * @throws StrutsConfigException on configuration errors
     *
     * @todo   check/handle different config file encodings
     */
    public function __construct($fileName, $prefix) {
        Assert::string($fileName, '$fileName');
        Assert::string($prefix, '$prefix');

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
     * Read and validate the module configuration, and convert it to a {@link SimpleXMLElement} instance.
     *
     * @param  string $fileName - full filename
     *
     * @return SimpleXMLElement
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function loadConfiguration($fileName) {
        if (!is_file($fileName)) throw new StrutsConfigException('Configuration file not found: "'.$fileName.'"');

        // TODO: what about checking the search result?
        $content = file_get_contents($fileName);
        $search  = '<!DOCTYPE struts-config SYSTEM "struts-config.dtd">';
        $offset  = strpos($content, $search);
        $dtd     = str_replace('\\', '/', __DIR__.'/dtd/struts-config.dtd');
        $replace = '<!DOCTYPE struts-config SYSTEM "file:///'.$dtd.'">';
        $content = substr_replace($content, $replace, $offset, strlen($search));

        // parse, validate, instantiate...
        return SimpleXMLElement::from($content, LIBXML_DTDVALID|LIBXML_NONET);
    }


    /**
     * Return the prefix of this module. All module prefixes of an application are unique.
     *
     * @return string - an empty string for the main module;
     *                  a path fragment not starting but ending with a slash "/" for a non-main module
     */
    public function getPrefix() {
        return $this->prefix;
    }


    /**
     * Set the module prefix.
     *
     * @param  string $prefix - the prefix of the main module is an empty string;
     *                          prefixes of non-main modules must not start but must end with a slash "/"
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setPrefix($prefix) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        if ($len=strlen($prefix)) {
            if ($prefix[     0] == '/') throw new StrutsConfigException('Non-main module prefixes must not start with a slash "/" character, found: "'.$prefix.'"');
            if ($prefix[$len-1] != '/') throw new StrutsConfigException('Non-main module prefixes must end with a slash "/" character, found: "'.$prefix.'"');
        }
        $this->prefix = $prefix;
    }


    /**
     * Set the default namespace used by the Module when looking up relative classnames.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setNamespace(SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        // default is the global namespace
        $namespace = '';

        if (isset($xml['namespace'])) {
            $namespace = trim((string) $xml['namespace']);
            $namespace = str_replace('/', '\\', $namespace);

            if ($namespace == '\\') {           // that's again the global namespace
                $namespace = '';
            }
            else if (strlen($namespace)) {
                if (!$this->isValidNamespace($namespace)) throw new StrutsConfigException('<struts-config namespace="'.$xml['namespace'].'": Invalid module namespace');
                if (strStartsWith($namespace, '\\')) $namespace  = substr($namespace, 1);
                if (!strEndsWith($namespace, '\\'))  $namespace .= '\\';
            }
        }
        $this->imports[strtolower($namespace)] = $namespace;
    }


    /**
     * Set the base directory used by the module when looking up file resources.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setResourceBase(SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        /** @var ConfigInterface $config */
        $config = $this->di('config');
        $rootDirectory = $config['app.dir.root'];

        if (!isset($xml['file-base'])) {
            // not specified, apply global configuration
            $location = $config->get('app.dir.view', null);
            if (!$location) throw new StrutsConfigException('Missing view directory configuration: '
                .'Neither $config[app.dir.view] nor <struts-config file-base="{base-directory}" are specified'.NL
                .'config: '.NL.$config->dump());
            isRelativePath($location) && $location = $rootDirectory.DIRECTORY_SEPARATOR.$location;
            if (!is_dir($location)) throw new StrutsConfigException('Resource location $config[app.dir.view]="'.$config['app.dir.view'].'" not found');

            $this->resourceLocations[] = realpath($location);
            return;
        }

        $locations = explode(',', (string) $xml['file-base']);

        foreach ($locations as $i => $location) {
            $location = trim($location);
            if (!strlen($location)) continue;

            isRelativePath($location) && $location = $rootDirectory.DIRECTORY_SEPARATOR.$location;
            if (!is_dir($location)) throw new StrutsConfigException('<struts-config file-base="'.$locations[$i].'": Resource location not found');

            $this->resourceLocations[] = realpath($location);
        }
    }


    /**
     * Process all configured global {@link ActionForward}s.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function processGlobalForwards(SimpleXMLElement $xml) {
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

            /** @var ActionForward $forward */
            $forward = null;

            if (isset($include)) {
                if (isset($redirect) || isset($mapping) || isset($alias)) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');

                $this->tilesContext = [];
                if (!$this->isIncludable($include, $xml))                 throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'" include="'.$include.'": '.(strStartsWith($include, '.') ? 'Tile definition':'File').' not found.');

                if ($this->isTileDefinition($include, $xml)) {
                    $tile = $this->getTile($include, $xml);
                    if ($tile->isAbstract())                              throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'" include="'.$include.'": The included tile is a template and cannot be used as a forward resource.');
                    /** @var ActionForward $forward */
                    $forward = new $this->forwardClass($name, $include, false);
                }
                else {
                    /** @var ActionForward $forward */
                    $forward = new $this->forwardClass($name, $this->findFile($include), false);
                }
            }

            if (isset($redirect)) {
                if (isset($include) || isset($mapping) || isset($alias))  throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');
                $redirectType = $redirectType=='temporary' ? HttpResponse::SC_MOVED_TEMPORARILY : HttpResponse::SC_MOVED_PERMANENTLY;

                /** @var ActionForward $forward */
                $forward = new $this->forwardClass($name, $redirect, true, $redirectType);  // TODO: URL validieren
            }
            elseif (isset($tag['redirect-type']))                         throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

            if (isset($mapping)) {
                if (isset($include) || isset($redirect) || isset($alias)) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');
                $tag = first($xml->xpath('/struts-config/action-mappings/mapping[@name="'.$mapping.'"]') ?: []);
                if (!$tag)                                                throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": Referenced action mapping "'.$mapping.'" not found.');
                $path = (string)$tag['path'];

                /** @var ActionForward $forward */
                $forward = new $this->forwardClass($name, $path, true);
            }

            if (!$forward) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": One of "include", "redirect", "mapping" or "forward" must be specified.');
            $this->addGlobalForward($forward);
        }

        // process all global forwards having a "forward" attribute (parsed after the others to be able to find them)
        $elements = $xml->xpath('/struts-config/global-forwards/forward[@forward]') ?: [];

        foreach ($elements as $tag) {
            $name  = (string)$tag['name'   ];
            $alias = (string)$tag['forward'];
            if ($name == $alias)                                   throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": The attribute forward="'.$alias.'" must not be self-referencing.');

            if (isset($tag['include']) || isset($tag['redirect'])) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": Only one of "include", "redirect" or "forward" can be specified.');
            if (isset($tag['redirect-type']))                      throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

            $forward = $this->findForward($alias);
            if (!$forward) throw new StrutsConfigException('<global-forwards> <forward name="'.$name.'": Referenced forward "'.$alias.'" not found.');

            $this->addGlobalForward($forward, $name);
        }
    }


    /**
     * Process all configured {@link ActionMapping}s.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function processMappings(SimpleXMLElement $xml) {
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
                $name  = (string) $tag['name'];
                $sName = ' name="'.$name.'"';
                $mapping->setName($name);
            }

            // attribute include="%ResourcePath" #IMPLIED
            if (isset($tag['include'])) {
                if ($mapping->getForward()) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": Only one of "action", "include", "redirect" or "forward" can be specified.');

                $this->tilesContext = [];
                $include = (string) $tag['include'];
                if (!$this->isIncludable($include, $xml)) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'" include="'.$include.'": '.(strStartsWith($include, '.') ? 'Tile definition':'File').' not found.');

                $forward = null;

                if ($this->isTileDefinition($include, $xml)) {
                    $tile = $this->getTile($include, $xml);
                    if ($tile->isAbstract()) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'" include="'.$include.'": The included tile is a template and cannot be used in a "mapping" definition.');
                    /** @var ActionForward $forward */
                    $forward = new $this->forwardClass('generic', $include, false);
                }
                else {
                    /** @var ActionForward $forward */
                    $forward = new $this->forwardClass('generic', $this->findFile($include), false);
                }
                $mapping->setForward($forward);
            }

            // attributes redirect="%RequestPath" #IMPLIED and redirect-type="[temporary|permanent]" "temporary"
            if (isset($tag['redirect'])) {
                if ($mapping->getForward()) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": Only one of "action", "include", "redirect" or "forward" can be specified');
                $redirect     = (string) $tag['redirect'];      // TODO: validate URI
                $redirectType = isset($tag['redirect-type']) ? (string) $tag['redirect-type'] : 'temporary';
                $redirectType = $redirectType=='temporary' ? HttpResponse::SC_MOVED_TEMPORARILY : HttpResponse::SC_MOVED_PERMANENTLY;

                /** @var ActionForward $forward */
                $forward = new $this->forwardClass('generic', $redirect, true, $redirectType);
                $mapping->setForward($forward);
            }
            else if (isset($tag['redirect-type'])) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

            // attribute forward="%LogicalName" #IMPLIED
            if (isset($tag['forward'])) {
                if ($mapping->getForward()) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": Only one of "action", "include", "redirect", "forward" can be specified.');
                $forwardAttr = (string) $tag['forward'];

                /** @var ActionForward $forward */
                $forward = $this->findForward($forwardAttr);
                if (!$forward)              throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": Forward "'.$forwardAttr.'" not found.');
                $mapping->setForward($forward);
            }
            if ($mapping->getForward() && sizeof($tag->xpath('./forward'))) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": Only an "include", "redirect" or "forward" attribute *or* nested <forward> elements can be specified.');

            // attribute action="%ClassName" #IMPLIED
            if (isset($tag['action'])) {
                if ($mapping->getForward())  throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": Only one of "action", "include", "redirect" or "forward" can be specified.');
                $name = trim((string) $tag['action']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'" action="'.$tag['action'].'": Class not found.');
                if (sizeof($classNames) > 1) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'" action="'.$tag['action'].'": Ambiguous class name, found "'.join('", "', $classNames).'".');
                $mapping->setActionClassName($classNames[0]);
            }

            // attribute form="%ClassName" #IMPLIED
            if (isset($tag['form'])) {
                $name = trim((string) $tag['form']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'" form="'.$tag['form'].'": Class not found.');
                if (sizeof($classNames) > 1) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'" form="'.$tag['form'].'": Ambiguous class name, found "'.join('", "', $classNames).'".');
                $mapping->setFormClassName($classNames[0]);
            }

            // attribute form-scope="(request|session)" "request"
            if (isset($tag['form-scope'])) {
                if (!isset($tag['form']))    throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": The "form" attribute must be specified if "form-scope" is defined.');
                $mapping->setFormScope((string) $tag['form-scope']);
            }

            // attribute form-validate-first="%Boolean" "true"
            $formValidateFirst = false;
            if ($mapping->getFormClassName()) {
                $action = $mapping->getActionClassName();
                if ($action || $mapping->getForward()) {
                    $formValidateFirst = isset($tag['form-validate-first']) ? ($tag['form-validate-first']=='true') : !$action;
                }
                else {
                    if ($tag['form-validate-first']=='false') throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": An "action", "include", "redirect" or "forward" attribute must be defined if "form-validate-first" is set to "false"');
                    $formValidateFirst = true;
                    // checking existence of "success" und "error" takes place in ActionMapping:freeze()
                }
            }
            elseif ($tag['form-validate-first'] == 'true') {
                throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": A "form" attribute must be specified if "form-validate-first" is set to "true"');
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
                if (!$this->roleProcessorClass) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'" roles="'.$tag['roles'].'": RoleProcessor configuration not found');
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
                if (strCompareI($name, ActionForward::SELF)) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Can not use reserved name "'.$name.'".');

                $include      = isset($forwardTag['include'      ]) ? (string)$forwardTag['include'      ] : null;
                $redirect     = isset($forwardTag['redirect'     ]) ? (string)$forwardTag['redirect'     ] : null;
                $redirectType = isset($forwardTag['redirect-type']) ? (string)$forwardTag['redirect-type'] : 'temporary';
                $mappingAttr  = isset($forwardTag['mapping'      ]) ? (string)$forwardTag['mapping'      ] : null;
                $alias        = isset($forwardTag['forward'      ]) ? (string)$forwardTag['forward'      ] : null;

                /** @var ActionForward $forward */
                $forward = null;

                if (isset($include)) {
                    if (isset($redirect) || isset($mappingAttr) || isset($alias)) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');

                    $this->tilesContext = [];
                    if (!$this->isIncludable($include, $xml)) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'" include="'.$include.'": '.(strStartsWith($include, '.') ? 'Tiles definition':'File').' not found.');

                    if ($this->isTileDefinition($include, $xml)) {
                        $tile = $this->getTile($include, $xml);
                        if ($tile->isAbstract()) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'" include="'.$include.'": The included tile is a template and cannot be used as a forward resource.');
                        /** @var ActionForward $forward */
                        $forward = new $this->forwardClass($name, $include, false);
                    }
                    else {
                        /** @var ActionForward $forward */
                        $forward = new $this->forwardClass($name, $this->findFile($include), false);
                    }
                }

                if (isset($redirect)) {
                    if (isset($include) || isset($mappingAttr) || isset($alias)) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');
                    $redirectType = $redirectType=='temporary' ? HttpResponse::SC_MOVED_TEMPORARILY : HttpResponse::SC_MOVED_PERMANENTLY;

                    /** @var ActionForward $forward */
                    $forward = new $this->forwardClass($name, $redirect, true, $redirectType);  // TODO: URL validieren
                }
                elseif (isset($forwardTag['redirect-type'])) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

                if (isset($mappingAttr)) {
                    if (isset($include) || isset($redirect) || isset($alias)) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect", "mapping" or "forward" can be specified.');
                    $mappingTag = first($xml->xpath('/struts-config/action-mappings/mapping[@name="'.$mappingAttr.'"]') ?: []);
                    if (!$mappingTag)                                         throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Referenced action mapping "'.$mapping.'" not found.');
                    $mappingPath = (string)$mappingTag['path'];

                    /** @var ActionForward $forward */
                    $forward = new $this->forwardClass($name, $mappingPath, true);
                }

                if (!$forward) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": One of "include", "redirect", "mapping" or "forward" must be specified.');
                $mapping->addForward($name, $forward);
            }

            // process all local forwards having a "forward" attribute (parsed after the others to be able to find them)
            $subElements = $tag->xpath('./forward[@forward]') ?: [];

            foreach ($subElements as $forwardTag) {
                $name  = (string) $forwardTag['name'   ];
                $alias = (string) $forwardTag['forward'];
                if ($name == $alias)                                                 throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": The attribute forward="'.$alias.'" must not be self-referencing.');
                if (isset($forwardTag['include']) || isset($forwardTag['redirect'])) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Only one of "include", "redirect" or "forward" can be specified.');
                if (isset($forwardTag['redirect-type']))                             throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": The "redirect" attribute must be specified if "redirect-type" is defined.');

                $forward = $mapping->findForward($alias);
                if (!$forward) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'"> <forward name="'.$name.'": Referenced forward "'.$alias.'" not found.');

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
     * @throws StrutsConfigException on configuration errors
     */
    protected function processTiles(SimpleXMLElement $xml) {
        $namespace = '';                                            // default is the global namespace

        if ($tiles = $xml->xpath('/struts-config/tiles') ?: []) {
            $tiles = $tiles[0];

            // attribute class="%ClassName" #IMPLIED
            if (isset($tiles['class'])) {
                $class   = trim((string) $tiles['class']);
                $classes = $this->resolveClassName($class);
                if (!$classes)            throw new StrutsConfigException('<tiles class="'.$tiles['class'].'": Class not found.');
                if (sizeof($classes) > 1) throw new StrutsConfigException('<tiles class="'.$tiles['class'].'": Ambiguous class name, found "'.join('", "', $classes).'".');
                $this->setTilesClass($classes[0]);
            }

            // attribute namespace="%ResourcePath" #IMPLIED
            if (isset($tiles['namespace'])) {
                $namespace = trim((string) $tiles['namespace']);
                $namespace = str_replace('/', '\\', $namespace);

                if ($namespace == '\\') {
                    $namespace = '';
                }
                else if (strlen($namespace)) {
                    if (!$this->isValidNamespace($namespace)) throw new StrutsConfigException('<tiles namespace="'.$tiles['namespace'].'": Invalid namespace');
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
     *
     * @throws StrutsConfigException on configuration errors
     */
    private function getTile($name, SimpleXMLElement $xml) {
        // if the tile is already registered return it
        if (isset($this->tiles[$name]))
            return $this->tiles[$name];

        // detect and block circular tile references
        if (in_array($name, $this->tilesContext)) {
            $this->tilesContext[] = $name;
            throw new StrutsConfigException('Circular tile reference detected: "'.join('" -> "', $this->tilesContext).'"');
        }
        $this->tilesContext[] = $name;

        // find the tile definition...
        /** @var SimpleXMLElement[] $nodes */
        $nodes = $xml->xpath("/struts-config/tiles/tile[@name='".$name."']");
        if (!$nodes)            throw new StrutsConfigException('Tile named "'.$name.'" not found');
        if (sizeof($nodes) > 1) throw new StrutsConfigException('Multiple tiles named "'.$name.'" found');

        $tag = $nodes[0];

        $file    = isset($tag['file'   ]) ? (string)$tag['file'   ] : null;
        $extends = isset($tag['extends']) ? (string)$tag['extends'] : null;
        $alias   = isset($tag['alias'  ]) ? (string)$tag['alias'  ] : null;
        $push    = isset($tag['push'   ]) ? (string)$tag['push'   ] : null;

        // attribute "alias" %LogicalName; #IMPLIED
        if (is_string($alias)) {
            if (is_string($file) || is_string($extends)) throw new StrutsConfigException('<tile name="'.$name.'": Only one of "file", "extends" or "alias" can be specified.');
            if (is_string($push))                        throw new StrutsConfigException('<tile name="'.$name.'" alias="'.$alias.' push="'.$push.'": The "alias" and "push" attributes cannot be combined.');
            $tile = $this->getTile($alias, $xml);
            $this->addTile($tile, $name);
            return $tile;
        }

        /** @var Tile $tile */
        $tile = null;

        // attribute "file" %ResourcePath; #IMPLIED
        if (is_string($file)) {
            if (is_string($extends)) throw new StrutsConfigException('<tile name="'.$name.'": Only one of "file", "extends" or "alias" can be specified.');

            /** @var string $filePath */
            $filePath = $this->findFile($file);
            if (!$filePath) throw new StrutsConfigException('<tile name="'.$name.'" file="'.$file.'": File not found.');

            /** @var Tile $tile */
            $tile = new $this->tilesClass($this);
            $tile->setName($name);
            $tile->setFileName($filePath);
        }

        // attribute "extends" %LogicalName; #IMPLIED
        else  {
            $extended = $this->getTile($extends, $xml);
            $tile = clone $extended;                    // clone the extended tile
            $tile->setName($name);
        }

        // attribute "push" %Boolean; "false"
        if (is_string($push)) {
            $tile->setPushModelSupport($push == 'true');
        }

        // process it's child nodes ...
        $this->processTileProperties($tile, $tag);

        // ...finally save the tile
        $this->addTile($tile);
        return $tile;
    }


    /**
     * Process the child nodes of a {@link Tile} definition.
     *
     * @param  Tile             $tile
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @throws StrutsConfigException on configuration errors
     */
    private function processTileProperties(Tile $tile, SimpleXMLElement $xml) {
        // process <include> elements
        foreach ($xml->{'include'} as $tag) {
            $name  = (string) $tag['name'];
            $nodes = $xml->xpath("/struts-config/tiles/tile[@name='".$tile->getName()."']/include[@name='".$name."']");
            if (sizeof($nodes) > 1) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <include name="'.$name.'">: Multiple elements with the same name found.');

            if (isset($tag['value'])) {                                 // "value" is specified
                $value = (string) $tag['value'];
                if (!$this->isIncludable($value, $xml)) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <include name="'.$name.'" value="'.$value.'": '.(strStartsWith($value, '.') ? 'Tile definition':'File').' not found.');

                if ($this->isTileDefinition($value, $xml)) {
                    $nestedTile = $this->getTile($value, $xml);
                    if ($nestedTile->isAbstract()) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <include name="'.$name.'" value="'.$value.'": A tiles template or layout cannot be used in an "include" definition.');
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
            $name  = (string) $tag['name'];
            $nodes = $xml->xpath("/struts-config/tiles/tile[@name='".$tile->getName()."']/set[@name='".$name."']");
            if (sizeof($nodes) > 1) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <set name="'.$name.'": Multiple elements with the same name found.');

            if (isset($tag['value'])) {                                 // value is specified as an attribute
                if (strlen($tag) > 0) throw new StrutsConfigException('<tile name="'.$tile->getName().'"> <set name="'.$name.'": Only one of attribute value or tag body value can be specified.');
                $value = (string) $tag['value'];
            }
            else {                                                      // value is specified as tag content
                $value = trim((string) $tag);
            }

            // TODO: check that $value matches the specified type
            switch (((string)$tag['type']) ?: 'string') {
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
     * @param  string        $alias [optional] - alias name of the forward
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function addGlobalForward(ActionForward $forward, $alias = null) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $name = isset($alias) ? $alias : $forward->getName();

        if (isset($this->globalForwards[$name])) throw new StrutsConfigException('Non-unique name detected for global ActionForward "'.$name.'"');
        $this->globalForwards[$name] = $forward;
    }


    /**
     * Add an {@link ActionMapping} to this module.
     *
     * @param  ActionMapping $mapping
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function addMapping(ActionMapping $mapping) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if ($mapping->isDefault()) {
            if ($this->defaultMapping) throw new StrutsConfigException('Only one action mapping can be marked as "default" within a module.');
            $this->defaultMapping = $mapping;
        }

        $name = $mapping->getName();
        if (strlen($name)) {
            if (isset($this->mappings['names'][$name])) throw new StrutsConfigException('All action mappings must have unique name attributes, non-unique name: "'.$name.'"');
            $this->mappings['names'][$name] = $mapping;
        }

        $path = $mapping->getPath();
        if (!strEndsWith($path, '/'))
            $path .= '/';
        if (isset($this->mappings['paths'][$path])) throw new StrutsConfigException('All action mappings must have unique path attributes, non-unique path: "'.$mapping->getPath().'"');
        $this->mappings['paths'][$path] = $mapping;
    }


    /**
     * Add a {@link Tile} to this module. If an alias is specified the tile is stored under both names (alias and tile name).
     *
     * @param  Tile   $tile
     * @param  string $alias [optional] - alias name of the tile
     */
    protected function addTile(Tile $tile, $alias = null) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $name = $tile->getName();
        $this->tiles[$name] = $tile;

        if (isset($alias))
            $this->tiles[$alias] = $tile;
    }


    /**
     * Lookup the {@link ActionMapping} responsible for processing the given request path.
     *
     * @param  string $path
     *
     * @return ActionMapping|null - mapping or NULL if no such mapping was found
     */
    public function findMapping($path) {
        if (!strEndsWith($path, '/'))
            $path .= '/';
        // $path: /
        // $path: /action/
        // $path: /controller/action/
        // $path: /controller/action/parameter/

        $pattern = $path;
        while (strlen($pattern)) {
            if (isset($this->mappings['paths'][$pattern]))          // path keys start and end with a slash "/"
                return $this->mappings['paths'][$pattern];
            $pattern = strLeftTo($pattern, '/', -2, true);
            if ($pattern == '/')
                break;
        }
        return null;
    }


    /**
     * Return the {@link ActionMapping} with the given name.
     *
     * @param  string $name
     *
     * @return ActionMapping|null - mapping or NULL if no such mapping exists
     */
    public function getMapping($name) {
        if (isset($this->mappings['names'][$name]))
            return $this->mappings['names'][$name];
        return null;
    }


    /**
     * Return the module's default {@link ActionMapping} (if configured).
     *
     * @return ActionMapping|null - instance or NULL if no default mapping is configured
     */
    public function getDefaultMapping() {
        return $this->defaultMapping;
    }


    /**
     * Process the configured import settings.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function processImports(SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $imports = $xml->xpath('/struts-config/imports/import') ?: [];

        foreach ($imports as $import) {
            $value = trim((string)$import['value']);
            $value = str_replace('/', '\\', $value);

            if (strEndsWith($value, '\\*')) {           // imported namespace
                $value = strLeft($value, -1);
                if (!$this->isValidNamespace($value)) throw new StrutsConfigException('<imports> <import value="'.$import['value'].'": Invalid value (neither a class nor a namespace).');
                if (strStartsWith($value, '\\')) $value  = substr($value, 1);
                if (!strEndsWith($value, '\\'))  $value .= '\\';
                $this->imports[strtolower($value)] = $value;
                continue;
            }

            if (is_class($value)) {                     // imported class
                if (strStartsWith($value, '\\')) $value = substr($value, 1);
                $simpleName = simpleClassName($value);
                if (isset($this->uses[$simpleName])) throw new StrutsConfigException('<imports> <import value="'.$import['value'].'": Duplicate value.');
                $this->uses[$simpleName] = $value;
                continue;
            }
            throw new StrutsConfigException('<imports> <import value="'.$import['value'].'": Invalid value (neither a class nor a namespace).');
        }
    }


    /**
     * Process the configured controller settings.
     *
     * @param  SimpleXMLElement $xml - module configuration
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function processController(SimpleXMLElement $xml) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $elements = $xml->xpath('/struts-config/controller') ?: [];

        foreach ($elements as $tag) {
            if (isset($tag['request-processor'])) {
                $name       = trim((string) $tag['request-processor']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            throw new StrutsConfigException('<controller request-processor="'.$tag['request-processor'].'": Class not found.');
                if (sizeof($classNames) > 1) throw new StrutsConfigException('<controller request-processor="'.$tag['request-processor'].'": Ambiguous class name, found "'.join('", "', $classNames).'"');
                $this->setRequestProcessorClass($classNames[0]);
            }

            if (isset($tag['role-processor'])) {
                $name       = trim((string) $tag['role-processor']);
                $classNames = $this->resolveClassName($name);
                if (!$classNames)            throw new StrutsConfigException('<controller role-processor="'.$tag['role-processor'].'": Class not found.');
                if (sizeof($classNames) > 1) throw new StrutsConfigException('<controller role-processor="'.$tag['role-processor'].'": Ambiguous class name, found "'.join('", "', $classNames).'"');
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
     * @throws StrutsConfigException on configuration errors
     */
    protected function setRequestProcessorClass($className) {
        if ($this->configured)                                            throw new IllegalStateException('Configuration is frozen');
        if (!is_subclass_of($className, DEFAULT_REQUEST_PROCESSOR_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_REQUEST_PROCESSOR_CLASS.': '.$className);
        $this->requestProcessorClass = $className;
    }


    /**
     * Return the classname of the {@link RequestProcessor} implementation used by the module.
     *
     * @return string
     */
    public function getRequestProcessorClass() {
        return $this->requestProcessorClass;
    }


    /**
     * Set the classname of the {@link RoleProcessor} implementation to be used by the module.
     * The class must be a subclass of {@link RoleProcessor}.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setRoleProcessorClass($className) {
        if ($this->configured)                                      throw new IllegalStateException('Configuration is frozen');
        if (!is_subclass_of($className, ROLE_PROCESSOR_BASE_CLASS)) throw new StrutsConfigException('Not a subclass of '.ROLE_PROCESSOR_BASE_CLASS.': '.$className);
        $this->roleProcessorClass = $className;
    }


    /**
     * Return the {@link RoleProcessor} intance of the module.
     *
     * @return RoleProcessor|null - instance or NULL if no RoleProcessor is configured for the module
     */
    public function getRoleProcessor() {
        if (!$this->roleProcessor) {
            $class = $this->roleProcessorClass;
            $class && $this->roleProcessor = new $class();
        }
        return $this->roleProcessor;
    }


    /**
     * Set the classname of the {@link Tile} implementation to be used by the module.
     * The class must be a subclass of {@link Tile}.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setTilesClass($className) {
        if ($this->configured)                        throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                    throw new StrutsConfigException('Class '.$className.' not found');
        if (!is_subclass_of($className, Tile::class)) throw new StrutsConfigException('Not a subclass of '.Tile::class.': '.$className);

        $this->tilesClass = $className;
    }


    /**
     * Return the classname of the {@link Tile} implementation used by the module.
     *
     * @return string
     */
    public function getTilesClass() {
        return $this->tilesClass;
    }


    /**
     * Set the classname of the {@link ActionMapping} implementation to be used by the module.
     * The class must be a subclass of {@link ActionMapping}.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setMappingClass($className) {
        if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                                     throw new StrutsConfigException('Class '.$className.' not found');
        if (!is_subclass_of($className, DEFAULT_ACTION_MAPPING_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_ACTION_MAPPING_CLASS.': '.$className);

        $this->mappingClass = $className;
    }


    /**
     * Return the classname of the {@link ActionMapping} implementation used by the module.
     *
     * @return string
     */
    public function getMappingClass() {
        return $this->mappingClass;
    }


    /**
     * Set the classname of the {@link ActionForward} implementation to be used by the module.
     * The class must be a subclass of {@link ActionForward}.
     *
     * @param  string $className
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function setForwardClass($className) {
        if ($this->configured)                                         throw new IllegalStateException('Configuration is frozen');
        if (!is_class($className))                                     throw new StrutsConfigException('Class '.$className.' not found');
        if (!is_subclass_of($className, DEFAULT_ACTION_FORWARD_CLASS)) throw new StrutsConfigException('Not a subclass of '.DEFAULT_ACTION_FORWARD_CLASS.': '.$className);

        $this->forwardClass = $className;
    }


    /**
     * Return the classname of the {@link ActionForward} implementation used by the module.
     *
     * @return string
     */
    public function getForwardClass() {
        return $this->forwardClass;
    }


    /**
     * Return the default view namespace used by file resources and tiles.
     *
     * @return string
     */
    public function getViewNamespace() {
        return $this->viewNamespace;
    }


    /**
     * Lock the configuration of this component. After the method returned modifications of the component will trigger
     * an exception.
     *
     * @return $this
     */
    public function freeze() {
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
     * @return ActionForward|null - instance or NULL if no such forward was found
     */
    public function findForward($name) {
        if (isset($this->globalForwards[$name]))
            return $this->globalForwards[$name];
        return null;
    }


    /**
     * Find and return the {@link Tile} with the specified name.
     *
     * @param  string $name - tile name
     *
     * @return Tile|null - instance or NULL if no such tile was found
     */
    public function findTile($name) {
        if (isset($this->tiles[$name]))
            return $this->tiles[$name];
        return null;
    }


    /**
     * Whether a file resource or a {@link Tile} exists under the specified name.
     *
     * @param  string           $name - resource name
     * @param  SimpleXMLElement $xml  - module configuration
     *
     * @return bool
     */
    private function isIncludable($name, SimpleXMLElement $xml) {
        return $this->isTileDefinition($name, $xml) || $this->isFile($name);
    }


    /**
     * Whether a {@link Tile} with the specified name is defined in the module's configuration.
     *
     * @param  string           $name - tile name
     * @param  SimpleXMLElement $xml  - module configuration
     *
     * @return bool
     *
     * @throws StrutsConfigException on configuration errors
     */
    private function isTileDefinition($name, SimpleXMLElement $xml) {
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
    private function isFile($path) {
        $filename = $this->findFile($path);
        return ($filename !== null);
    }


    /**
     * Find a file with the specified name in the module's configured resource locations, and return its full name.
     *
     * @param  string $name - relative name
     *
     * @return string|null - full filename or NULL if no such file was found
     */
    private function findFile($name) {
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
    private function isValidNamespace($value) {
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
    private function resolveClassName($name) {
        $name = str_replace('/', '\\', trim($name));

        // no need to resolve a qualified name
        if (strContains($name, '\\'))
            return is_class($name) ? [$name] : [];

        // unqualified name, check "use" declarations
        $lowerName = strtolower($name);
        if (isset($this->uses[$lowerName]))
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
