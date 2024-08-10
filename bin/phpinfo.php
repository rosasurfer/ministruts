#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace rosasurfer\ministruts;

/**
 * CLI version of the framework's phpinfo() task.
 *
 * Checks the PHP runtime configuration and reports found issues.
 */
if (!\is_file($autoload = __DIR__.'/../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".NL;
    exit(1);
}

require($autoload);

echo NL;
\rosasurfer\ministruts\util\PHP::phpinfo();
echo NL.'loaded php.ini: "'.\php_ini_loaded_file().'"'.NL;
