<?php
/**
 * Protective wrapper around the framework.
 */
if (PHP_VERSION_ID < 50600 || PHP_VERSION_ID >= 70200) {
    echo 'Error: unsupported PHP version '.PHP_VERSION.' (this "rosasurfer/ministruts" version requires PHP 5.6 to 7.1)'.PHP_EOL;
    exit(1);
}

// prevent multiple includes
if (defined('rosasurfer\MINISTRUTS_ROOT')) return;

// now include the framework
require(__DIR__.'/bootstrap.php');
