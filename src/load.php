<?php
declare(strict_types=1);

/**
 * Protective wrapper around the framework.
 */
if (PHP_VERSION_ID < 70400 || PHP_VERSION_ID >= 90000) {
    echo 'Error: unsupported PHP version '.PHP_VERSION.' (this "rosasurfer/ministruts" version requires PHP 7.4 to 8.*)'.PHP_EOL;
    exit(1);
}

// prevent multiple includes
if (\defined('rosasurfer\ministruts\ROOT_DIR')) return;

// now include the framework
require(__DIR__.'/bootstrap.php');
