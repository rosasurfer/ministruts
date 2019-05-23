<?php
namespace rosasurfer\di\defaultt;

use rosasurfer\console\io\Input;
use rosasurfer\console\io\Output;
use rosasurfer\di\Di;
use rosasurfer\di\service\Service;


/**
 * Default DI automatically created for command-line interface applications.
 *
 * A variant of the standard {@link Di} especially suitable for CLI applications. It automatically registers all the
 * services provided by the framework and loads user-defined services from the file "{app.dir.config}/services.php".
 */
class DefaultCliDi extends Di {


    /**
     * Constructor
     *
     * @param  string $configDir - directory to load custom service definitions from
     */
    public function __construct($configDir) {
        parent::__construct();

        $defaultServices = [
            // an empty default Input service without a docopt definition
            Input::class          => new Service(Input::class      , Input::class                              ),

            Output::class         => new Service(Output::class     , Output::class                             ),
            // 'frontController'  => new Service('frontController' , 'rosasurfer\\ministruts\\FrontController' ),
            // 'request'          => new Service('request'         , 'rosasurfer\\ministruts\\Request'         ),
            // 'requestProcessor' => new Service('requestProcessor', 'rosasurfer\\ministruts\\RequestProcessor'),
        ];
        $this->services = array_merge($this->services, $defaultServices);

        $this->loadCustomServices($configDir);
    }
}
