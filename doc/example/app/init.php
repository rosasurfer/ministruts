<?php
if (PHP_VERSION_ID < 50600) exit('[FATAL] This application requires PHP >= 5.6');

use rosasurfer\MiniStruts;
use rosasurfer\util\PHP;


// check app configuration
!defined('APPLICATION_ROOT') && define('APPLICATION_ROOT', dirname(__DIR__));
!defined('APPLICATION_ID'  ) && define('APPLICATION_ID',  'example');


// load Composer
require(APPLICATION_ROOT.'/etc/vendor/autoload.php');


// global settings
error_reporting(E_ALL);
PHP::ini_set('error_log', APPLICATION_ROOT.'/etc/log/php-error.log');


// initialize Ministruts
$options = [
   'config'            => APPLICATION_ROOT.'/app/config',
   'handle-errors'     => MiniStruts::THROW_EXCEPTIONS,
   'handle-exceptions' => true,
   'global-helpers'    => true,
];
MiniStruts::init($options);
