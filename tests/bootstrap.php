<?php
declare(strict_types=1);

use rosasurfer\ministruts\Application;

// register the class loader (needed if a global PHPUnit is used)
require __DIR__.'/../vendor/autoload.php';

// update "php.ini" settings
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

ini_set('error_log', __DIR__.'/../php-error.log');
ini_set('log_errors',         '1');
ini_set('log_errors_max_len', '0');
ini_set('memory_limit',      '-1');

// create an application to initialize the service container
$app = new Application();
