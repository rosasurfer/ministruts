<?php
namespace rosasurfer\ministruts;

use rosasurfer\config\Config;
use rosasurfer\core\Object;
use rosasurfer\exception\IllegalStateException;

use function rosasurfer\strRightFrom;

use const rosasurfer\LOCALHOST;


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
     * @param  Module    $module - Module, zu dem diese Tile gehoert
     * @param  Tile|null $parent - (Parent-)Instanz der neuen (verschachtelten) Instanz
     */
    public function __construct(Module $module, Tile $parent=null) {
        $this->module = $module;
        $this->parent = $parent;
    }


    /**
     * Gibt den Namen dieser Tile zurueck.
     *
     * @return string $name
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
     * Speichert in der Tile unter dem angegebenen Namen eine Child-Tile.
     *
     * @param  string    $name - Name der Tile
     * @param  self|null $tile - die zu speichernde Tile oder NULL, wenn die Child-Deklaration abstrakt ist
     *
     * @return $this
     */
    public function setNestedTile($name, self $tile=null) {
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
        return array_merge($parentProperties, $this->properties);
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
     */
    public function freeze() {
        if (!$this->configured) {
            if (!$this->name)     throw new IllegalStateException('No name configured for this '.$this);
            if (!$this->fileName) throw new IllegalStateException('No file configured for tile "'.$this->name.'"');

            foreach ($this->nestedTiles as $tile) {
                $tile && $tile->freeze();
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
        $nestedTiles = $this->nestedTiles;
        $properties  = $this->getMergedProperties();
        $request     = Request::me();

        $properties['request' ] = $request;
        $properties['response'] = Response::me();
        $properties['session' ] = $request->isSession() ? $request->getSession() : null;
        $properties['form'    ] = $request->getAttribute(ACTION_FORM_KEY);
        $properties['page'    ] = PageContext::me();

        if (LOCALHOST && $this->parent) {
            $rootDir = Config::getDefault()->get('app.dir.root');
            $file    = $this->fileName;
            $file    = strRightFrom($file, $rootDir.DIRECTORY_SEPARATOR, 1, false, $file);
            $file    = str_replace('\\', '/', $file);

            if ($this->name == self::GENERIC_NAME) $tileHint = $file;
            else                                   $tileHint = $this->name.' ('.$file.')';
            echo "\n<!-- #begin: ".$tileHint." -->\n";
        } else                                     $tileHint = null;

        includeFile($this->fileName, $nestedTiles + $properties);

        if (LOCALHOST && $this->parent) {
            echo "\n<!-- #end: ".$tileHint." -->\n";
        }

        return $this;
    }
}


/**
 * Populate the function context with the passed properties and include the specified file. Prevents the view from accessing
 * the Tile instance (variable $this is not available).
 *
 * @param  string $___        - name of the file to include (somewhat obfuscated)
 * @param  array  $properties - properties accessible to the view
 */
function includeFile($___, array $properties) {
    extract($properties);
    unset($properties);
    include($___);
}
