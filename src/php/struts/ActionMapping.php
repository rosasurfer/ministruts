<?
/**
 * ActionMapping
 */
class ActionMapping extends Object {


   protected $path;                   // string
   protected $forward;                // string (der direkt konfigurierte ActionForward, wenn angegeben)
   protected $action;                 // string
   protected $form;                   // string
   protected $default;                // boolean


   // Array mit den lokalen ActionForwards
   protected $forwards = array();

   // ob diese Komponente vollständig konfiguriert ist
   protected $configured = false;     // boolean

   // ModuleConfig, zu der wir gehören
   protected $moduleConfig;


   /**
    * Constructor
    *
    * @param ModuleConfig $config - ModuleConfig, zu der dieses Mapping gehört
    */
   public function __construct(ModuleConfig $config) {
      $this->moduleConfig = $config;
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


   /**
    * Gibt den Pfad dieses Mappings zurück.
    *
    * @return string
    */
   public function getPath() {
      return $this->path;
   }


   /**
    * Setzt den Namen des direkt konfigurierten Forwards.
    *
    * @param string $name
    *
    * @return ActionMapping
    */
   public function setForward($name) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));
      if ($this->action)     throw new RuntimeException('Configuration error: Exactly one of action "forward" or action "type" must be specified.');

      $this->forward = $name;
      return $this;
   }


   /**
    * Gibt den Namen des internen Forwards oder NULL, wenn keiner konfiguriert wurde, zurück.
    *
    * @return string
    */
   public function getForward() {
      return $this->forward;
   }


   /**
    * Setzt den Klassennamen der auszuführenden Action.
    *
    * @param string $className
    *
    * @return ActionMapping
    */
   public function setAction($className) {
      if ($this->configured)                             throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                        throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, $parent='Action')) throw new InvalidArgumentException("Not a subclass of $parent: ".$className);
      if ($this->forward)                                throw new RuntimeException('Configuration error: Exactly one of action "forward" or action "type" must be specified.');

      $this->action = $className;
      return $this;
   }


   /**
    * Gibt den Klassennamen der auszuführenden Action oder NULL, wenn keine Action konfiguriert wurde, zurück.
    *
    * @return string - Klassenname
    */
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
      if (!is_subclass_of($className, $parent='ActionForm')) throw new InvalidArgumentException("Not a subclass of $parent: ".$className);

      $this->form = $className;
      return $this;
   }


   /**
    * Gibt den Klassennamen der ActionForm oder NULL, wenn keine ActionForm konfiguriert wurde, zurück.
    *
    * @return string - Klassenname
    */
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


   /**
    * Sucht und gibt den ActionForward mit dem angegebenen Namen zurück. Zuerst werden die lokalen
    * Forwards des Mappings durchsucht und danach, wenn kein Forward gefunden wurde, die globalen
    * Forwards der Modulkonfiguration. Wird kein Forward gefunden, wird NULL zurückgegeben.
    *
    * @param $name - logischer Name des ActionForwards
    *
    * @return ActionForward
    */
   public function findForward($name) {
      if (isSet($this->forwards[$name]))
         return $this->forwards[$name];

      $forward = $this->moduleConfig->findForward($name);
      if (!$forward)
         Logger ::log('No ActionForward found for name: '.$name, L_WARN, __CLASS__);

      return $forward;
   }
}
?>
