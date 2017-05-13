<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalArgumentException;
use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;

use rosasurfer\log\Logger;

use function rosasurfer\strStartsWith;

use const rosasurfer\L_WARN;


/**
 * ActionMapping
 */
class ActionMapping extends Object {


    /** @var bool - Ob diese Komponente vollstaendig konfiguriert ist. */
    protected $configured = false;

    /** @var string */
    protected $path;

    /** @var string */
    protected $actionClassName;

    /** @var string */
    protected $formClassName;

    /** @var string */
    protected $formScope = 'request';

    /** @var bool */
    protected $formValidateFirst;

    /** @var bool[] */
    protected $methods;

    /** @var string */
    protected $roles;

    /** @var bool */
    protected $default = false;

    /** @var ActionForward - Im Mapping statt einer Action konfigurierter ActionForward. */
    protected $forward;

    /** @var ActionForward[] - Die lokalen Forwards dieses Mappings. */
    protected $forwards = [];

    /** @var Module - Module, zu dem das Mapping gehoert */
    protected $module;


    /**
     * Constructor
     *
     * @param  Module $module - Module, zu dem dieses Mapping gehoert
     */
    public function __construct(Module $module) {
        $this->module = $module;
    }


    /**
     * Gibt das Module, zu dem dieses Mapping gehoert, zurueck.
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
     * @return $this
     */
    public function setPath($path) {
        if ($this->configured)          throw new IllegalStateException('Configuration is frozen');
        if (!strStartsWith($path, '/')) throw new IllegalArgumentException('Illegal parameter $path: "'.$path.'" (value must start with a slash "/")');
        $this->path = $path;
        return $this;
    }


    /**
     * Gibt den Pfad dieses Mappings zurueck.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }


    /**
     * Ob das Mapping fuer die angegebene HTTP-Methode konfiguriert wurde.
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
     * Setzt die HTTP-Methoden, die dieses Mapping unterstuetzt.  Requests einer nicht konfigurierten
     * Methode werden abgewiesen.
     *
     * @param  string $method - HTTP-Methode: "GET"|"POST"
     *
     * @return $this
     */
    public function setMethod($method) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $method = strToUpper($method);
        if ($method!='GET' && $method!='POST') throw new IllegalArgumentException('Illegal parameter $method: "'.$method.'" (invalid HTTP method)');

        $this->methods[$method] = true;
        return $this;
    }


    /**
     * Gibt die Rollenbeschraenkung dieses Mappings zurueck.
     *
     * @return string - Rollenbezeichner
     */
    public function getRoles() {
        return $this->roles;
    }


    /**
     * Setzt die Rollenbeschraenkung dieses Mappings.  Requests, die nicht den angebenen Rollen genuegen,
     * werden abgewiesen.
     *
     * @param  string - Rollenausdruck
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setRoles($roles) {
        if ($this->configured)  throw new IllegalStateException('Configuration is frozen');

        //static $pattern = '/^!?[A-Za-z_][A-Za-z0-9_]*(,!?[A-Za-z_][A-Za-z0-9_]*)*$/';
        static $pattern = '/^!?[A-Za-z_][A-Za-z0-9_]*$/';
        if (!strLen($roles) || !preg_match($pattern, $roles)) throw new StrutsConfigException('<mapping path="'.$this->path.'" roles="'.$roles.'": Invalid roles expression.');

        // check for impossible constraints, ie. "Member,!Member"
        $tokens = explode(',', $roles);
        $keys = array_flip($tokens);

        foreach ($tokens as $role) {
            if (isSet($keys['!'.$role])) throw new StrutsConfigException('<mapping path="'.$this->path.'" roles="'.$roles.'": Invalid roles expression.');
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
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setForward(ActionForward $forward) {
        if ($this->configured)      throw new IllegalStateException('Configuration is frozen');
        if ($this->actionClassName) throw new StrutsConfigException('<mapping path="'.$this->path.'": Only one of "action", "include", "forward" or "redirect" must be specified.');

        $this->forward = $forward;
        return $this;
    }


    /**
     * Gibt den direkt konfigurierten ActionForward zurueck, oder NULL, wenn eine Action konfiguriert wurde.
     *
     * @return ActionForward|null
     */
    public function getForward() {
        return $this->forward;
    }


    /**
     * Setzt den Klassennamen der auszufuehrenden Action.
     *
     * @param  string $className
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setActionClassName($className) {
        if ($this->configured)                              throw new IllegalStateException('Configuration is frozen');
        if (!is_subclass_of($className, ACTION_BASE_CLASS)) throw new StrutsConfigException('<mapping path="'.$this->path.'" action="'.$className.'": Not a subclass of '.ACTION_BASE_CLASS.'.');
        if ($this->forward)                                 throw new StrutsConfigException('<mapping path="'.$this->path.'": Only one of "action", "include", "forward" or "redirect" must be specified.');

        $this->actionClassName = $className;
        return $this;
    }


    /**
     * Gibt den Klassennamen der auszufuehrenden Action zurueck, oder NULL, wenn keine Action konfiguriert
     * wurde.
     *
     * @return string - Klassenname
     */
    public function getActionClassName() {
        return $this->actionClassName;
    }


    /**
     * Setzt den Klassennamen der zur Action gehoerenden ActionForm.
     *
     * @param  string $className
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setFormClassName($className) {
        if ($this->configured)                                   throw new IllegalStateException('Configuration is frozen');
        if (!is_subclass_of($className, ACTION_FORM_BASE_CLASS)) throw new StrutsConfigException('<mapping path="'.$this->path.'" form="'.$className.'": Not a subclass of '.ACTION_FORM_BASE_CLASS.'.');

        $this->formClassName = $className;
        return $this;
    }


    /**
     * Gibt den Klassennamen der ActionForm oder NULL, wenn keine ActionForm konfiguriert wurde, zurueck.
     *
     * @return string - Klassenname
     */
    public function getFormClassName() {
        return $this->formClassName;
    }


    /**
     * Setzt das Scope-Attribute der ActionForm dieses Mappings.  Das Scope-Attribute bestimmt, in
     * welchem Kontext auf die ActionForm-Instanz zugegriffen wird.  Default ist "request".  Wird dieser
     * Wert auf "session" gesetzt, koennen Formulareingaben ueber mehrere Requests zur Verfuegung stehen
     * (z.B. fuer Page-Wizards o.ae. mehrseitige Formulare).
     *
     * @param  string $value - "request" oder "session"
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setFormScope($value) {
        if ($this->configured)                      throw new IllegalStateException('Configuration is frozen');
        if ($value!='request' && $value!='session') throw new StrutsConfigException('<mapping path="'.$this->path.'" form-scope="'.$value.'": Invalid form scope.');

        $this->formScope = $value;
        return $this;
    }


    /**
     * Gibt den Bezeichner des Kontexts zurueck, in dem auf die ActionForm dieses Mappings zugegriffen wird.
     *
     * @return string - Scope-Bezeichner
     */
    public function getFormScope() {
        return $this->formScope;
    }


    /**
     * Ob die ActionForm dieses Mappings im Request gespeichert wird.
     *
     * @return bool
     *
     * @see ActionMapping::setFormScope()
     */
    public function isRequestScope() {
        return ($this->formScope == 'request');
    }


    /**
     * Ob die ActionForm dieses Mappings in der HttpSession gespeichert wird.
     *
     * @return bool
     *
     * @see ActionMapping::setFormScope()
     */
    public function isSessionScope() {
        return ($this->formScope == 'session');
    }


    /**
     * Setzt das FormValidateFirst-Flag fuer die ActionForm des ActionMappings.  Das Flag zeigt an, ob die
     * ActionForm vor Aufruf der Action validiert werden soll oder nicht.  Ohne entsprechende Angabe
     * in der struts-config.xml wird die ActionForm immer validiert.
     *
     * @param  bool $mode
     *
     * @return $this
     */
    public function setFormValidateFirst($mode) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $this->formValidateFirst = $mode;
        return $this;
    }


    /**
     * Ob die ActionForm des Mappings vor Aufruf der Action automatisch validiert wird oder nicht.
     *
     * @return bool
     */
    public function isFormValidateFirst() {
        return (bool) $this->formValidateFirst;
    }


    /**
     * Setzt das Default-Flag fuer dieses ActionMapping. Requests, die keinem anderen Mapping zugeordnet
     * werden koennen, werden von dem Mapping mit gesetztem Default-Flag verarbeitet. Nur ein Mapping
     * innerhalb eines Modules kann dieses Flag gesetzt werden.
     *
     * @param  bool $default
     *
     * @return $this
     */
    public function setDefault($default) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $this->default = $default;
        return $this;
    }


    /**
     * Ob fuer dieses ActionMapping das Default-Flag gesetzt ist.
     *
     * @return bool
     *
     * @see setDefault()
     */
    public function isDefault() {
        return (bool) $this->default;
    }


    /**
     * Fuegt dem ActionMapping unter dem angegebenen Namen einen ActionForward hinzu.
     *
     * @param  ActionForward $forward
     * @param  string|null   $alias   - alias name of the forward (default: none)
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function addForward(ActionForward $forward, $alias=null) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        $name = is_null($alias) ? $forward->getName() : $alias;
        if (isSet($this->forwards[$name])) throw new StrutsConfigException('<mapping path="'.$this->path.'"> <forward name="'.$name.'": Non-unique forward name.');

        $this->forwards[$name] = $forward;
        return $this;
    }


    /**
     * Friert die Konfiguration dieser Komponente ein.
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function freeze() {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');

        if (!$this->path)                                           throw new StrutsConfigException('A "path" attribute must be configured for '.$this);
        if (!$this->formClassName && $this->formValidateFirst)      throw new StrutsConfigException('<mapping path="'.$this->path.'": A form must be configured if "form-validate-first" is set to "true".');

        if (!$this->actionClassName && !$this->forward) {
            if (!$this->formClassName || !$this->formValidateFirst) throw new StrutsConfigException('<mapping path="'.$this->path.'": Either an "action", "include", "forward" or "redirect" attribute must be specified.');

            if (!$this->formClassName || !$this->formValidateFirst) {
                throw new StrutsConfigException('<mapping path="'.$this->path.'": Either an "action", "include", "forward" or "redirect" attribute must be specified.');
            }
            elseif ($this->formClassName && $this->formValidateFirst) {
                if (!isSet($this->forwards[ActionForward::VALIDATION_SUCCESS_KEY]) || !isSet($this->forwards[ActionForward::VALIDATION_ERROR_KEY]))
                    throw new IllegalStateException('<mapping path="'.$this->path.' form="'.$this->formClassName.'": A "success" and "error" forward must be configured to validate the form first.');
            }
        }

        $this->configured = true;
        return $this;
    }


    /**
     * Sucht und gibt den ActionForward mit dem angegebenen Namen zurueck. Zuerst werden die lokalen
     * Forwards des Mappings durchsucht, danach die globalen Forwards des Modules.  Wird kein Forward
     * gefunden, wird NULL zurueckgegeben.  Zusaetzlich zu den konfigurierten Forwards kann zur Laufzeit
     * unter dem geschuetzten Bezeichner "__self" ein Redirect-Forward auf das ActionMapping selbst
     * abgerufen werden.
     *
     * @param  string $name - logischer Name
     *
     * @return ActionForward|null
     */
    public function findForward($name) {
        if (isSet($this->forwards[$name]))
            return $this->forwards[$name];

        if ($name === ActionForward::__SELF) {
            $url = $this->path;

            $query = Request::me()->getQueryString();
            if (strLen($query))
                $url .= '?'.$query;

            $class = $this->module->getForwardClass();
            return new $class($name, $url, true);
        }

        $forward = $this->module->findForward($name);

        if ($this->configured && !$forward)
            Logger::log('No ActionForward found for name "'.$name.'"', L_WARN);

        return $forward;
    }
}
