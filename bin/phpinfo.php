#!/usr/bin/env php
<?php
namespace rosasurfer\bin\phpinfo;

use rosasurfer\util\PHP;
use function rosasurfer\echoPre;
use const rosasurfer\NL;


/**
 * Command line version of phpInfo()
 */
require(__DIR__.'/../src/load.php');

PHP::phpInfo();

echoPre(NL.'loaded php.ini: "'.php_ini_loaded_file().'"');
