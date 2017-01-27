<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;


/**
 * Tile
 */
class Tile extends Object {


   /**
    * Typenbezeichner für in einzelne Tiles mit dem <set>-Tag eingebundene, zusätzliche Eigenschaften.
    */
   const PROPERTY_TYPE_STRING   = 'string';
   const PROPERTY_TYPE_RESOURCE = 'resource';


   /**
    * Ob diese Komponente vollständig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder
    * Versuch, die Komponente zu ändern, eine IllegalStateException.
    */
   protected $configured = false;


   /**
    * Module, zu dem diese Tile gehört
    */
   protected /*Module*/ $module;


   /**
    * eindeutige Name dieser Tile
    */
   protected /*string*/ $name;


   /**
    * vollständiger Dateiname dieser Tile
    */
   protected /*string*/ $fileName;


   /**
    * Label dieser Tile (für Kommentare etc.)
    */
   protected /*string*/ $label;


   // Property-Pool
   protected $properties = array();


   /**
    * Die zur Laufzeit diese Tile-Instanz umgebende Instanz oder NULL, wenn diese Instanz das äußerste
    * Fragment der Ausgabe darstellt.
    */
   protected /*Tile*/ $parent;


   /**
    * Constructor
    *
    * @param  Module $module - Module, zu dem diese Tile gehört
    * @param  Tile   $parent - (Parent-)Instanz der neuen (verschachtelten) Instanz
    */
   public function __construct(Module $module, Tile $parent=null) {
      $this->module = $module;
      $this->parent = $parent;
   }


   /**
    * Gibt den Namen dieser Tile zurück.
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
    * @return Tile
    */
   public function setName($name) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));

      $this->name = $name;
      return $this;
   }


   /**
    * Gibt den Pfad dieser Tile zurück.
    *
    * @return string
    */
   public function getFileName() {
      return $this->fileName;
   }


   /**
    * Setzt den Dateinamen dieser Tile.
    *
    * @param  string $filename - vollständiger Dateiname
    * @param  string $label    - Label für diese Tile (für Kommentare im HTML etc.)
    *
    * @return Tile
    */
   public function setFileName($filename) {
      if ($this->configured)     throw new IllegalStateException('Configuration is frozen');
      if (!is_string($filename)) throw new IllegalTypeException('Illegal type of parameter $filename: '.getType($filename));

      $this->fileName = $filename;
      return $this;
   }


   /**
    * Setzt das Label dieser Tile. Das Label wird in HTML-Kommentaren etc. verwendet.
    *
    * @param  string $label - Label
    *
    * @return Tile
    */
   public function setLabel($label) {
      if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($label)) throw new IllegalTypeException('Illegal type of parameter $label: '.getType($label));

      $this->label = $label;
      return $this;
   }


   /**
    * Speichert in der Tile unter dem angegebenen Namen eine zusätzliche Eigenschaft.
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
    * @return Tile
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
    * Gibt die eigenen und die geerbten Properties dieser Tile zurück. Eigene Properties überschreiben geerbte Properties mit demselben Namen.
    *
    * @return array - Properties
    */
   protected function getMergedProperties() {
      if ($this->parent)
         return array_merge($this->parent->getMergedProperties(), $this->properties);

      return $this->properties;
   }


   /**
    * Gibt den Inhalt dieser Tile aus.
    */
   public function render() {
      // alle Properties holen und im Context dieser Methode ablegen
      $properties = $this->getMergedProperties();
      $request = Request::me();

      $properties['request' ] = $request;
      $properties['response'] = Response::me();
      $properties['session' ] = $request->isSession() ? $request->getSession() : null;
      $properties['form'    ] = $request->getAttribute(ACTION_FORM_KEY);
      $properties['PAGE'    ] = PageContext::me();

      echo ($this->parent ? "\n<!-- #begin: ".$this->label." -->\n" : null);
      includeFile($this->fileName, $properties);
      echo ($this->parent ? "\n<!-- #end: ".  $this->label." -->\n" : null);
   }
}


/**
 * Populate the function context with the passed properties and include the specified file. Prevents the view from accessing
 * the Tile instance (variable $this is not available).
 *
 * @param  string $fileName
 * @param  array  $properties - context properties accessible to the view
 */
function includeFile($fileName, array $properties) {
   extract($properties);
   include($fileName);
}