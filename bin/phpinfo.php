#!/usr/bin/env php
<?php
namespace rosasurfer\bin\phpinfo;

use rosasurfer\MiniStruts;
use rosasurfer\util\PHP;

/**
 * Command line version of phpInfo()
 */
require(__DIR__.'/../src/load.php');
MiniStruts::init([
    'config'  => __DIR__,
    'globals' => true,
]);


PHP::phpInfo();
