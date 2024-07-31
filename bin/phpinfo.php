#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * CLI version of the framework's phpinfo() task.
 *
 * Checks the PHP runtime configuration and reports found issues.
 */
if (!is_file($autoload = __DIR__.'/../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}

require($autoload);

echo PHP_EOL;
\rosasurfer\ministruts\util\PHP::phpinfo();
echo PHP_EOL.'loaded php.ini: "'.php_ini_loaded_file().'"'.PHP_EOL;
