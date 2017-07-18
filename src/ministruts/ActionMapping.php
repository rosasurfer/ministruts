<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;

use rosasurfer\log\Logger;

use function rosasurfer\strStartsWith;

use const rosasurfer\L_WARN;


/**
 * ActionMapping
 */
class ActionMapping extends Object {


    /** @var bool - whether or not this component is fully configured */
    protected $configured = false;

    /** @var string */
    protected $name;

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

    /** @var ActionForward - the mapping's explicitely specified ActionForward. */
    protected $forward;

    /** @var ActionForward[] - the mapping's local forwards */
    protected $forwards = [];

    /** @var Module - Module the mapping belongs to */
    protected $module;


    /**
     * Constructor
     *
     * @param  Module $module - Module the mapping belongs to
     */
    public function __construct(Module $module) {
        $this->module = $module;
    }


    /**
     * Return the {@link Module} the mapping belongs to.
     *
     * @return Module
     */
    public function getModule() {
        return $this->module;
    }


    /**
     * Set the mapping's name.
     *
     * @param  string $name
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setName($name) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $sPath = $this->path ? ' path="'.$this->path.'"':'';
        $_name = trim($name);
        if (!strLen($_name)) throw new StrutsConfigException('<mapping name="'.$name.'"'.$sPath.': Illegal name (empty value).');
        $this->name = $_name;
        return $this;
    }


    /**
     * Return the mapping's name.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * Set the mapping's path.
     *
     * @param  string $path
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setPath($path) {
        if ($this->configured)          throw new IllegalStateException('Configuration is frozen');
        $sName = $this->name ? ' name="'.$this->name.'"':'';
        if (!strStartsWith($path, '/')) throw new StrutsConfigException('<mapping'.$sName.' path="'.$path.'": Illegal path (value must start with a slash "/").');
        $this->path = $path;
        return $this;
    }


    /**
     * Return the mapping's path.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }


    /**
     * Whether or not the mapping is configured to handle requests of the specified HTTP method.
     *
     * @param  string $method - HTTP method verb
     *
     * @return bool
     */
    public function isSupportedMethod($method) {
        if (!is_string($method)) throw new IllegalTypeException('Illegal type of parameter $method: '.getType($method));
        return isSet($this->methods[strToUpper($method)]);
    }


    /**
     * Set the HTTP methods the mapping will handle.
     *
     * @param  string $method - HTTP method verb
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setMethod($method) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $sName = $this->name ? ' name="'.$this->name.'"':'';
        $sPath = $this->path ? ' path="'.$this->path.'"':'';

        $_method = strToUpper($method);
        if ($_method!='GET' && $_method!='POST') throw new StrutsConfigException('<mapping'.$sName.''.$sPath.' http-methods="'.$method.'":  Invalid HTTP method.');

        $this->methods[$_method] = true;
        return $this;
    }


    /**
     * Return the mapping's role restrictions.
     *
     * @return string|null - role identifier or NULL if no role restrictions are defined
     */
    public function getRoles() {
        return $this->roles;
    }


    /**
     * Set the mapping's role restrictions.
     *
     * @param  string - role expression
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setRoles($roles) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $sName = $this->name ? ' name="'.$this->name.'"':'';
        $sPath = $this->path ? ' path="'.$this->path.'"':'';

        //static $pattern = '/^!?[A-Za-z_][A-Za-z0-9_]*(,!?[A-Za-z_][A-Za-z0-9_]*)*$/';
        static $pattern = '/^!?[A-Za-z_][A-Za-z0-9_]*$/';
        if (!strLen($roles) || !preg_match($pattern, $roles)) throw new StrutsConfigException('<mapping'.$sName.$sPath.' roles="'.$roles.'": Invalid roles expression.');

        // check for invalid id combinations, e.g. "Member,!Member"
        $tokens = explode(',', $roles);
        $keys = array_flip($tokens);

        foreach ($tokens as $role) {
            if (isSet($keys['!'.$role])) throw new StrutsConfigException('<mapping'.$sName.$sPath.' roles="'.$roles.'": Invalid roles expression.');
        }

        // remove duplicates
        $this->roles = join(',', array_flip($keys));
        return $this;
    }


    /**
     * Explicitely configure an {@link ActionForward} instead of an {@link Action}.
     *
     * @param  ActionForward $forward
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setForward(ActionForward $forward) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $sName = $this->name ? ' name="'.$this->name.'"':'';
        $sPath = $this->path ? ' path="'.$this->path.'"':'';

        if ($this->actionClassName) throw new StrutsConfigException('<mapping'.$sName.$sPath.': Only one of "action", "include", "forward" or "redirect" can be specified.');

        $this->forward = $forward;
        return $this;
    }


    /**
     * Return the explicitely configured {@link ActionForward}.
     *
     * @return ActionForward|null - ActionForward or NULL if no explicit forward is configured
     */
    public function getForward() {
        return $this->forward;
    }


    /**
     * Set the class name of the {@link Action} to process requests.
     *
     * @param  string $className
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setActionClassName($className) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $sName = $this->name ? ' name="'.$this->name.'"':'';
        $sPath = $this->path ? ' path="'.$this->path.'"':'';

        if (!is_subclass_of($className, ACTION_BASE_CLASS)) throw new StrutsConfigException('<mapping'.$sName.$sPath.' action="'.$className.'": Not a subclass of '.ACTION_BASE_CLASS.'.');
        if ($this->forward)                                 throw new StrutsConfigException('<mapping'.$sName.$sPath.': Only one of "action", "include", "forward" or "redirect" can be specified.');

        $this->actionClassName = $className;
        return $this;
    }


    /**
     * Return the class name of the {@link Action} to process requests.
     *
     * @return string|null - Action class name or NULL if no Action is configured
     */
    public function getActionClassName() {
        return $this->actionClassName;
    }


    /**
     * Set the class name of the {@link ActionForm} used by the mapping.
     *
     * @param  string $className
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setFormClassName($className) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $sName = $this->name ? ' name="'.$this->name.'"':'';
        $sPath = $this->path ? ' path="'.$this->path.'"':'';

        if (!is_subclass_of($className, ACTION_FORM_BASE_CLASS)) throw new StrutsConfigException('<mapping'.$sName.$sPath.' form="'.$className.'": Not a subclass of '.ACTION_FORM_BASE_CLASS.'.');

        $this->formClassName = $className;
        return $this;
    }


    /**
     * Return the class name of the {@link ActionForm} used by the mapping.
     *
     * @return string - ActionForm class name
     */
    public function getFormClassName() {
        return $this->formClassName;
    }


    /**
     * Set the scope attribute used for the {@link ActionForm} of the mapping. The scope attribute identifies the storage
     * location for the Actionform.
     *
     * @param  string $value - may be "request" or "session"
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function setFormScope($value) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $sName = $this->name ? ' name="'.$this->name.'"':'';
        $sPath = $this->path ? ' path="'.$this->path.'"':'';

        if ($value!='request' && $value!='session') throw new StrutsConfigException('<mapping'.$sName.$sPath.' form-scope="'.$value.'": Invalid form scope.');

        $this->formScope = $value;
        return $this;
    }


    /**
     * Return the scope attribute used for storing the {@link ActionForm} of the mapping.
     *
     * @return string - scope attribute value
     */
    public function getFormScope() {
        return $this->formScope;
    }


    /**
     * Whether or not the mapping's {@link ActionForm} is stored in the {@link Request} instance.
     *
     * @return bool
     */
    public function isRequestScope() {
        return ($this->formScope == 'request');
    }


    /**
     * Whether or not the mapping's {@link ActionForm} is stored in the {@link HttpSession} instance.
     *
     * @return bool
     */
    public function isSessionScope() {
        return ($this->formScope == 'session');
    }


    /**
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
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
     * @param  string        $alias [optional] - alias name of the forward
     *
     * @return $this
     *
     * @throws StrutsConfigException in case of configuration errors
     */
    public function addForward(ActionForward $forward, $alias = null) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $sName = $this->name ? ' name="'.$this->name.'"':'';
        $sPath = $this->path ? ' path="'.$this->path.'"':'';

        $name = is_null($alias) ? $forward->getName() : $alias;
        if (isSet($this->forwards[$name])) throw new StrutsConfigException('<mapping'.$sName.$sPath.'> <forward name="'.$name.'": Non-unique forward name.');

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
        $sName = $this->name ? ' name="'.$this->name.'"':'';
        $sPath = $this->path ? ' path="'.$this->path.'"':'';

        if (!$this->path)                                           throw new StrutsConfigException('<mapping'.$sName.$sPath.': A "path" attribute must be configured for '.$this);
        if (!$this->formClassName && $this->formValidateFirst)      throw new StrutsConfigException('<mapping'.$sName.$sPath.': A form must be configured if "form-validate-first" is set to "true".');

        if (!$this->actionClassName && !$this->forward) {
            if (!$this->formClassName || !$this->formValidateFirst) throw new StrutsConfigException('<mapping'.$sName.$sPath.': Either an "action", "include", "forward" or "redirect" attribute must be specified.');

            if (!$this->formClassName || !$this->formValidateFirst) {
                throw new StrutsConfigException('<mapping'.$sName.$sPath.': Either an "action", "include", "forward" or "redirect" attribute must be specified.');
            }
            elseif ($this->formClassName && $this->formValidateFirst) {
                if (!isSet($this->forwards[ActionForward::VALIDATION_SUCCESS_KEY]) || !isSet($this->forwards[ActionForward::VALIDATION_ERROR_KEY]))
                    throw new StrutsConfigException('<mapping'.$sName.$sPath.' form="'.$this->formClassName.'": A "success" and "error" forward must be configured to validate the form.');
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

            /** ActionForward $forward */
            $forward = new $class($name, $url, true);
            return $forward;
        }

        $forward = $this->module->findForward($name);

        if ($this->configured && !$forward)
            Logger::log('No ActionForward found for name "'.$name.'"', L_WARN);

        return $forward;
    }
}
