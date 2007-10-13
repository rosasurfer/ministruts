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
   protected $method;      // string
   protected $default;     // boolean

   protected /*String[]*/ $roles = array();

   protected /*ActionForward*/ $forward;  // im Mapping angegebener ActionForward (statt einer Action)


   /**
    * Die lokalen Forwards dieses Mappings.
    */
   protected /*ActionForward[]*/ $forwards = array();


   /**
    * Module, zu dem das Mapping gehört
    */
   protected /*Module*/ $module;


   /**
    * Constructor
    *
    * @param Module $module - Module, zu dem dieses Mapping gehört
    */
   public function __construct(Module $module) {
      $this->module = $module;
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
    * Gibt die Methodenbeschränkung dieses Mappings zurück.
    *
    * @return string - Name einer HTTP-Methode oder NULL, wenn keine Einschränkung existiert
    */
   public function getMethod() {
      return $this->method;
   }


   /**
    * Setzt die Methodenbeschränkung dieses Mappings.  Requests, die nicht der angegebenen Methode entsprechen, werden abgewiesen.
    *
    * @param string $method - HTTP-Methode, "GET" oder "POST"
    *
    * @return ActionMapping
    */
   public function setMethod($method) {
      if ($this->configured)                   throw new IllegalStateException('Configuration is frozen');
      if (!is_string($method))                 throw new IllegalTypeException('Illegal type of argument $method: '.getType($method));
      $method = strToUpper($method);
      if ($method!=='GET' && $method!=='POST') throw new InvalidArgumentException('Invalid argument $method: '.$method);

      $this->method = $method;
      return $this;
   }


   /**
    * Gibt die Rollenbeschränkung dieses Mappings zurück.
    *
    * @return string - Rollenbezeichner
    */
   public function getRoles() {
      return $this->roles;
   }


   /**
    * Setzt die Rollenbeschränkung dieses Mappings.  Requests, die nicht mindestens einer angegebenen Rolle genügen, werden abgewiesen.
    * Beginnt ein Rollenbezeichner mit einem Ausrufezeichen "!", darf der User der angegebenen Rolle NICHT angehören.
    *
    * @param string - ein oder mehrere Rollenbezeichner (komma-getrennet)
    *
    * @return ActionMapping
    */
   public function setRoles($roles) {
      if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($roles)) throw new IllegalTypeException('Illegal type of argument $roles: '.getType($roles));

      static $pattern = '/^!?[A-Za-z_][A-Za-z0-9_]*(,!?[A-Za-z_][A-Za-z0-9_]*)*$/';
      if (!strLen($roles) || !preg_match($pattern, $roles)) throw new InvalidArgumentException('Invalid argument $roles: "'.$roles.'"');

      // check for impossible constraints, ie. "Member,!Member"
      $tokens = explode(',', $roles);
      $keys = array_flip($tokens);

      foreach ($tokens as $role) {
         if (isSet($keys['!'.$role])) throw new InvalidArgumentException('Invalid argument $roles: "'.$roles.'"');
      }

      // remove duplicates
      $this->roles = join(',', array_flip($keys));
      return $this;
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
      if ($this->configured)                                       throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                  throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::ACTION_BASE_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::ACTION_BASE_CLASS.': '.$className);
      if ($this->forward)                                          throw new RuntimeException('Configuration error: Exactly one of action "forward" or action "type" must be specified.');

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
      if ($this->configured)                                            throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                       throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, Struts ::ACTION_FORM_BASE_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::ACTION_FORM_BASE_CLASS.': '.$className);

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
    * durchsucht und danach die globalen Forwards des Modules.  Wird kein Forward gefunden, wird NULL zurückgegeben.  Es
    * existiert immer ein Forward mit dem speziellen Name "__self". Er ist ein Redirect-Forward auf das ActionMapping selbst.
    *
    * @param $name - logischer Name des ActionForwards
    *
    * @return ActionForward
    */
   public function findForward($name) {
      if (isSet($this->forwards[$name]))
         return $this->forwards[$name];

      if ($name === '__self') {
         $class = $this->module->getForwardClass();
         $forward = new $class($name, $this->path, true);
         $forward->freeze();
         return $this->forwards[$name] = $forward;
      }

      $forward = $this->module->findForward($name);

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
