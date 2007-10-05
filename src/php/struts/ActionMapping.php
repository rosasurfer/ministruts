<?
/**
 * ActionMapping
 */
class ActionMapping extends Object {


   private $path;                   // string
   private $action;                 // string
   private $form;                   // string
   private $default;                // boolean


   // Array mit den lokalen ActionForwards
   private $forwards = array();

   // ob diese Komponente vollständig konfiguriert ist
   private $configured = false;     // boolean


   // new ActionMapping()->
   public static function create() {
      return new self();
   }


   /**
    * Setzt den Pfad dieses Mappings.
    *
    * @param string $path
    *
    * @return ActionMapping
    */
   public function setPath($path) {
      if ($this->configured)                throw new IllegalStateException('Configuration is frozen');
      if (!is_string($path))                throw new IllegalTypeException('Illegal type of argument $path: '.getType($path));
      if (!String ::startsWith($path, '/')) throw new InvalidArgumentException('The path property of an '.__CLASS__.' must begin with a slash "/", found: "'.$path.'"');

      $this->path = $path;
      return $this;
   }


   public function getPath() {
      return $this->path;
   }


   /**
    * Setzt den Klassennamen der auszuführenden Action.
    *
    * @param string $className
    *
    * @return ActionMapping
    */
   public function setAction($className) {
      if ($this->configured)                         throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                    throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, 'BaseAction')) throw new InvalidArgumentException('Not a subclass of BaseAction: '.$className);

      $this->action = $className;
      return $this;
   }


   public function getAction() {
      return $this->action;
   }


   /**
    * Setzt den Klassennamen der zur Action gehörenden ActionForm.
    *
    * @param string $className
    *
    * @return ActionMapping
    */
   public function setForm($className) {
      if ($this->configured)                                 throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                            throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, 'BaseActionForm')) throw new InvalidArgumentException('Not an BaseActionForm subclass: '.$className);

      $this->form = $className;
      return $this;
   }


   public function getForm() {
      return $this->form;
   }


   /**
    * Setzt das Default-Flag für dieses ActionMapping. Requests, die keinem anderen Mapping zugeordnet werden können,
    * werden von dem Mapping mit gesetztem Default-Flag verarbeitet. Nur ein Mapping innerhalb eines Modules kann
    * dieses Flag gesetzt werden.
    *
    * @param boolean $default
    *
    * @return ActionMapping
    */
   public function setDefault($default) {
      if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
      if (!is_bool($default)) throw new IllegalTypeException('Illegal type of argument $default: '.getType($default));

      $this->default = $default;
      return $this;
   }


   /**
    * Ob für dieses ActionMapping das Default-Flag gesetzt ist.
    *
    * @return boolean
    *
    * @see setDefault()
    */
   public function isDefault() {
      return ($this->default);
   }


   /**
    * Fügt dem ActionMapping einen ActionForward hinzu.
    *
    * @param ActionForward $forward
    *
    * @return ActionMapping
    */
   public function addForward(ActionForward $forward) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');

      $this->forwards[$forward->getName()] = $forward;
      return $this;
   }


   /**
    * Friert die Konfiguration dieser Komponente ein.
    */
   public function freeze() {
      $this->configured = true;

      foreach ($this->forwards as $forward)
         $forward->freeze();
   }
}
?>
