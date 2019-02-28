<?php
namespace rosasurfer\di\auto;

use rosasurfer\console\io\Output;
use rosasurfer\di\Di;
use rosasurfer\di\service\Service;


/**
 * Default DI automatically created for web applications.
 *
 * A variant of the standard {@link Di}. It automatically registers all the services provided by the framework and loads
 * user-defined services from the file "{app.dir.config}/services.php".
 */
class DefaultDi extends Di {


    /**
     * Constructor
     *
     * @param  string $configDir - directory to load custom service definitions from
     */
    public function __construct($configDir) {
        parent::__construct();

        $defaultServices = [
            Output::class         => new Service(Output::class     , Output::class                             ),
            // 'frontController'  => new Service('frontController' , 'rosasurfer\\ministruts\\FrontController' ),
            // 'request'          => new Service('request'         , 'rosasurfer\\ministruts\\Request'         ),
            // 'requestProcessor' => new Service('requestProcessor', 'rosasurfer\\ministruts\\RequestProcessor'),
        ];
        $this->services = array_merge($this->services, $defaultServices);

        $this->loadCustomServices($configDir);
    }
}
