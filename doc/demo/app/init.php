<?php
namespace rosasurfer;

// class loader
require(__DIR__.'/../vendor/autoload.php');

// global settings
error_reporting(E_ALL);
ini_set('error_log', __DIR__.'/../etc/log/php-error.log');

// initialize a new application
$app = new Application([
    'config'  => __DIR__.'/config',
    'globals' => true,
]);
