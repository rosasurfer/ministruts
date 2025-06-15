#!/usr/bin/env php
<?php

/**
 * CLI version of the framework's phpinfo() task.
 *
 * Checks the PHP runtime configuration and reports found issues.
 */
require __DIR__.'/../vendor/autoload.php';

echo PHP_EOL;
\rosasurfer\util\PHP::phpinfo();
echo PHP_EOL.'loaded php.ini: "'.php_ini_loaded_file().'"'.PHP_EOL;
