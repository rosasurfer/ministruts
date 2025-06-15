<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\auto;

use rosasurfer\ministruts\console\io\Output;
use rosasurfer\ministruts\core\di\Di;
use rosasurfer\ministruts\core\di\service\Service;
use rosasurfer\ministruts\struts\Request;

/**
 * Default dependency injector automatically created for web applications.
 *
 * A variant of the standard dependency injector {@link Di} suitable for web applications. Registers web app
 * related services provided by the framework and user-defined services loaded from the file "{app.dir.config}/services.php".
 */
class WebServiceContainer extends Di {

    /**
     * Constructor
     *
     * @param  string $configDir - directory to load service definitions from
     */
    public function __construct(string $configDir) {
        $services = [
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
