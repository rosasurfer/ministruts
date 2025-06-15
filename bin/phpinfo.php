#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace rosasurfer\ministruts;

use rosasurfer\ministruts\util\PHP;

/**
 * CLI version of the framework's phpinfo() task.
 *
 * Checks the PHP runtime configuration and reports found issues.
 */
require __DIR__.'/../vendor/autoload.php';

// initialize a new application
PHP::ini_set('log_errors',         '1'                        );
PHP::ini_set('log_errors_max_len', '0'                        );
PHP::ini_set('error_log',          __DIR__.'/../php-error.log');

new Application(['app.dir.root' => __DIR__.'/..']);

// call phpInfo() task
echo PHP_EOL;
PHP::phpinfo();
$iniFile = \php_ini_loaded_file();
echo PHP_EOL."loaded php.ini: \"$iniFile\"".PHP_EOL;
