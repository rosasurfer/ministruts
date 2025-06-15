<?php
declare(strict_types=1);

use rosasurfer\ministruts\Application;

// make sure the class loader is registered (in case a global phpunit.phar is used)
require __DIR__.'/../vendor/autoload.php';

// php.ini settings
error_reporting(E_ALL & ~E_DEPRECATED);

ini_set('memory_limit',                '-1');
ini_set('log_errors',                  '1');
ini_set('log_errors_max_len',          '0');
ini_set('error_log',                   __DIR__.'/../php-error.log');
ini_set('assert.exception',            '1');
ini_set('xdebug.show_exception_trace', '0');

// create an application to initialize the service container
$app = new Application();
