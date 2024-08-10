<?php
declare(strict_types=1);

/**
 * Framework loader.
 *
 * Load framework functions and constants.
 */
namespace rosasurfer\ministruts;

define('rosasurfer\ministruts\ROOT_DIR', dirname(__DIR__));


// Include helper functions and constants which can't be auto-loaded.
if (!defined('rosasurfer\ministruts\CLI'))               require(__DIR__.'/helpers.php');
if (!defined('rosasurfer\ministruts\db\orm\meta\BOOL'))  require(__DIR__.'/db/orm/meta/defines.php');
if (!defined('rosasurfer\ministruts\struts\MODULE_KEY')) require(__DIR__.'/struts/defines.php');


// In CLI mode register a SIGINT handler to catch Ctrl-C and execute destructors on shutdown.
if (CLI && function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function(int $signo, $signinfo = null): void {
        exit(1);          // calling exit() is sufficient to execute destructors
    });
}
