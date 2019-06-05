<?php
namespace rosasurfer\di\defaultt;

use rosasurfer\console\io\Input;
use rosasurfer\console\io\Output;
use rosasurfer\di\Di;
use rosasurfer\di\service\Service;
use rosasurfer\ministruts\Request;
use rosasurfer\ministruts\RequestProcessor;


/**
 * Default dependency injector automatically created for web applications.
 *
 * A variant of the standard dependency injector {@link Di} suitable for web applications. Registers web app related services
 * provided by the framework and user-defined services loaded from the file "{app.dir.config}/services.php".
 */
class WebServiceContainer extends Di {


    /**
     * Constructor
     *
     * @param  string $configDir - directory to load custom service definitions from
     */
    public function __construct($configDir) {
        $services = [
            (new Service(Input::class,            Input::class           ))->addAlias('input'),
            (new Service(Output::class,           Output::class          ))->addAlias('output'),
          //(new Service(Request::class,          Request::class         ))->addAlias('request'),
          //(new Service(RequestProcessor::class, RequestProcessor::class))->addAlias('requestProcessor'),
        ];
        foreach ($services as $service) {
            $this->registerService($service);
        }
        parent::__construct($configDir);
    }
}
