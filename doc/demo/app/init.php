<?php
namespace rosasurfer;


// class loader and view helpers
require(__DIR__.'/../vendor/autoload.php');
require(__DIR__.'/view/helpers.php');


// global settings
error_reporting(E_ALL);
ini_set('error_log',         __DIR__.'/../etc/log/php-error.log');
ini_set('session.save_path', __DIR__.'/../etc/tmp'              );
ini_set('default_charset',  'UTF-8'                             );


// initialize a new application
return new Application([
    'config'  => __DIR__.'/config',
    'globals' => true,
]);
