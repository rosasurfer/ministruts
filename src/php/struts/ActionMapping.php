<?
/**
 * ActionMapping
 */
class ActionMapping extends Object {


   /**
    * Ob diese Komponente vollständig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder Versuch,
    * die Komponente zu ändern, eine IllegalStateException.
    */
   protected $configured = false;


   protected $path;        // string
   protected $action;      // string
   protected $form;        // string
   protected $default;     // boolean

   protected /*ActionForward*/ $forward;  // im Mapping angegebener ActionForward (statt einer Action)


   /**
    * Die lokalen Forwards dieses Mappings.
    */
   protected /*ActionForward[]*/ $forwards = array();


   /**
    * Modulkonfiguration, zu dem das Mapping gehört
    */
   protected /*ModuleConfig*/ $moduleConfig;


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
    * Fügt dem ActionMapping unter dem angegebenen Namen einen ActionForward hinzu. Der angegebene
    * Name kann vom internen Namen des Forwards abweichen, sodaß die Definition von Aliassen möglich
    * ist (ein Forward ist unter mehreren Namen auffindbar).
    *
    * @param string        $name
    * @param ActionForward $forward
    *
    * @return ActionMapping
    */
   public function addForward($name, ActionForward $forward) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));

      if (isSet($this->forwards[$name]))
         throw new RuntimeException('Non-unique identifier detected for local ActionForwards: '.$name);

      $this->forwards[$name] = $forward;
      return $this;
   }


   /**
    * Friert die Konfiguration dieser Komponente ein.
    *
    * @return ActionMapping
    */
   public function freeze() {
      if (!$this->configured) {

         if ($this->path === null)
            throw new IllegalStateException('No path configured for this '.$this);

         if ($this->action === null && $this->forward === null)
            throw new IllegalStateException('Neither an action nor a forward configured for this '.$this);

         // try to find a matching ActionForm
         if ($this->action!==null && $this->form===null && isImportedClass($this->action.'Form')) {
            $this->setForm($this->action.'Form');
         }

         foreach ($this->forwards as $forward)
            $forward->freeze();

         $this->configured = true;
      }
      return $this;
   }


   /**
    * Sucht und gibt den ActionForward mit dem angegebenen Namen zurück. Zuerst werden die lokalen Forwards des Mappings
    * durchsucht und danach die globalen Forwards der Modulkonfiguration.  Wird kein Forward gefunden, wird NULL zurückgegeben.
    * Es existiert immer ein Forward mit dem speziellen Name "__self". Er ist ein Redirect-Forward auf das ActionMapping selbst.
    *
    * @param $name - logischer Name des ActionForwards
    *
    * @return ActionForward
    */
   public function findForward($name) {
      if (isSet($this->forwards[$name]))
         return $this->forwards[$name];

      if ($name === '__self') {
         $class = $this->moduleConfig->getForwardClass();
         $forward = new $class($name, $this->path, true);
         $forward->freeze();
         return $this->forwards[$name] = $forward;
      }

      $forward = $this->moduleConfig->findForward($name);

      if (!$forward && $this->configured)
         Logger ::log('No ActionForward found for name: '.$name, L_ERROR, __CLASS__);

      return $forward;
   }


   /**
    * Return a human readable string representation of this instance.
    *
    * @return string
    */
   public function __toString() {
       return print_r($this, true);
   }
}
?>
