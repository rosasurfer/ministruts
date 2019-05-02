<?php
/**
 * Protective wrapper around the framework loader to prevent usage with unsupported PHP versions.
 */
if (PHP_VERSION_ID < 50600) {
    exit('Error: unsupported PHP version '.PHP_VERSION.' (rosasurfer/ministruts requires PHP >= 5.6)'.PHP_EOL);
}

// include the framework
require(__DIR__.'/framework.php');
