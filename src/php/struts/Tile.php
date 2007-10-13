<?
/**
 * Tile
 */
class Tile extends Object {


   /**
    * Ob diese Komponente vollständig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder Versuch,
    * die Komponente zu ändern, eine IllegalStateException.
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
    * Speichert unter dem angegebenen Namen eine Property in der Tile.
    *
    * @param string $name  - Name der Property
    * @param mixed  $value - der zu speichernde Wert
    */
   public function setProperty($name, $value) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));

      $this->properties[$name] = $value;
   }


   /**
    * Friert die Konfiguration dieser Komponente ein.
    */
   public function freeze() {
      if (!$this->configured) {
         if (!$this->name)
            throw new IllegalStateException('No name configured for this '.$this);
         if (!$this->path)
            throw new IllegalStateException('No path configured for '.__CLASS__.' "'.$this->name.'"');

         $this->configured = true;
      }
   }


   /**
    * Gibt den Content dieser Tiles-Definition aus.
    *
    * @param Request  $request
    * @param Response $response
    */
   public function render(Request $request, Response $response) {
      $appPath = $request->getAttribute(Struts ::APPLICATION_PATH_KEY);
      $form    = $request->getAttribute(Struts ::ACTION_FORM_KEY);

      $resourceBase = $this->module->getResourceBase();

      foreach ($this->properties as $key => $value) {
         ${$key} = $resourceBase.$value;
      }
      include($resourceBase.$this->path);
   }
}
?>
