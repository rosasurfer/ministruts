#!/usr/bin/env php
<?php
/**
 * Command line version of phpInfo()
 */
use rosasurfer\Application;
use rosasurfer\util\PHP;

require(($dir=dirName(realPath(__FILE__))).'/../src/load.php');


// php.ini settings
error_reporting(E_ALL & ~E_DEPRECATED);
PHP::ini_set('error_log', $dir.'/../php-error.log');


$app = new Application();


PHP::phpInfo();
echo PHP_EOL.'loaded php.ini: "'.php_ini_loaded_file().'"'.PHP_EOL;
