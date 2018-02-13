<?php
/**
 * Protective wrapper around the framework loader to prevent legacy PHP versions from triggering parser errors.
 */
if (PHP_VERSION_ID < 50600) exit('[FATAL] This project requires PHP >= 5.6'.PHP_EOL);


// include the framework
require(__DIR__.'/framework.php');
