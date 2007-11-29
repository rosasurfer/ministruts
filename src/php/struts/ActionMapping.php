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
   protected $roles;       // string

   /**
    * Direkt im Mapping statt einer Action konfigurierter ActionForward.
    */
   protected /*ActionForward*/ $forward;  //


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
    * Gibt das Module, zu dem dieses Mapping gehört, zurück.
    *
    * @return Module instance
    */
   public function getModule() {
      return $this->module;
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
    * Setzt die Methodenbeschränkung dieses Mappings.  Requests, die nicht der angegebenen Methode
    * entsprechen, werden abgewiesen.
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
    * Setzt die Rollenbeschränkung dieses Mappings.  Requests, die nicht den angebenen Rollen genügen,
    * werden abgewiesen.
    *
    * @param string - Rollenausdruck
    *
    * @return ActionMapping
    */
   public function setRoles($roles) {
      if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($roles)) throw new IllegalTypeException('Illegal type of argument $roles: '.getType($roles));

      //static $pattern = '/^!?[A-Za-z_][A-Za-z0-9_]*(,!?[A-Za-z_][A-Za-z0-9_]*)*$/';
      static $pattern = '/^!?[A-Za-z_][A-Za-z0-9_]*$/';

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
    * Setzt einen direkt konfigurierten ActionForward (statt einer Action).
    *
    * @param ActionForward $forward - ActionForward
    *
    * @return ActionMapping
    */
   public function setForward(ActionForward $forward) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if ($this->action)     throw new RuntimeException('Configuration error: Only one of mapping "forward" or mapping "action" can be specified.');

      $this->forward = $forward;
      return $this;
   }


   /**
    * Gibt den direkt konfigurierten ActionForward zurück, oder NULL, wenn eine Action konfiguriert wurde.
    *
    * @return ActionForward
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
      if ($this->forward)                                          throw new RuntimeException('Configuration error: Only one of mapping "forward" or mapping "action" can be specified.');

      $this->action = $className;
      return $this;
   }


   /**
    * Gibt den Klassennamen der auszuführenden Action zurück, oder NULL, wenn keine Action konfiguriert
    * wurde.
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
    * Setzt das Default-Flag für dieses ActionMapping. Requests, die keinem anderen Mapping zugeordnet
    * werden können, werden von dem Mapping mit gesetztem Default-Flag verarbeitet. Nur ein Mapping
    * innerhalb eines Modules kann dieses Flag gesetzt werden.
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
    * @return Actionmapping
    */
   public function freeze() {
      if (!$this->configured) {
         if (!$this->path)
            throw new IllegalStateException('No path configured for this '.$this);

         // wenn weder Action noch interner ActionForward angegeben sind, nach einer zum Pfad passenden Action suchen
         if (!$this->action && !$this->forward) {
            $className = ucFirst(baseName($this->path, '.php')).'Action';
            if (!is_class($className))
               throw new IllegalStateException('Either an action or a forward must be configured for this '.$this);
            $this->setAction($className);
         }

         // wenn keine ActionForm angegeben ist, nach einer zur Action passenden ActionForm suchen
         if ($this->action && !$this->form && is_class($this->action.'Form')) {
            $this->setForm($this->action.'Form');
         }

         if ($this->forward)
            $this->forward->freeze();

         foreach ($this->forwards as $forward)
            $forward->freeze();

         $this->configured = true;
      }
      return $this;
   }


   /**
    * Sucht und gibt den ActionForward mit dem angegebenen Namen zurück. Zuerst werden die lokalen
    * Forwards des Mappings durchsucht, danach die globalen Forwards des Modules.  Wird kein Forward
    * gefunden, wird NULL zurückgegeben.  Zusätzlich zu den konfigureierten Forwards kann zur Laufzeit
    * unter dem speziellen Namen "__self" ein Redirect-Forward auf das ActionMapping selbst abgerufen
    * werden.
    *
    * @param $name - logischer Name des ActionForwards
    *
    * @return ActionForward
    */
   public function findForward($name) {
      if (isSet($this->forwards[$name]))
         return $this->forwards[$name];

      if ($name === ActionForward ::__SELF) {
         $query = Request ::me()->getQueryString();
         $url = $this->path.($query===null ? '' : '?'.$query);

         $class = $this->module->getForwardClass();
         $forward = new $class($name, $url, true);
         //$forward->freeze();  // don't freeze it, user code may want to change it
         return $forward;
      }

      $forward = $this->module->findForward($name);

      if (!$forward && $this->configured)
         Logger ::log('No ActionForward found for name: "'.$name.'"', L_WARN, __CLASS__);

      return $forward;
   }
}
?>
