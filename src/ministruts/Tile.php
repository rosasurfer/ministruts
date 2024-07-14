<?php
namespace rosasurfer\ministruts;

use rosasurfer\Application;
use rosasurfer\config\Config;
use rosasurfer\core\Object;
use rosasurfer\exception\IllegalStateException;

use function rosasurfer\strLeft;
use function rosasurfer\strRightFrom;

use const rosasurfer\NL;


/**
 * Tile
 */
class Tile extends Object {


    /** @var string - runtime generated name for anonymous tiles */
    const GENERIC_NAME = 'generic';                     // TODO: make generic names unique

    /** @var Module - Module, zu dem diese Tile gehoert */
    protected $module;

    /** @var string - eindeutiger Name dieser Tile */
    protected $name;

    /** @var string - vollstaendiger Dateiname dieser Tile */
    protected $fileName;

    /** @var bool - whether or not MVC push model is activated for the tile */
    protected $pushModelSupport;

    /** @var Tile[] - nested tiles */
    protected $nestedTiles = [];

    /** @var array - additional tile properties */
    protected $properties = [];

    /**
     * @var Tile|null - Die zur Laufzeit diese Tile-Instanz umgebende Instanz oder NULL, wenn diese Instanz das aeusserste
     *                  Fragment der Ausgabe darstellt.
     */
    protected $parent;

    /** @var bool - Ob diese Komponente noch modifiziert werden kann oder bereits vollstaendig konfiguriert ist. */
    protected $configured = false;


    /**
     * Constructor
     *
     * @param  Module $module            - Module, zu dem diese Tile gehoert
     * @param  Tile   $parent [optional] - die umgebende Instanz der Tile
     */
    public function __construct(Module $module, Tile $parent = null) {
        $this->module = $module;
        $this->parent = $parent;
    }


    /**
     * Gibt den Namen dieser Tile zurueck.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * Setzt den Namen dieser Tile.
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
     * Set the parent of this Tile. Called before rendering as a tile can have different parents if re-used in multiple
     * places.
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
     * Gibt den Pfad dieser Tile zurueck.
     *
     * @return string
     */
    public function getFileName() {
        return $this->fileName;
    }


    /**
     * Setzt den Dateinamen dieser Tile.
     *
     * @param  string $filename - vollstaendiger Dateiname
     *
     * @return $this
     */
    public function setFileName($filename) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->fileName = $filename;
        return $this;
    }


    /**
     * Whether or not the MVC push model is activated for the tile.
     *
     * @return bool|null - configured state or NULL if the state is inherited from a surrounding element
     */
    public function isPushModelSupport() {
        return $this->pushModelSupport;
    }


    /**
     * Enable/disable push model support for the tile.
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
     * Speichert in der Tile unter dem angegebenen Namen eine Child-Tile.
     *
     * @param  string $name            - Name der Tile
     * @param  Tile   $tile [optional] - die zu speichernde Tile oder NULL, wenn die Child-Deklaration abstrakt ist
     *
     * @return $this
     */
    public function setNestedTile($name, Tile $tile = null) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->nestedTiles[$name] = $tile;
        return $this;
    }


    /**
     * Speichert in der Tile unter dem angegebenen Namen eine zusaetzliche Eigenschaft.
     *
     * @param  string $name  - Name der Eigenschaft
     * @param  mixed  $value - der zu speichernde Wert
     *
     * @return $this
     */
    public function setProperty($name, $value) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->properties[$name] = $value;
        return $this;
    }


    /**
     * Gibt die eigenen und die Properties der umgebenden Tile zurueck. Eigene Properties ueberschreiben gleichnamige
     * Properties der umgebenden Tile.
     *
     * @return array - Properties
     */
    protected function getMergedProperties() {
        $parentProperties = $this->parent ? $this->parent->getMergedProperties() : [];
        return \array_merge($parentProperties, $this->properties);
    }


    /**
     * Whether or not this instance must be extended and can't be inserted into a view directly.
     *
     * @return bool
     */
    public function isAbstract() {
        return in_array(null, $this->nestedTiles, true);
    }


    /**
     * Friert die Konfiguration dieser Komponente ein.
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
        $request     = Request::me();
        $namespace   = $this->module->getViewNamespace();
        $appUri      = $request->getApplicationBaseUri();
        $nestedTiles = $this->nestedTiles;
        foreach ($nestedTiles as $tile) {
            $tile->setParent($this);
        }
        $properties  = $this->getMergedProperties();

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
            $rootDir  = Config::getDefault()->get('app.dir.root');
            $file     = $this->fileName;
            $file     = strRightFrom($file, $rootDir.DIRECTORY_SEPARATOR, 1, false, $file);
            $file     = 'file="'.str_replace('\\', '/', $file).'"';
            $tile     = $this->name==self::GENERIC_NAME ? '':'tile="'.$this->name.'" ';
            $tileHint = $tile.$file;
            echo ($this->parent ? NL:'').'<!-- #begin: '.$tileHint.' -->'.NL;
        }

        includeFile($this->fileName, $nestedTiles + $properties);

        if ($tileHint) {
            echo NL.'<!-- #end: '.$tileHint.' -->'.NL;
        }
        return $this;
    }
}


/**
 * Populate the function context with the passed properties and include the specified file. Prevents the view from accessing
 * the Tile instance (var $this is not available).
 *
 * @param  string $file   - name of the file to include
 * @param  array  $values - values accessible to the view
 */
function includeFile(/*$file, $values*/) {
    foreach (func_get_args()[1] as $__key__ => $__value__) {    // We can't use extract() as it skips variables with
        $$__key__ = $__value__;                                 // irregular names (e.g. with dots).
    }                                                           // Surprisingly foreach is even faster.
    unset($__key__, $__value__);

    include(func_get_args()[0]);
}
