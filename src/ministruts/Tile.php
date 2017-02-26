<?php
namespace rosasurfer\ministruts;

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

    /** @var array - Property-Pool */
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
     * @return self
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
     * @return self
     */
    public function setFileName($filename) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->fileName = $filename;
        return $this;
    }


    /**
     * Speichert in der Tile unter dem angegebenen Namen eine Child-Tile.
     *
     * @param  string    $name  - Name der Tile
     * @param  self|null $child - die zu speichernde Tile oder NULL, wenn die Child-Deklaration abstrakt ist
     */
    public function setNestedTile($name, self $tile=null) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $this->nestedTiles[$name] = $tile;
    }


    /**
     * Speichert in der Tile unter dem angegebenen Namen eine zusaetzliche Eigenschaft.
     *
     * @param  string $name  - Name der Eigenschaft
     * @param  mixed  $value - der zu speichernde Wert
     */
    public function setProperty($name, $value) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->properties[$name] = $value;
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
     * @return self
     */
    public function freeze() {
        if (!$this->configured) {
            if (!$this->name)     throw new IllegalStateException('No name configured for this '.$this);
            if (!$this->fileName) throw new IllegalStateException('No file configured for '.get_class().' "'.$this->name.'"');

            foreach ($this->nestedTiles as $tile) {
                $tile && $tile->freeze();
            }
            $this->configured = true;
        }
        return $this;
    }


    /**
     * Gibt die eigenen und die geerbten Properties dieser Tile zurueck. Eigene Properties ueberschreiben geerbte Properties
     * mit demselben Namen.
     *
     * @return array - Properties
     */
    protected function getMergedProperties() {
        if ($this->parent) {
            return array_merge($this->parent->getMergedProperties(), $this->properties);
        }
        return $this->properties;
    }


    /**
     * Render the Tile.
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
            $file = $this->fileName;
            $file = strRightFrom($file, APPLICATION_ROOT.DIRECTORY_SEPARATOR, 1, false, $file);
            $file = str_replace('\\', '/', $file);

            if ($this->name == self::GENERIC_NAME) $tileHint = $file;
            else                                   $tileHint = $this->name.' ('.$file.')';
            echo "\n<!-- #begin: ".$tileHint." -->\n";
        }
        includeFile($this->fileName, $nestedTiles + $properties);

        if (LOCALHOST && $this->parent) {
            echo "\n<!-- #end: ".$tileHint." -->\n";
        }
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
