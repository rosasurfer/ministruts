<?php
namespace rosasurfer\di\defaultt;

use rosasurfer\core\io\Output;
use rosasurfer\core\io\cli\Input;
use rosasurfer\di\Di;
use rosasurfer\di\service\Service;


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
            (new Service('input',  Input::class ))->addAlias(Input::class),
            (new Service('output', Output::class))->addAlias(Output::class),
        ];
        foreach ($services as $service) {
            $this->addService($service);
        }
        parent::__construct($configDir);
    }
}
