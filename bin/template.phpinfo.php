#!/usr/bin/env php
<?php
/**
 * Command line version of phpInfo()
 */
use rosasurfer\Application;
use rosasurfer\util\PHP;

$root = dirName(dirName(realPath(__FILE__)));
require($root.'/src/load.php');


// php.ini settings
error_reporting(E_ALL & ~E_DEPRECATED);
PHP::ini_set('error_log', $root.'/php-error.log');


$app = new Application();                               // creating an application loads the configuration

PHP::phpInfo();
echo PHP_EOL.'loaded php.ini: "'.php_ini_loaded_file().'"'.PHP_EOL;
