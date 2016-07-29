<?php
use rosasurfer\ministruts\core\Object;

use rosasurfer\ministruts\exception\ClassNotFoundException;
use rosasurfer\ministruts\exception\IllegalStateException;
use rosasurfer\ministruts\exception\IllegalTypeException;
use rosasurfer\ministruts\exception\InvalidArgumentException;
use rosasurfer\ministruts\exception\RuntimeException;

use rosasurfer\ministruts\struts\Request;

use function rosasurfer\is_class;
use function rosasurfer\strStartsWith;

use rosasurfer\L_WARN;


/**
 * ActionMapping
 */
class ActionMapping extends Object {


   /**
    * Ob diese Komponente vollständig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder Versuch,
    * die Komponente zu ändern, eine IllegalStateException.
    */
   protected /*bool*/ $configured = false;


   protected /*string */ $path;
   protected /*string */ $actionClassName;
   protected /*string */ $formClassName;
   protected /*string */ $scope = 'request';
   protected /*bool   */ $validate;
   protected /*bool[] */ $methods;
   protected /*string */ $roles;
   protected /*bool   */ $default = false;


   /**
    * Im Mapping statt einer Action konfigurierter ActionForward.
    */
   protected /*ActionForward*/ $forward;


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
    * @param  Module $module - Module, zu dem dieses Mapping gehört
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
    * @param  string $path
    *
    * @return ActionMapping
    */
   public function setPath($path) {
      if ($this->configured)          throw new IllegalStateException('Configuration is frozen');
      if (!is_string($path))          throw new IllegalTypeException('Illegal type of parameter $path: '.getType($path));
      if (!strStartsWith($path, '/')) throw new InvalidArgumentException('The "path" attribute of a mapping must begin with a slash "/", found "'.$path.'"');

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
    * Ob das Mapping für die angegebene HTTP-Methode konfiguriert wurde.
    *
    * @param  string $method - HTTP-Methode
    *
    * @return bool
    */
   public function isSupportedMethod($method) {
      if (!is_string($method)) throw new IllegalTypeException('Illegal type of parameter $method: '.getType($method));

      return isSet($this->methods[strToUpper($method)]);
   }


   /**
    * Setzt die HTTP-Methoden, die dieses Mapping unterstützt.  Requests einer nicht konfigurierten
    * Methode werden abgewiesen.
    *
    * @param  string $method - HTTP-Methode: "GET"|"POST"
    *
    * @return ActionMapping
    */
   public function setMethod($method) {
      if ($this->configured)                   throw new IllegalStateException('Configuration is frozen');
      if (!is_string($method))                 throw new IllegalTypeException('Illegal type of parameter $method: '.getType($method));
      $method = strToUpper($method);
      if ($method!=='GET' && $method!=='POST') throw new InvalidArgumentException('Invalid argument $method: '.$method);

      $this->methods[$method] = true;
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
    * @param  string - Rollenausdruck
    *
    * @return ActionMapping
    */
   public function setRoles($roles) {
      if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($roles)) throw new IllegalTypeException('Illegal type of parameter $roles: '.getType($roles));

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
    * @param  ActionForward $forward - ActionForward
    *
    * @return ActionMapping
    */
   public function setForward(ActionForward $forward) {
      if ($this->configured)      throw new IllegalStateException('Configuration is frozen');
      if ($this->actionClassName) throw new RuntimeException('Configuration error: Only one attribute of "action", "include", "redirect" or "forward" can be specified for mapping "'.$this->path.'"');

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
    * @param  string $className
    *
    * @return ActionMapping
    */
   public function setActionClassName($className) {
      if ($this->configured)                                       throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                  throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
      if (!is_class($className))                                   throw new ClassNotFoundException("Undefined class '$className'");
      if (!is_subclass_of($className, Struts ::ACTION_BASE_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::ACTION_BASE_CLASS.': '.$className);
      if ($this->forward)                                          throw new RuntimeException('Configuration error: Only one attribute of "action", "include", "redirect" or "forward" can be specified for mapping "'.$this->path.'"');

      $this->actionClassName = $className;
      return $this;
   }


   /**
    * Gibt den Klassennamen der auszuführenden Action zurück, oder NULL, wenn keine Action konfiguriert
    * wurde.
    *
    * @return string - Klassenname
    */
   public function getActionClassName() {
      return $this->actionClassName;
   }


   /**
    * Setzt den Klassennamen der zur Action gehörenden ActionForm.
    *
    * @param  string $className
    *
    * @return ActionMapping
    */
   public function setFormClassName($className) {
      if ($this->configured)                                            throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                       throw new IllegalTypeException('Illegal type of parameter $className: '.getType($className));
      if (!is_class($className))                                        throw new ClassNotFoundException("Undefined class '$className'");
      if (!is_subclass_of($className, Struts ::ACTION_FORM_BASE_CLASS)) throw new InvalidArgumentException('Not a subclass of '.Struts ::ACTION_FORM_BASE_CLASS.': '.$className);

      $this->formClassName = $className;
      return $this;
   }


   /**
    * Gibt den Klassennamen der ActionForm oder NULL, wenn keine ActionForm konfiguriert wurde, zurück.
    *
    * @return string - Klassenname
    */
   public function getFormClassName() {
      return $this->formClassName;
   }


   /**
    * Setzt das Scope-Attribute der ActionForm dieses Mappings.  Das Scope-Attribute bestimmt, in
    * welchem Kontext auf die ActionForm-Instanz zugegriffen wird.  Default ist "request".  Wird dieser
    * Wert auf "session" gesetzt, können Formulareingaben über mehrere Requests zur Verfügung stehen
    * (z.B. für Page-Wizards o.ä. mehrseitige Formulare).
    *
    * @param  string $scope - "request" oder "session"
    *
    * @return ActionMapping
    */
   public function setScope($scope) {
      if ($this->configured)                        throw new IllegalStateException('Configuration is frozen');
      if (!is_string($scope))                       throw new IllegalTypeException('Illegal type of parameter $scope: '.getType($scope));
      if ($scope!=='request' && $scope!=='session') throw new InvalidArgumentException('Invalid argument $scope: '.$scope);

      $this->scope = $scope;
      return $this;
   }


   /**
    * Gibt den Bezeichner des Kontexts zurück, in dem auf die ActionForm dieses Mappings zugegriffen wird.
    *
    * @return string - Scope-Bezeichner
    */
   public function getScope() {
      return $this->scope;
   }


   /**
    * Ob die ActionForm dieses Mappings im Request gespeichert wird.
    *
    * @return bool
    *
    * @see ActionMapping::setScope()
    */
   public function isRequestScope() {
      return ($this->scope == 'request');
   }


   /**
    * Ob die ActionForm dieses Mappings in der HttpSession gespeichert wird.
    *
    * @return bool
    *
    * @see ActionMapping::setScope()
    */
   public function isSessionScope() {
      return ($this->scope == 'session');
   }


   /**
    * Setzt das Validate-Flag für die ActionForm des ActionMappings.  Das Flag zeigt an, ob die
    * ActionForm vor Aufruf der Action validiert werden soll oder nicht.  Ohne entsprechende Angabe
    * in der struts-config.xml wird die ActionForm immer validiert.
    *
    * @param  bool $default
    *
    * @return ActionMapping
    */
   public function setValidate($validate) {
      if ($this->configured)   throw new IllegalStateException('Configuration is frozen');
      if (!is_bool($validate)) throw new IllegalTypeException('Illegal type of parameter $validate: '.getType($validate));

      $this->validate = $validate;
      return $this;
   }


   /**
    * Ob die ActionForm des Mappings vor Aufruf der Action automatisch validiert wird oder nicht.
    *
    * @return bool
    */
   public function isValidate() {
      return ($this->validate);
   }


   /**
    * Setzt das Default-Flag für dieses ActionMapping. Requests, die keinem anderen Mapping zugeordnet
    * werden können, werden von dem Mapping mit gesetztem Default-Flag verarbeitet. Nur ein Mapping
    * innerhalb eines Modules kann dieses Flag gesetzt werden.
    *
    * @param  bool $default
    *
    * @return ActionMapping
    */
   public function setDefault($default) {
      if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
      if (!is_bool($default)) throw new IllegalTypeException('Illegal type of parameter $default: '.getType($default));

      $this->default = $default;
      return $this;
   }


   /**
    * Ob für dieses ActionMapping das Default-Flag gesetzt ist.
    *
    * @return bool
    *
    * @see setDefault()
    */
   public function isDefault() {
      return (bool) $this->default;
   }


   /**
    * Fügt dem ActionMapping unter dem angegebenen Namen einen ActionForward hinzu. Der angegebene
    * Name kann vom internen Namen des Forwards abweichen, sodaß die Definition von Aliassen möglich
    * ist (ein Forward ist unter mehreren Namen auffindbar).
    *
    * @param  string        $name
    * @param  ActionForward $forward
    *
    * @return ActionMapping
    */
   public function addForward($name, ActionForward $forward) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));

      if (isSet($this->forwards[$name]))
         throw new RuntimeException('Non-unique name detected for local action forward "'.$name.'"');

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
         if (!$this->path)                                 throw new IllegalStateException('A "path" attribute must be configured for mapping "'.$this->path.'"');
         if (!$this->formClassName && $this->validate)     throw new IllegalStateException('A "form" must be configured for "validate" attribute value "true" in mapping "'.$this->path.'"');

         if (!$this->actionClassName && !$this->forward) {
            if (!$this->formClassName || !$this->validate) throw new IllegalStateException('Either an "action", "include", "redirect" or "forward" attribute must be specified for mapping "'.$this->path.'"');

            if (!$this->formClassName || !$this->validate) {
               throw new IllegalStateException('Either an "action", "include", "redirect" or "forward" attribute must be specified for mapping "'.$this->path.'"');
            }
            elseif ($this->formClassName && $this->validate) {
               if (!isSet($this->forwards[ActionForward ::VALIDATION_SUCCESS_KEY]) || !isSet($this->forwards[ActionForward ::VALIDATION_ERROR_KEY]))
                  throw new IllegalStateException('A "success" and an "error" forward must be configured for validation of mapping "'.$this->path.'"');
            }
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
    * gefunden, wird NULL zurückgegeben.  Zusätzlich zu den konfigurierten Forwards kann zur Laufzeit
    * unter dem geschützten Bezeichner "__self" ein Redirect-Forward auf das ActionMapping selbst
    * abgerufen werden.
    *
    * @param  string $name - logischer Name
    *
    * @return ActionForward
    */
   public function findForward($name) {
      if (isSet($this->forwards[$name]))
         return $this->forwards[$name];

      if ($name === ActionForward ::__SELF) {
         $url = $this->path;

         $query = Request ::me()->getQueryString();
         if (strLen($query))
            $url .= '?'.$query;

         $class = $this->module->getForwardClass();
         $forward = new $class($name, $url, true);
         //don't call $forward->freeze(), userland code may want to modify this forward further
         return $forward;
      }

      $forward = $this->module->findForward($name);

      if ($this->configured && !$forward)
         Logger::log('No ActionForward found for name "'.$name.'"', null, L_WARN, __CLASS__);

      return $forward;
   }
}
