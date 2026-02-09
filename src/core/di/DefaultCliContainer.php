<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di;

use rosasurfer\ministruts\console\io\Input;
use rosasurfer\ministruts\console\io\Output;
use rosasurfer\ministruts\core\di\service\Service;

/**
 * Default dependency container automatically created for command-line applications.
 *
 * A variant of {@link Container} suitable for CLI applications. Registers CLI related dependencies provided by
 * the framework and user-defined dependencies loaded from file "{app.dir.config}/services.php".
 */
class DefaultCliContainer extends Container {

    /**
     * Constructor
     *
     * @param  string $configDir - directory to load custom dependency definitions from
     */
    public function __construct(string $configDir) {
        $services = [
            (new Service('input',  Input::class))->addAlias(Input::class),
            (new Service('output', Output::class))->addAlias(Output::class),
        ];
        foreach ($services as $service) {
            $this->addService($service);
        }
        parent::__construct($configDir);
    }
}
