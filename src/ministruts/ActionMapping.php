<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\IllegalStateException;
use rosasurfer\core\proxy\Request as RequestProxy;

use function rosasurfer\strCompareI;
use function rosasurfer\strLeftTo;


/**
 * ActionMapping
 *
 * An ActionMapping encapsulates the processing instructions for a single route. For in-depth documentation of properties
 * and configuration see the following link:
 *
 * @link  https://github.com/rosasurfer/ministruts/blob/master/src/ministruts/dtd/struts-config.dtd#L137
 */
class ActionMapping extends CObject {


    /** @var bool - whether this component is fully configured */
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
     * @param  Module $module - application module the mapping belongs to
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
        if (!strlen($name=trim($name))) throw new StrutsConfigException('<mapping name="'.func_get_arg(0).'"'.($this->path ? ' path="'.$this->path.'"':'').': Illegal name (empty value).');

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
     * Whether the mapping is configured to handle requests of the specified HTTP method.
     *
     * @param  string $method - HTTP method verb
     *
     * @return bool
     */
    public function isSupportedMethod($method) {
        Assert::string($method);
        return isset($this->methods[strtoupper($method)]);
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

        $method = strtoupper($method);
        if ($method!='GET' && $method!='POST') throw new StrutsConfigException('<mapping'.$name.''.$path.' methods="'.func_get_arg(0).'":  Invalid HTTP method.');

        $this->methods[$method] = true;
        return $this;
    }


    /**
     * Return the mapping's role constraint. Depending on the used {@link RoleProcessor} this may be a single role identifier
     * or a logical expression (possibly referencing multiple roles).
     *
     * @return string|null - role constraint or NULL if no role constraint is defined
     */
    public function getRoles() {
        return $this->roles;
    }


    /**
     * Set the mapping's role constraint.
     *
     * @param  string $roles - role constraint or expression
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
        if (!strlen($roles) || !preg_match($pattern, $roles)) throw new StrutsConfigException('<mapping'.$name.$path.' roles="'.$roles.'": Invalid roles expression.');

        // check for invalid id combinations, e.g. "Member,!Member"
        $tokens = explode(',', $roles);
        $keys = \array_flip($tokens);

        foreach ($tokens as $role) {
            if (isset($keys['!'.$role])) throw new StrutsConfigException('<mapping'.$name.$path.' roles="'.$roles.'": Invalid roles expression.');
        }

        // remove duplicates
        $this->roles = join(',', \array_flip($keys));
        return $this;
    }


    /**
     * Configure the mapping to use an {@link ActionForward} instead of passing processing to an {@link Action}.
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
     * Set the scope attribute of the mapping's {@link ActionForm}. The scope attribute identifies the storage location of
     * the Actionform.
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
     * Return the scope attribute of the mapping's {@link ActionForm}.
     *
     * @return string - scope attribute value
     */
    public function getFormScope() {
        return $this->formScope;
    }


    /**
     * Whether the mapping's {@link ActionForm} is stored in the {@link Request}.
     *
     * @return bool
     */
    public function isRequestScope() {
        return ($this->formScope == 'request');
    }


    /**
     * Whether the mapping's {@link ActionForm} is stored in the {@link HttpSession}.
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
     * Whether the mapping's {@link ActionForm} is validated by the framework before execution of the the {@link Action}.
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
     * Whether the mapping's "default" flag is set.
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

        if (isset($this->forwards[$name])) throw new StrutsConfigException('<mapping'.$mapping.'> <forward name="'.$name.'": Non-unique forward identifier.');

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

        if (!$this->path)                                      throw new StrutsConfigException('<mapping'.$mapping.': A "path" attribute must be configured for '.$this);
        if (!$this->formClassName && $this->formValidateFirst) throw new StrutsConfigException('<mapping'.$mapping.': A form must be configured if "form-validate-first" is set to "true".');

        if (!$this->actionClassName && !$this->forward) {
            // In general either an "action" or a "forward" resource is required to process a request. Except if a "form"
            // is configured as "form-validate-first" (implicit or explicit). Only in that case the "forward" resource is
            // looked-up from child elements "forward[@name=error|success]" and the mapping may define neither an "action"
            // nor a "forward".

            if (!$this->formClassName || !$this->formValidateFirst)
                throw new StrutsConfigException('<mapping'.$mapping.': Either an "action", "include", "redirect" or "forward" attribute must be specified.');

            if (!isset($this->forwards[ActionForward::VALIDATION_SUCCESS_KEY]) || !isset($this->forwards[ActionForward::VALIDATION_ERROR_KEY]))
                throw new StrutsConfigException('<mapping'.$mapping.' form="'.$this->formClassName.'": A "success" and "error" forward must be configured to automatically validate the form.');
        }

        $this->configured = true;
        return $this;
    }


    /**
     * Lookup and return the {@link ActionForward} accessible under the specified name. First the lookup tries to find a
     * local forward of the specified name. If no such forward is found global forwards are checked. This method returns NULL
     * if no forward is found.
     *
     * @param  string $name - logical name; can be "self" to return a redirect forward to the mapping itself
     *
     * @return ActionForward|null - ActionForward or NULL if no such forward was found
     */
    public function findForward($name) {
        $forward = null;

        if (isset($this->forwards[$name])) {
            $forward = $this->forwards[$name];
        }
        else if (strCompareI($name, ActionForward::SELF)) {
            $name = ActionForward::SELF;
            $path = $this->path;
            $class = $this->module->getForwardClass();
            /** @var ActionForward $forward */
            $forward = new $class($name, $path, true);
        }
        else {
            return $this->module->findForward($name);
        }

        if ($forward->getName() == ActionForward::SELF) {
            if ($this->configured) {                            // at runtime only: append the request's query string
                $path = $this->path;                            // TODO: Don't lose additional path data. Example:
                $query = RequestProxy::getQueryString();        //       /path/beautified-url-data/?query-string
                if (strlen($query))
                    $path = strLeftTo($path, '?').'?'.$query;
                $forward->setPath($path);
            }
        }
        return $forward;
    }


    /**
     * Return the mapping's configured {@link ActionForward} or lookup and return the forward accessible under the specified
     * name. First the lookup tries to find a local forward of the specified name. If no such forward is found global
     * forwards are checked. This method throws an exception if a name was specified but no such forward was found.
     *
     * @param  string $name [optional] - logical name; can be "self" to return a redirect forward to the mapping itself
     *                                   (default: none)
     *
     * @return ActionForward|null - ActionForward or NULL if no name was specified and no forward is configured
     *
     * @throws StrutsConfigException if a name was specified but no such forward was found
     */
    public function getForward($name = null) {
        if (!func_num_args())
            return $this->forward;

        $forward = $this->findForward($name);
        if (!$forward)
            throw new StrutsConfigException('<mapping name="'.$this->getName().'"  path="'.$this->getPath().'": ActionForward "'.$name.'" not found.');
        return $forward;
    }
}
