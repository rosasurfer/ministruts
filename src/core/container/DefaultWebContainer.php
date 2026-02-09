<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\container;

use rosasurfer\ministruts\console\io\Output;
use rosasurfer\ministruts\core\container\service\Service;
use rosasurfer\ministruts\struts\Request;

/**
 * Default dependency container automatically created for web applications.
 *
 * A variant of {@link Container} suitable for web applications. Registers web app related dependencies provided by
 * the framework and user-defined services loaded from file "{app.dir.config}/services.php".
 */
class DefaultWebContainer extends Container {

    /**
     * Constructor
     *
     * @param  string $configDir - directory to load dependency definitions from
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
