<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\log\Logger;

use function rosasurfer\strCompareI;
use function rosasurfer\strLeftTo;

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
     * @throws StrutsConfigException on configuration errors
     */
    public function setName($name) {
        if ($this->configured)          throw new IllegalStateException('Configuration is frozen');
        if (!strLen($name=trim($name))) throw new StrutsConfigException('<mapping name="'.func_get_arg(0).'"'.($this->path ? ' path="'.$this->path.'"':'').': Illegal name (empty value).');

        $this->name = $name;
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
     * @throws StrutsConfigException on configuration errors
     */
    public function setPath($path) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        if ($path[0] != '/')   throw new StrutsConfigException('<mapping'.($this->name ? ' name="'.$this->name.'"':'').' path="'.$path.'": Illegal path (value must start with a slash "/").');

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
     * Set the HTTP methods the mapping will be able to handle.
     *
     * @param  string $method - HTTP method verb
     *
     * @return $this
     *
     * @throws StrutsConfigException on configuration errors
     */
    public function setMethod($method) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $name = $this->name ? ' name="'.$this->name.'"':'';
        $path = $this->path ? ' path="'.$this->path.'"':'';

        $method = strToUpper($method);
        if ($method!='GET' && $method!='POST') throw new StrutsConfigException('<mapping'.$name.''.$path.' methods="'.func_get_arg(0).'":  Invalid HTTP method.');

        $this->methods[$method] = true;
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
     * @throws StrutsConfigException on configuration errors
     */
    public function setRoles($roles) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $name = $this->name ? ' name="'.$this->name.'"':'';
        $path = $this->path ? ' path="'.$this->path.'"':'';

        //static $pattern = '/^!?[A-Za-z_][A-Za-z0-9_]*(,!?[A-Za-z_][A-Za-z0-9_]*)*$/';
        static $pattern = '/^!?[A-Za-z_][A-Za-z0-9_]*$/';
        if (!strLen($roles) || !preg_match($pattern, $roles)) throw new StrutsConfigException('<mapping'.$name.$path.' roles="'.$roles.'": Invalid roles expression.');

        // check for invalid id combinations, e.g. "Member,!Member"
        $tokens = explode(',', $roles);
        $keys = array_flip($tokens);

        foreach ($tokens as $role) {
            if (isSet($keys['!'.$role])) throw new StrutsConfigException('<mapping'.$name.$path.' roles="'.$roles.'": Invalid roles expression.');
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
     * @throws StrutsConfigException on configuration errors
     */
    public function setForward(ActionForward $forward) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $name = $this->name ? ' name="'.$this->name.'"':'';
        $path = $this->path ? ' path="'.$this->path.'"':'';

        if ($this->actionClassName) throw new StrutsConfigException('<mapping'.$name.$path.': Only one of "action", "include", "forward" or "redirect" can be specified.');

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
     * @throws StrutsConfigException on configuration errors
     */
    public function setActionClassName($className) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $name = $this->name ? ' name="'.$this->name.'"':'';
        $path = $this->path ? ' path="'.$this->path.'"':'';

        if (!is_subclass_of($className, ACTION_BASE_CLASS)) throw new StrutsConfigException('<mapping'.$name.$path.' action="'.$className.'": Not a subclass of '.ACTION_BASE_CLASS.'.');
        if ($this->forward)                                 throw new StrutsConfigException('<mapping'.$name.$path.': Only one of "action", "include", "forward" or "redirect" can be specified.');

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
     * @throws StrutsConfigException on configuration errors
     */
    public function setFormClassName($className) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $name = $this->name ? ' name="'.$this->name.'"':'';
        $path = $this->path ? ' path="'.$this->path.'"':'';

        if (!is_subclass_of($className, ACTION_FORM_BASE_CLASS)) throw new StrutsConfigException('<mapping'.$name.$path.' form="'.$className.'": Not a subclass of '.ACTION_FORM_BASE_CLASS.'.');

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
     * Set the scope attribute used for the mapping's {@link ActionForm}. The scope attribute identifies the storage
     * location of the Actionform.
     *
     * @param  string $value - may be "request" or "session"
     *
     * @return $this
     *
     * @throws StrutsConfigException on configuration errors
     */
    public function setFormScope($value) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $name = $this->name ? ' name="'.$this->name.'"':'';
        $path = $this->path ? ' path="'.$this->path.'"':'';

        if ($value!='request' && $value!='session') throw new StrutsConfigException('<mapping'.$name.$path.' form-scope="'.$value.'": Invalid form scope.');

        $this->formScope = $value;
        return $this;
    }


    /**
     * Return the scope attribute used for storing the mapping's {@link ActionForm}.
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
     * Set the status of the mapping's {@link ActionForm} "validate-first" flag. If the flag is set the ActionForm is
     * validated by the framework before the {@link Action} is executed. If the flag is not set the Action is responsible
     * for validating the ActionForm.
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
     * Whether or not the mapping's {@link ActionForm} is validated by the framework before execution of the the {@link Action}.
     *
     * @return bool
     */
    public function isFormValidateFirst() {
        return (bool) $this->formValidateFirst;
    }


    /**
     * Set the mapping's "default" flag. Requests otherwise causing a HTTP 404 status are processed by the mapping with the
     * "default" flag set. Only one mapping can have this flag set.
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
     * Whether or not the mapping's "default" flag is set.
     *
     * @return bool
     *
     * @see setDefault()
     */
    public function isDefault() {
        return (bool) $this->default;
    }


    /**
     * Add an {@link ActionForward} accessible under the specified name to the mapping.
     *
     * @param  string        $name    - access identifier (may differ from the forward's name)
     * @param  ActionForward $forward
     *
     * @return $this
     *
     * @throws StrutsConfigException on configuration errors
     */
    public function addForward($name, ActionForward $forward) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $mName = $this->name ? ' name="'.$this->name.'"':'';
        $mPath = $this->path ? ' path="'.$this->path.'"':'';
        $mapping = $mName.$mPath;

        if (isSet($this->forwards[$name])) throw new StrutsConfigException('<mapping'.$mapping.'> <forward name="'.$name.'": Non-unique forward identifier.');

        $this->forwards[$name] = $forward;
        return $this;
    }


    /**
     * Lock the mapping's configuration. Called after all properties of the mapping are set.
     *
     * @return $this
     *
     * @throws StrutsConfigException on configuration errors
     */
    public function freeze() {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        $name    = $this->name ? ' name="'.$this->name.'"':'';
        $path    = $this->path ? ' path="'.$this->path.'"':'';
        $mapping = $name.$path;

        if (!$this->path)                                           throw new StrutsConfigException('<mapping'.$mapping.': A "path" attribute must be configured for '.$this);
        if (!$this->formClassName && $this->formValidateFirst)      throw new StrutsConfigException('<mapping'.$mapping.': A form must be configured if "form-validate-first" is set to "true".');

        if (!$this->actionClassName && !$this->forward) {
            if (!$this->formClassName || !$this->formValidateFirst) throw new StrutsConfigException('<mapping'.$mapping.': Either an "action", "include", "forward" or "redirect" attribute must be specified.');

            if (!$this->formClassName || !$this->formValidateFirst) {
                throw new StrutsConfigException('<mapping'.$mapping.': Either an "action", "include", "forward" or "redirect" attribute must be specified.');
            }
            elseif ($this->formClassName && $this->formValidateFirst) {
                if (!isSet($this->forwards[ActionForward::VALIDATION_SUCCESS_KEY]) || !isSet($this->forwards[ActionForward::VALIDATION_ERROR_KEY]))
                    throw new StrutsConfigException('<mapping'.$mapping.' form="'.$this->formClassName.'": A "success" and "error" forward must be configured to validate the form.');
            }
        }

        $this->configured = true;
        return $this;
    }


    /**
     * Lookup and return the {@link ActionForward} accessible under the specified name. First the lookup tries to find a local
     * forward of the given name. If no local forward is found global forwards are checked.
     *
     * @param  string $name - logical name; can be "self" to return a redirect forward to the mapping itself
     *
     * @return ActionForward|null - ActionForward or NULL if neither a local nor a global forward was found
     */
    public function findForward($name) {
        $forward = null;

        if (isSet($this->forwards[$name])) {
            $forward = $this->forwards[$name];
        }
        else if (strCompareI($name, ActionForward::SELF)) {
            $name    = ActionForward::SELF;
            $path    = $this->path;
            $class   = $this->module->getForwardClass();
            /** @var ActionForward $forward */
            $forward = new $class($name, $path, true);
        }
        else {
            $forward = $this->module->findForward($name);
            if (!$forward && $this->configured) Logger::log('No ActionForward found for name "'.$name.'"', L_WARN);
            return $forward;
        }

        if ($forward->getName() == ActionForward::SELF) {
            if ($this->configured) {                            // runtime: set the current request's query string
                $path  = $this->path;
                $query = Request::me()->getQueryString();
                if (strLen($query))
                    $path = strLeftTo($path, '?').'?'.$query;
                $forward->setPath($path);
            }
        }
        return $forward;
    }


    /**
     * Lookup and return the configured {@link ActionForward} accessible under the specified name. This method differs from
     * {@link ActionMapping::findForward()} in that it always returns an instance.
     *
     * @param  string $name - logical name; can be "self" to return a redirect forward to the mapping itself
     *
     * @return ActionForward
     *
     * @throws StrutsConfigException if the forward was not found
     */
    public function findForwardOrFail($name) {
        $forward = $this->findForward($name);
        if (!$forward)
            throw new StrutsConfigException('<mapping name="'.$this->getName().'"  path="'.$this->getPath().'": ActionForward "edit" not found.');
        return $forward;
    }
}
