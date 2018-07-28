#!/usr/bin/env php56
<?php
/**
 * Copy this file to your project's bin directory and point line 11 to your application's init script.
 *
 *
 * Command line version of the application's phpInfo() task accessible at http://{project-url}/?__phpinfo__.
 */
use rosasurfer\util\PHP;

require(dirName(realPath(__FILE__)).'/../app/init.php');

PHP::phpInfo();
echo PHP_EOL.'loaded php.ini: "'.php_ini_loaded_file().'"'.PHP_EOL;
