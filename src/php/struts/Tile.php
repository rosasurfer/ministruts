<?
/**
 * Tile
 */
class Tile extends Object {


   /**
    * Typenbezeichner für in einzelne Tiles mit dem <set>-Tag eingebundene, zusätzliche Eigenschaften.
    */
   const PROP_TYPE_STRING   = 'string';
   const PROP_TYPE_RESOURCE = 'resource';


   /**
    * Ob diese Komponente vollständig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder
    * Versuch, die Komponente zu ändern, eine IllegalStateException.
    */
   protected $configured = false;


   /**
    * Module, zu dem diese Tile gehört
    */
   protected /*Module*/ $module;


   protected $name;           // string
   protected $path;           // string

   // Property-Pool
   protected $properties = array();


   /**
    * Constructor
    *
    * @param Module $module - Module, zu dem diese Tile gehört
    */
   public function __construct(Module $module) {
      $this->module = $module;
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
    * @param string $name
    *
    * @return Tile
    */
   public function setName($name) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));

      $this->name = $name;
      return $this;
   }


   /**
    * Gibt den Pfad dieser Tile zurück.
    *
    * @return string $path
    */
   public function getPath() {
      return $this->path;
   }


   /**
    * Setzt den Pfad dieser Tile.
    *
    * @param string $path
    *
    * @return Tile
    */
   public function setPath($path) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($path)) throw new IllegalTypeException('Illegal type of argument $path: '.getType($path));

      $this->path = $path;
      return $this;
   }


   /**
    * Speichert in der Tile unter dem angegebenen Namen eine zusätzliche Eigenschaft.
    *
    * @param string $name  - Name der Eigenschaft
    * @param string $type  - Typ der Eigenschaft (string|resource)
    * @param string $value - der zu speichernde Wert
    */
   public function setProperty($name, $type, $value) {
      if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name))  throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));
      if (!is_string($value)) throw new IllegalTypeException('Illegal type of argument $value: '.getType($value));

      if ($type!==self ::PROP_TYPE_STRING && $type!==self ::PROP_TYPE_RESOURCE)
         throw new InvalidArgumentException('Invalid argument $type: '.$type);

      $this->properties[$name] = array($type, $value);
      // TODO: valid types -> string, page or definition
   }


   /**
    * Initialisiert die für diese Tile definierten zusätzlichen Eigenschaften (siehe <set>-Tag in
    * struts-config.xml).  Dabei werden Bezeichner für in der Tile enthaltene Seiten oder weitere
    * Tilesdefinitionen durch ihre jeweiligen Instanzen ersetzt.
    */
   private function initProperties() {
      foreach($this->properties as &$property) {
         if (sizeOf($property) == 1)
            return;                          // Tile-Properties wurden schon initialisiert

         $type  = $property[0];
         $value = $property[1];

         if ($type == self ::PROP_TYPE_STRING) {         // String-Value
            $property = $value;
         }
         elseif ($type == self ::PROP_TYPE_RESOURCE) {   // Page oder Tilesdefinition
            $tile = $this->module->findTile($value);

            if (!$tile) {     // it's a page, create a simple one on the fly
               $class = $this->module->getTilesClass();
               $tile = new $class($this->module);
               $tile->setName('generic')
                    ->setPath($value)
                    ->freeze();
            }
            $property = $tile;
         }
      }
   }


   /**
    * Friert die Konfiguration dieser Komponente ein.
    *
    * @return Tile
    */
   public function freeze() {
      if (!$this->configured) {
         if (!$this->name) throw new IllegalStateException('No name configured for this '.$this);
         if (!$this->path) throw new IllegalStateException('No path configured for '.__CLASS__.' "'.$this->name.'"');

         $this->configured = true;
      }
      return $this;
   }


   /**
    * Zeigt den Inhalt dieser Komponente an.
    */
   public function render() {
      $this->initProperties();

      $request  = Request  ::me();
      $response = Response ::me();
      $form     = $request->getAttribute(Struts ::ACTION_FORM_KEY);

      extract($this->properties);

      // TODO: Framework vor Zugriff aus der HTML-Seite mit $this schützen
      include($this->module->getResourceBase().$this->path);
   }
}
?>
