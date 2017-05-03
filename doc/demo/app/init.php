<?php
namespace rosasurfer;

// load Composer
require(__DIR__.'/../vendor/autoload.php');

// global settings
error_reporting(E_ALL);
ini_set('error_log', __DIR__.'/../etc/log/php-error.log');

// initialize Ministruts
MiniStruts::init([
    'config'  => __DIR__.'/config',
    'globals' => true,
]);
