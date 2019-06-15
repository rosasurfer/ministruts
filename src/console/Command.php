<?php
namespace rosasurfer\console;

use rosasurfer\Application;
use rosasurfer\console\docopt\DocoptParser;
use rosasurfer\console\docopt\DocoptResult;
use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\IllegalStateException;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\core\io\Output;
use rosasurfer\core\io\cli\Input;
use rosasurfer\di\Di;


/**
 * Command
 *
 * The configuration of a command will be frozen after it is added to the {@link Application}. A frozen configuration
 * can't be changed anymore.
 */
class Command extends CObject {


    /** @var string */
    private $name = '';

    /** @var string[] */
    private $aliases = [];

    /** @var string - syntax definition in docopt format */
    private $docoptDefinition;

    /** @var DocoptResult - parsed and matched docopt block */
    private $docoptResult;

    /** @var \Closure */
    private $validator;

    /** @var \Closure */
    private $task;

    /** @var bool - whether the command configuration is frozen */
    private $frozen = false;

    /** @var int - the command's error status */
    protected $status = 0;


    /**
     * Constructor
     *
     * Create a new command.
     */
    public function __construct() {
        $this->configure();
    }


    /**
     * Configures the command. Override this method to pre-define a custom configuration. All configuration properties are
     * optional and may also be set separately.
     *
     * @return $this
     */
    protected function configure() {
        return $this;
    }


    /**
     * Trigger execution of the command.
     *
     * @return int - execution status (0 for success)
     */
    public function run() {
        /** @var Input $input */
        $input = $this->di('input');
        $input->setDocoptResult($this->docoptResult);

        /** @var Output $output */
        $output = $this->di('output');

        if ($this->validator) $error = $this->validator->__invoke($input, $output);
        else                  $error = $this->validate($input, $output);

        if ($error)
            return $this->status = (int) $error;

        if ($this->task) $status = $this->task->__invoke($input, $output);
        else             $status = $this->execute($input, $output);

        return $this->status = (int) $status;
    }


    /**
     * Validate the command line arguments. Override this method to pre-define custom validation or set the validator
     * dynamically via {@link Command::setValidator()}.
     *
     * @param  Input  $input
     * @param  Output $output
     *
     * @return int - validation error status (0 for no error)
     */
    protected function validate(Input $input, Output $output) {
        return 0;
    }


    /**
     * Execute the command. Override this method to pre-define a command implementation or set it dynamically via
     * {@link Command::setTask()}.
     *
     * @param  Input  $input
     * @param  Output $output
     *
     * @return int - execution status (0 for success)
     */
    protected function execute(Input $input, Output $output) {
        return 0;
    }


    /**
     * Return the name of the command.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * Set the name of the command. A name must not contain white space and may define namespaces by using a colon ":".
     * An empty string as the name marks the default command of the CLI application.
     *
     * @param  string $name - e.g. "foo" or "bar:baz"
     *
     * @return $this
     */
    public function setName($name) {
        if ($this->frozen) throw new RuntimeException('Configuration of "'.get_class($this).'" is frozen');

        $this->validateName($name);
        $this->name = $name;

        if ($name === '') {
            $this->aliases = [];                                    // a default command cannot have aliases
        }
        else {
            $this->aliases = array_diff($this->aliases, [$name]);   // remove overlapping aliases
        }
        return $this;
    }


    /**
     * Return the aliases of the command.
     *
     * @return string[]
     */
    public function getAliases() {
        return $this->aliases;
    }


    /**
     * Set the alias names of the command if the command has a non-empty name. A command with an empty name is considered
     * the default command and cannot have aliases.
     *
     * @param  string[] $names
     *
     * @return $this
     */
    public function setAliases(array $names) {
        if ($this->frozen)      throw new RuntimeException('Configuration of "'.get_class($this).'" is frozen');
        if ($this->name === '') throw new IllegalStateException('A default command (name="") cannot have aliases');

        foreach ($names as $i => $alias) {
            $this->validateName($alias);
        }
        $this->aliases = array_diff($names, [$this->name]);     // remove an overlapping command name
        return $this;
    }


    /**
     * Return the command's docopt definition.
     *
     * @return string - syntax definition in docopt format
     */
    public function getDocoptDefinition() {
        return $this->docoptDefinition;
    }


    /**
     * Set the command's docopt definition.
     *
     * @param  string $doc - syntax definition in docopt format
     *
     * @return $this
     *
     * @link   http://docopt.org
     */
    public function setDocoptDefinition($doc) {
        if ($this->frozen) throw new RuntimeException('Configuration of "'.get_class($this).'" is frozen');
        Assert::string($doc);

        $parser = new DocoptParser();
        $this->docoptResult = $parser->parse($doc);

        $this->docoptDefinition = $doc;
        return $this;
    }


    /**
     * Return the command's dynamic validation implementation.
     *
     * @return \Closure|null
     */
    public function getValidator() {
        return $this->validator;
    }


    /**
     * Set the command's dynamic validation implementation. When a command is executed a dynamic validator is given higher
     * priority than a pre-defined implementation in an overridden {@link Command::validate()} method.
     *
     * @param  \Closure $validator
     *
     * @return $this
     */
    public function setValidator(\Closure $validator) {
        $this->validator = $validator->bindTo($this);
        return $this;
    }


    /**
     * Return the command's dynamic task implementation.
     *
     * @return \Closure|null
     */
    public function getTask() {
        return $this->task;
    }


    /**
     * Set the command's dynamic task implementation. When a command is executed a dynamic task is given higher priority than
     * a pre-defined implementation in an overridden {@link Command::execute()} method.
     *
     * @param  \Closure $task
     *
     * @return $this
     */
    public function setTask(\Closure $task) {
        $this->task = $task->bindTo($this);
        return $this;
    }


    /**
     * Validate the command configuration and lock its configuration. Called after the command is added to the
     * {@link Application}.
     *
     * @return $this
     */
    final public function freeze() {
        if (!$this->frozen) {
            if (!isset($this->name))         throw new IllegalStateException('Incomplete command configuration: no name');
            if (!isset($this->docoptResult)) throw new IllegalStateException('Incomplete command configuration: no docopt definition');
            $this->frozen = true;
        }
        return $this;
    }


    /**
     * Validate a command name.
     *
     * @param  string $name
     *
     * @return $this
     */
    private function validateName($name) {
        Assert::string($name);
        if ($name != trim($name)) throw new InvalidArgumentException('Invalid parameter $name: "'.$name.'" (enclosing white space)');

        if (strlen($name) && !preg_match('/^[^\s:]+(:[^\s:]+)*$/', $name))
            throw new InvalidArgumentException('Invalid parameter $name: "'.$name.'" (not a command name)');
        return $this;
    }
}
