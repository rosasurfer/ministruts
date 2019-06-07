<?php
namespace rosasurfer\di\defaultt;

use rosasurfer\console\io\Input;
use rosasurfer\console\io\Output;
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
            (new Service(Input::class,  Input::class ))->addAlias('input'),
            (new Service(Output::class, Output::class))->addAlias('output'),
        ];
        foreach ($services as $service) {
            $this->addService($service);
        }
        parent::__construct($configDir);
    }
}
