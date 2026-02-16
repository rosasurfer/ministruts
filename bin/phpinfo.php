#!/usr/bin/env php
<?php
/**
 * CLI version of the framework's phpinfo() task. Checks the PHP runtime configuration and reports found issues.
 */
declare(strict_types=1);

namespace rosasurfer\ministruts;

use rosasurfer\ministruts\util\PHP;

require_once __DIR__.'/../vendor/autoload.php';

// initialize a new application
new Application([
    'app.dir.root' => __DIR__.'/..',
]);

// call phpinfo() task
echo PHP_EOL;
PHP::phpinfo();
$iniFile = php_ini_loaded_file();
echo PHP_EOL."loaded php.ini: \"$iniFile\"".PHP_EOL.PHP_EOL;
