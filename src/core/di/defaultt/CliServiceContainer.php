<?php
namespace rosasurfer\core\di\defaultt;

use rosasurfer\console\io\CliInput;
use rosasurfer\console\io\Output;
use rosasurfer\core\di\Di;
use rosasurfer\core\di\service\Service;


/**
 * Default dependency injector automatically created for command-line applications.
 *
 * A variant of the standard dependency injector {@link Di} suitable for CLI applications. Registers CLI related services
 * provided by the framework and user-defined services loaded from the file "{app.dir.config}/services.php".
 */
class CliServiceContainer extends Di {


    /**
     * Constructor
     *
     * @param  string $configDir - directory to load custom service definitions from
     */
    public function __construct($configDir) {
        $services = [
            (new Service('input',  CliInput::class))->addAlias(CliInput::class),
            (new Service('output', Output::class  ))->addAlias(Output::class),
        ];
        foreach ($services as $service) {
            $this->addService($service);
        }
        parent::__construct($configDir);
    }
}
