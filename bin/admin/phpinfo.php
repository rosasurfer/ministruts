#!/usr/bin/env php
<?php
/**
 * --- TEMPLATE -------------------------------------------------------------------------------------------------------------
 *
 * Copy this file to your project's "bin" directory and point line 16 to your application's real init script.
 *
 * --------------------------------------------------------------------------------------------------------------------------
 *
 *
 * Command line version of the application's web interface phpinfo() task accessible via
 * "http://{application}/{any-url}?__phpinfo__"
 */
use rosasurfer\util\PHP;

require(dirname(realpath(__FILE__)).'/../app/init.php');

PHP::phpinfo();
echo PHP_EOL.'loaded php.ini: "'.php_ini_loaded_file().'"'.PHP_EOL;
