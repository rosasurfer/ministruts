<?php
namespace rosasurfer\console;

use rosasurfer\Application;
use rosasurfer\console\docopt\DocoptParser;
use rosasurfer\console\docopt\DocoptResult;
use rosasurfer\console\input\Input;
use rosasurfer\core\Object;
use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;


/**
 * Command
 *
 * The configuration of a command is frozen after it is added to the {@link Application} or when the command is executed,
 * whichever comes first. A frozen configuration can't be changed anymore.
 */
class Command extends Object {


    /** @var string */
    private $name;

    /** @var string[] */
    private $aliases = [];

    /** @var string - syntax definition in docopt format */
    private $docoptDefinition;

    /** @var DocoptResult - parsed and matched docopt block */
    private $docoptResult;

    /** @var bool - whether the command configuration is frozen */
    private $frozen = false;


    /**
     * Constructor
     *
     * Create a new command and optionally set its name. The name may also be set separately.
     *
     * @param  string $name [optional] - command name (default: none)
     *
     * @see    Command::setName()
     */
    public function __construct($name = null) {
        if (isset($name))
            $this->setName($name);
        $this->configure();
    }


    /**
     * Configures the command. Override this method to pre-define a custom configuration.
     *
     * @return $this
     */
    protected function configure() {
        return $this;
    }


    /**
     * Trigger execution of the command.
     *
     * @return int - execution status code: 0 (zero) for "success"
     */
    public function run() {
        $input = new Input($this->docoptResult);
        return $this->execute($input);
    }


    /**
     * Execute the command. Override this method to pre-define a custom command implementation.
     *
     * @param  Input $input
     *
     * @return int - execution status code: 0 (zero) for "success"
     */
    protected function execute(Input $input) {
        throw new IllegalStateException('Override this method to pre-define a custom command implementation.');
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
     * If an empty string is passed the command is marked as the default command of the CLI application.
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
        if ($this->frozen)    throw new RuntimeException('Configuration of "'.get_class($this).'" is frozen');
        if (!is_string($doc)) throw new IllegalTypeException('Illegal type of parameter $doc: '.gettype($doc));

        $parser = new DocoptParser();
        $this->docoptResult = $parser->parse($doc);

        $this->docoptDefinition = $doc;
        return $this;
    }


    /**
     * Validate the command configuration and lock its configuration. Called after the command is added to the
     * {@link Application} or when the command is executed, whichever comes first.
     *
     * @return $this
     */
    final public function freeze() {
        if (!$this->frozen) {
            $this->validate();
            $this->frozen = true;
        }
        return $this;
    }


    /**
     * Validate the command configuration.
     *
     * @return $this
     *
     * @throws IllegalStateException if the command configuration is incomplete
     */
    private function validate() {
        if (!isset($this->name))         throw new IllegalStateException('Incomplete command configuration: no name');
        if (!isset($this->docoptResult)) throw new IllegalStateException('Incomplete command configuration: no docopt definition');
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
        if (!is_string($name))    throw new IllegalTypeException('Illegal type of parameter $name: '.gettype($name));
        if ($name != trim($name)) throw new InvalidArgumentException('Invalid parameter $name: "'.$name.'" (enclosing white space)');

        if (strlen($name) && !preg_match('/^[^\s:]+(:[^\s:]+)*$/', $name))
            throw new InvalidArgumentException('Invalid parameter $name: "'.$name.'" (not a command name)');
        return $this;
    }
}
