<?php
use rosasurfer\MiniStruts;
use rosasurfer\util\PHP;


// check app configuration
!defined('APPLICATION_ROOT') && define('APPLICATION_ROOT', dirname(__DIR__));
!defined('APPLICATION_ID'  ) && define('APPLICATION_ID',  'example-app');


// load Composer
require(APPLICATION_ROOT.'/etc/vendor/autoload.php');


// global settings
error_reporting(E_ALL);
PHP::ini_set('error_log', APPLICATION_ROOT.'/etc/log/php-error.log');


// initialize Ministruts
MiniStruts::init([
    'config'  => APPLICATION_ROOT.'/app/config',
    'globals' => true,
]);
