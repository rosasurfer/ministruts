<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;

use function rosasurfer\strRightFrom;

use const rosasurfer\LOCALHOST;


/**
 * Tile
 */
class Tile extends Object {


   /**
    * Typenbezeichner fuer in einzelne Tiles mit dem <set>-Tag eingebundene, zusaetzliche Eigenschaften.
    */
   const PROPERTY_TYPE_STRING   = 'string';
   const PROPERTY_TYPE_RESOURCE = 'resource';


   const GENERIC_NAME = 'generic';


   /**
    * Ob diese Komponente vollstaendig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder
    * Versuch, die Komponente zu aendern, eine IllegalStateException.
    */
   protected $configured = false;


   /**
    * Module, zu dem diese Tile gehoert
    */
   protected /*Module*/ $module;


   /**
    * eindeutige Name dieser Tile
    */
   protected /*string*/ $name;


   /**
    * vollstaendiger Dateiname dieser Tile
    */
   protected /*string*/ $fileName;


   // Property-Pool
   protected $properties = [];


   /**
    * Die zur Laufzeit diese Tile-Instanz umgebende Instanz oder NULL, wenn diese Instanz das aeusserste
    * Fragment der Ausgabe darstellt.
    */
   protected /*Tile*/ $parent;


   /**
    * Constructor
    *
    * @param  Module $module - Module, zu dem diese Tile gehoert
    * @param  Tile   $parent - (Parent-)Instanz der neuen (verschachtelten) Instanz
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
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));

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
      if ($this->configured)     throw new IllegalStateException('Configuration is frozen');
      if (!is_string($filename)) throw new IllegalTypeException('Illegal type of parameter $filename: '.getType($filename));

      $this->fileName = $filename;
      return $this;
   }


   /**
    * Speichert in der Tile unter dem angegebenen Namen eine zusaetzliche Eigenschaft.
    *
    * @param  string $name  - Name der Eigenschaft
    * @param  mixed  $value - der zu speichernde Wert (String oder Tile)
    */
   public function setProperty($name, $value) {
      if ($this->configured)                             throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name))                             throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
      if (!$value instanceof self && !is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

      $this->properties[$name] = $value;
      // TODO: valid types -> string, page or tile
   }


   /**
    * Friert die Konfiguration dieser Komponente ein.
    *
    * @return self
    */
   public function freeze() {
      if (!$this->configured) {
         if (!$this->name)     throw new IllegalStateException('No name configured for this '.$this);
         if (!$this->fileName) throw new IllegalStateException('No file configured for '.get_class($this).' "'.$this->name.'"');

         foreach ($this->properties as $property) {
            if ($property instanceof self)
               $property->freeze();
         }

         $this->configured = true;
      }
      return $this;
   }


   /**
    * Gibt die eigenen und die geerbten Properties dieser Tile zurueck. Eigene Properties ueberschreiben geerbte Properties mit demselben Namen.
    *
    * @return array - Properties
    */
   protected function getMergedProperties() {
      if ($this->parent)
         return array_merge($this->parent->getMergedProperties(), $this->properties);

      return $this->properties;
   }


   /**
    * Render the Tile.
    */
   public function render() {
      $properties = $this->getMergedProperties();
      $request = Request::me();

      $properties['request' ] = $request;
      $properties['response'] = Response::me();
      $properties['session' ] = $request->isSession() ? $request->getSession() : null;
      $properties['form'    ] = $request->getAttribute(ACTION_FORM_KEY);
      $properties['PAGE'    ] = PageContext::me();

      if (LOCALHOST && $this->parent) {
         $file = $this->fileName;
         $file = strRightFrom($file, APPLICATION_ROOT.DIRECTORY_SEPARATOR, 1, false, $file);
         $file = str_replace('\\', '/', $file);

         if ($this->name == self::GENERIC_NAME) $tileHint = $file;
         else                                   $tileHint = $this->name.' ('.$file.')';
         echo "\n<!-- #begin: ".$tileHint." -->\n";
      }

      includeFile($this->fileName, $properties);

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
