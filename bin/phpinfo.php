#!/usr/bin/env php
<?php
/**
 * CLI version of the application's phpinfo() task accessible at "http://{application}/{any-url}?__phpinfo__".
 *
 * Checks PHP runtime configuration and reports found issues.
 */
$dir = dirname(realpath(__FILE__));                             // TODO: adjust init file to your project
if (!is_file($initFile = $dir.'/../app/init.php')) $initFile = $dir.'/../src/load.php';
require($initFile);

\rosasurfer\ministruts\util\PHP::phpinfo();
echo PHP_EOL.'loaded php.ini: "'.php_ini_loaded_file().'"'.PHP_EOL;
