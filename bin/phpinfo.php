#!/usr/bin/php
<?php
use rosasurfer\MiniStruts;
use rosasurfer\util\PHP;


/**
 * Command line version of phpinfo.php
 */
!defined('APPLICATION_ROOT') && define('APPLICATION_ROOT', dirname(__DIR__));
require(APPLICATION_ROOT.'/src/load.php');
$options = [
   'global-helpers'    => true,
   'handle-errors'     => MiniStruts::THROW_EXCEPTIONS,
   'handle-exceptions' => true,
];
MiniStruts::init($options);


// call phpInfo();
PHP::info();
