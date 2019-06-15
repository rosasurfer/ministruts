<?php
namespace rosasurfer\di\defaultt;

use rosasurfer\core\io\Input;
use rosasurfer\core\io\Output;
use rosasurfer\di\Di;
use rosasurfer\di\service\Service;
use rosasurfer\ministruts\Request;
use rosasurfer\ministruts\RequestProcessor;


/**
 * Default dependency injector automatically created for web applications.
 *
 * A variant of the standard dependency injector {@link Di} suitable for web applications. Registers web app related
 * services provided by the framework and user-defined services loaded from the file "{app.dir.config}/services.php".
 */
class WebServiceContainer extends Di {


    /**
     * Constructor
     *
     * @param  string $configDir - directory to load service definitions from
     */
    public function __construct($configDir) {
        $services = [
            (new Service('input',   Input::class  ))->addAlias(Input::class),
            (new Service('output',  Output::class ))->addAlias(Output::class),
            (new Service('request', Request::class))->addAlias(Request::class),
          //(new Service('requestProcessor', RequestProcessor::class))->addAlias(RequestProcessor::class),
        ];
        foreach ($services as $service) {
            $this->addService($service);
        }
        parent::__construct($configDir);
    }
}
