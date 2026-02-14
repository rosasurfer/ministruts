<?php
declare(strict_types=1);

use rosasurfer\ministruts\Application;

$rootDir = dirname(__DIR__);

// class loader (needed if a global PHPUnit is used)
require $rootDir.'/vendor/autoload.php';

// php.ini settings
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

ini_set('error_log',          $rootDir.'/phpunit-error.log');
ini_set('log_errors',         '1');
ini_set('log_errors_max_len', '0');
ini_set('memory_limit',      '-1');
set_include_path($rootDir.'/vendor');

// create an application to initialize the service container
return new Application([
    'app.dir.root' => $rootDir,
    'app.dir.tmp'  => $rootDir.'/etc/tmp',
]);
