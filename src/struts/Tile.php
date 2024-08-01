<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\Application;
use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\di\proxy\Request as Request;
use rosasurfer\ministruts\core\exception\IllegalStateException;

use function rosasurfer\ministruts\strLeft;
use function rosasurfer\ministruts\strRightFrom;

use const rosasurfer\ministruts\NL;


/**
 * Tile
 *
 * A Tile describes a visual view fragment of the generated HTTP response. Like a tile at the wall the whole picture (view)
 * is a composition of multiple arranged tiles.
 *
 * Tiles can be nested, each nested component is represented by another Tile instance.
 *
 * Tiles can be used to define layouts (a.k.a. templates) consisting of multiple arranged tiles. A layout can be
 * extended. In an extended layout a single tile (view component) may be swapped by different view content at runtime.
 */
class Tile extends CObject {


    /**
     * @var string - runtime generated name for anonymous tiles
     *
     * @todo  make generic names unique
     */
    const GENERIC_NAME = 'generic';

    /** @var Module - the Module this Tile belongs to */
    protected $module;

    /** @var string - unique name of the Tile */
    protected $name;

    /** @var string - full filename of the Tile */
    protected $fileName;

    /** @var ?bool - whether the MVC push model is enabled for the Tile */
    protected $pushModelSupport = null;

    /** @var array<string, ?Tile> - nested Tiles */
    protected $nestedTiles = [];

    /** @var mixed[] - additional Tile properties */
    protected $properties = [];

    /** @var ?Tile - parent instance containing this Tile or NULL if this Tile is the outermost fragment of the generated view */
    protected $parent;

    /** @var bool - whether this component can still be modified or configuration is frozen */
    protected $configured = false;


    /**
     * Constructor
     *
     * @param  Module $module            - the Module the Tile belongs to
     * @param  ?Tile  $parent [optional] - parent instance of the Tile
     */
    public function __construct(Module $module, Tile $parent = null) {
        $this->module = $module;
        $this->parent = $parent;
    }


    /**
     * Return the name of the Tile.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * Set the name of the Tile
     *
     * @param  string $name
     *
     * @return $this
     */
    public function setName($name) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->name = $name;
        return $this;
    }


    /**
     * Set the parent of the Tile.
     *
     * @param  self $parent
     *
     * @return $this
     */
    public function setParent(self $parent) {
        $this->parent = $parent;
        return $this;
    }


    /**
     * Return the full filename of the Tile.
     *
     * @return string
     */
    public function getFileName() {
        return $this->fileName;
    }


    /**
     * Set the full filename of the Tile.
     *
     * @param  string $filename
     *
     * @return $this
     */
    public function setFileName($filename) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->fileName = $filename;
        return $this;
    }


    /**
     * Whether the MVC push model is enabled for the Tile.
     *
     * @return ?bool - configured state or NULL if the state is inherited from a surrounding element
     */
    public function isPushModelSupport() {
        return $this->pushModelSupport;
    }


    /**
     * Enable/disable push model support for the Tile.
     *
     * @param  bool $state
     *
     * @return $this
     */
    public function setPushModelSupport($state) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->pushModelSupport = (bool) $state;

        foreach ($this->nestedTiles as $tile) {
            if ($tile && $tile->isPushModelSupport()===null) {
                $tile->setPushModelSupport($state);
            }
        }
        return $this;
    }


    /**
     * Store a child Tile under the specified name.
     *
     * @param  string $name            - name of the Tile
     * @param  ?Tile  $tile [optional] - Tile instance or NULL if the child declaration is abstract
     *
     * @return $this
     */
    public function setNestedTile($name, Tile $tile = null) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->nestedTiles[$name] = $tile;
        return $this;
    }


    /**
     * Store an additional property under the specified name.
     *
     * @param  string $name  - property name
     * @param  mixed  $value - property value (any value)
     *
     * @return $this
     */
    public function setProperty($name, $value) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->properties[$name] = $value;
        return $this;
    }


    /**
     * Return all properties of the Tile itself and the properties of the surrounding Tile.
     * The Tile's own properties overwrite properties of the same name in the surrounding Tile.
     *
     * @return mixed[] - properties
     */
    protected function getMergedProperties() {
        $parentProperties = $this->parent ? $this->parent->getMergedProperties() : [];
        return \array_merge($parentProperties, $this->properties);
    }


    /**
     * Whether the instance must be extended before it can be inserted into a view.
     *
     * @return bool
     */
    public function isAbstract() {
        return in_array(null, $this->nestedTiles, true);
    }


    /**
     * Freeze the configuration of this component. After the call the instance can't be modified anymore.
     *
     * @return $this
     *
     * @throws StrutsConfigException on configuration errors
     */
    public function freeze() {
        if (!$this->configured) {
            if (!$this->fileName) throw new StrutsConfigException('<tile name="'.$this->name.'": No file configured.');

            foreach ($this->nestedTiles as $tile) {
                if ($tile) $tile->freeze();
            }
            $this->configured = true;
        }
        return $this;
    }


    /**
     * Render the Tile.
     *
     * @return $this
     */
    public function render() {
        $request     = Request::instance();
        $namespace   = $this->module->getViewNamespace();
        $appUri      = $request->getApplicationBaseUri();
        $nestedTiles = $this->nestedTiles;
        foreach ($nestedTiles as $tile) {
            $tile->setParent($this);
        }
        $properties = $this->getMergedProperties();

        if (!defined($namespace.'APP')) {
            define($namespace.'APP', strLeft($appUri, -1));
        }
        if (!defined($namespace.'MODULE')) {
            $moduleUri = $appUri.$this->module->getPrefix();
            define($namespace.'MODULE', strLeft($moduleUri, -1));
        }

        $properties['request' ] = $request;
        $properties['response'] = Response::me();
        $properties['session' ] = $request->isSession() ? $request->getSession() : null;
        $properties['form'    ] = $request->getAttribute(ACTION_FORM_KEY);
        $properties['page'    ] = Page::me();

        if ($this->isPushModelSupport()) {
            $pageValues = Page::me()->values();
            $properties = \array_merge($properties, $pageValues);
        }

        $tileHint = false;
        if (Application::isAdminIP()) {
            $rootDir  = $this->di('config')['app.dir.root'];
            $file     = $this->fileName;
            $file     = strRightFrom($file, $rootDir.DIRECTORY_SEPARATOR, 1, false, $file);
            $file     = 'file="'.str_replace('\\', '/', $file).'"';
            $tile     = $this->name==self::GENERIC_NAME ? '':'tile="'.$this->name.'" ';
            $tileHint = $tile.$file;
            echo ($this->parent ? NL:'').'<!-- #begin: '.$tileHint.' -->'.NL;
        }

        $this->includeFile($this->fileName, $nestedTiles + $properties);

        if ($tileHint) {
            echo NL.'<!-- #end: '.$tileHint.' -->'.NL;
        }
        return $this;
    }


    /**
     * Include the specified file in an scope isolated way (no access to $this/self),
     * and populate it with the passed properties.
     *
     * @param  string  $file       - name of the view file to include
     * @param  mixed[] $properties - property values accessible to the view
     *
     * @return void
     */
    protected function includeFile($file, array $properties) {
        static $includeFile = null;
        if (!$includeFile) {
            // define scope isolated Closure
            $includeFile = \Closure::bind(static function() {
                foreach (func_get_args()[1] as $__name13ae1dbf8af83a86 => $__value13ae1dbf8af83a86) {
                    $$__name13ae1dbf8af83a86 = $__value13ae1dbf8af83a86;        // We can't use extract() as it skips variables with
                }                                                               // irregular names (e.g. with dots).
                unset($__name13ae1dbf8af83a86, $__value13ae1dbf8af83a86);       // Surprisingly foreach() is even faster.

                include(func_get_args()[0]);
            }, null, null);
        }

        // include the file
        $includeFile($file, $properties);
    }
}
