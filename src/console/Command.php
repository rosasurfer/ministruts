<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console;

use Closure;

use rosasurfer\ministruts\console\docopt\DocoptParser;
use rosasurfer\ministruts\console\docopt\DocoptResult;
use rosasurfer\ministruts\console\io\Input;
use rosasurfer\ministruts\console\io\Output;
use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\di\proxy\CliInput as InputProxy;
use rosasurfer\ministruts\core\di\proxy\Output as OutputProxy;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\RuntimeException;

use function rosasurfer\ministruts\preg_match;
use function rosasurfer\ministruts\simpleClassName;

/**
 * Command
 *
 * The configuration of a command will be frozen after it is added to the {@link \rosasurfer\ministruts\Application}.
 * A frozen configuration can't be changed anymore.
 */
class Command extends CObject {

    public const DOCOPT = '';

    /** @var string */
    private string $name = '';

    /** @var string[] */
    private array $aliases = [];

    /** @var string - syntax definition in Docopt format */
    private string $docoptDefinition;

    /** @var ?DocoptResult - parsed and matched Docopt block */
    private ?DocoptResult $docoptResult = null;

    /** @var ?Closure */
    private ?Closure $validator = null;

    /** @var ?Closure */
    private ?Closure $task = null;

    /** @var bool - whether the command configuration is frozen */
    private bool $frozen = false;

    /** @var Input */
    protected Input $input;

    /** @var Output */
    protected Output $output;

    /** @var int - the command's error status */
    protected int $status = 0;


    /**
     * Constructor
     *
     * Create a new command.
     */
    final public function __construct() {
        $this->input = InputProxy::instance();
        $this->output = OutputProxy::instance();
        $this->configure();

        if ($this->name === '') {
            $this->setName(simpleClassName($this));
        }
    }


    /**
     * Configures the command. Override this method to pre-define a custom configuration. All configuration properties are
     * optional and may also be set separately.
     *
     * @return $this
     */
    protected function configure(): self {
        if (strlen($docopt = $this::DOCOPT)) {
            $this->setDocoptDefinition($docopt);
        }
        return $this;
    }


    /**
     * Trigger execution of the command.
     *
     * @return int - execution status (0 for success)
     */
    public function run(): int {
        $input = $this->input;
        $output = $this->output;

        if ($this->docoptResult) {
            $input->setDocoptResult($this->docoptResult);
        }

        if ($this->validator) {
            $error = ($this->validator)($input, $output);
        }
        else {
            $error = $this->validate($input, $output);
        }

        if ($error) {
            return $this->status = (int)$error;
        }

        if ($this->task) {
            $status = ($this->task)($input, $output);
        }
        else {
            $status = $this->execute($input, $output);
        }

        return $this->status = (int)$status;
    }


    /**
     * Validate the command line arguments. Override this method to pre-define a custom validation or
     * set the validator dynamically via {@link Command::setValidator()}.
     *
     * @param  Input  $input
     * @param  Output $output
     *
     * @return int - validation error status (0 for no error)
     */
    protected function validate(Input $input, Output $output): int {
        return 0;
    }


    /**
     * Execute the command.
     *
     * Override this method to define a command implementation or set it dynamically via {@link Command::setTask()}.
     *
     * @param  Input  $input
     * @param  Output $output
     *
     * @return int - execution status (0 for success)
     */
    protected function execute(Input $input, Output $output): int {
        return 0;
    }


    /**
     * Return the name of the command.
     *
     * @return string
     */
    public function getName(): string {
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
    public function setName(string $name): self {
        if ($this->frozen) throw new RuntimeException('Configuration of '.static::class.' is frozen');

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
    public function getAliases(): array {
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
    public function setAliases(array $names): self {
        if ($this->frozen)      throw new RuntimeException('Configuration of '.static::class.' is frozen');
        if ($this->name === '') throw new IllegalStateException('A default command (name="") cannot have aliases');

        foreach ($names as $alias) {
            $this->validateName($alias);
        }
        $this->aliases = array_diff($names, [$this->name]);     // remove an overlapping command name
        return $this;
    }


    /**
     * Return the command's Docopt definition.
     *
     * @return string - syntax definition in Docopt format
     */
    public function getDocoptDefinition(): string {
        return $this->docoptDefinition;
    }


    /**
     * Set the command's Docopt definition.
     *
     * @param  string $doc - syntax definition in Docopt format
     *
     * @return $this
     *
     * @link   https://docopt.org/
     */
    public function setDocoptDefinition(string $doc): self {
        if ($this->frozen) throw new RuntimeException('Configuration of '.static::class.' is frozen');

        $self = basename($_SERVER['PHP_SELF']);
        $doc = str_replace('{:cmd:}', $self, $doc);

        $parser = new DocoptParser();
        $this->docoptResult = $parser->parse($doc);

        $this->docoptDefinition = $doc;
        return $this;
    }


    /**
     * Return the command's dynamic validation implementation.
     *
     * @return ?Closure
     */
    public function getValidator(): ?Closure {
        return $this->validator;
    }


    /**
     * Set the command's dynamic validation implementation. When a command is executed a dynamic validator is given higher
     * priority than a pre-defined implementation in an overridden {@link Command::validate()} method.
     *
     * @param  Closure $validator
     *
     * @return $this
     */
    public function setValidator(Closure $validator): self {
        $this->validator = $validator->bindTo($this);
        return $this;
    }


    /**
     * Return the command's dynamic task implementation.
     *
     * @return ?Closure
     */
    public function getTask(): ?Closure {
        return $this->task;
    }


    /**
     * Set the command's dynamic task implementation. When a command is executed a dynamic task is given higher priority than
     * a pre-defined implementation in an overridden {@link Command::execute()} method.
     *
     * @param  Closure $task
     *
     * @return $this
     */
    public function setTask(Closure $task): self {
        $this->task = $task->bindTo($this);
        return $this;
    }


    /**
     * Validate the command configuration and lock its configuration. Called after the command
     * is added to the {@link \rosasurfer\ministruts\Application}.
     *
     * @return $this
     */
    final public function freeze(): self {
        if (!$this->frozen) {
            if ($this->name === '')   throw new IllegalStateException('Incomplete command configuration: no name');
            if (!$this->docoptResult) throw new IllegalStateException('Incomplete command configuration: no Docopt definition');
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
    private function validateName(string $name): self {
        if ($name != trim($name)) throw new InvalidValueException("Invalid parameter \$name: \"$name\" (enclosing white space)");

        if (strlen($name) && !preg_match('/^[^\s:]+(:[^\s:]+)*$/', $name)) {
            throw new InvalidValueException("Invalid parameter \$name: \"$name\" (not a command name)");
        }
        return $this;
    }
}
