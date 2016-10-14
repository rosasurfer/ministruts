#!/usr/bin/php
<?php
use rosasurfer\MiniStruts;
use rosasurfer\util\PHP;


/**
 * Command line version of phpinfo.php
 */
error_reporting(E_ALL & ~E_DEPRECATED);
!defined('APPLICATION_ROOT') && define('APPLICATION_ROOT', dirname(__DIR__));
ini_set('error_log', APPLICATION_ROOT.'/etc/log/php_error.log');


// configure and load the framework
require(APPLICATION_ROOT.'/src/load.php');
$options = [
   'global-helpers'    => true,
   'handle-errors'     => MiniStruts::THROW_EXCEPTIONS,
   'handle-exceptions' => true,
];
MiniStruts::init($options);


// call phpInfo();
PHP::phpInfo();