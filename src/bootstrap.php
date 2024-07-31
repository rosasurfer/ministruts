<?php
/**
 * Framework loader.
 *
 * Load framework functions and constants.
 */
namespace rosasurfer;

define('rosasurfer\_MINISTRUTS_ROOT', dirname(__DIR__));
const MINISTRUTS_ROOT = _MINISTRUTS_ROOT;                       // local constants improve IDE auto-completion


// Include helper functions and constants which can't be auto-loaded.
if (!defined('rosasurfer\CLI'))                   require(MINISTRUTS_ROOT.'/src/helpers.php');
if (!defined('rosasurfer\db\orm\meta\BOOL'))      require(MINISTRUTS_ROOT.'/src/db/orm/meta/defines.php');
if (!defined('rosasurfer\ministruts\MODULE_KEY')) require(MINISTRUTS_ROOT.'/src/ministruts/defines.php');


// In CLI mode register a SIGINT handler to catch Ctrl-C and execute destructors on shutdown.
if (CLI && function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function($signo, $signinfo = null) {
        exit(1);                                                // calling exit() is sufficient to execute destructors
    });
}
