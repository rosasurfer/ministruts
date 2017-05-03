#!/usr/bin/env php
<?php
/**
 * Command line version of phpInfo()
 */
require(__DIR__.'/../src/load.php');

rosasurfer\util\PHP::phpInfo();
echo PHP_EOL.'loaded php.ini: "'.php_ini_loaded_file().'"'.PHP_EOL;
