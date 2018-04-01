<?php
use rosasurfer\Application;

// class loader
require(($appRoot=dirName(__DIR__)).'/vendor/autoload.php');


// php.ini settings
error_reporting(E_ALL);
ini_set('log_errors',        1                                );
ini_set('error_log',         $appRoot.'/etc/log/php-error.log');
ini_set('session.save_path', $appRoot.'/etc/tmp'              );
ini_set('default_charset',  'UTF-8'                           );


// create a new application
return new Application([
    'app.dir.root'   => $appRoot,
    'app.dir.config' => __DIR__.'/config',
]);
