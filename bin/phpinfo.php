#!/usr/bin/php
<?php
namespace rosasurfer\bin\phpinfo;

use rosasurfer\MiniStruts;
use rosasurfer\util\PHP;


/**
 * Command line version of phpInfo()
 */
!defined('APPLICATION_ROOT') && define('APPLICATION_ROOT', dirname(__DIR__));
require(APPLICATION_ROOT.'/src/load.php');
MiniStruts::init(['globals' => true]);

PHP::phpInfo();
