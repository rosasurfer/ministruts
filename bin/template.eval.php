#!/usr/bin/env php56
<?php
/**
 * Copy this file to your project's bin directory and point line 20 to your application's init script.
 *
 *
 * Command line script to execute arbitrary PHP code in the context of the application. Used to read application
 * configuration values by regular shell scripts.
 *
 * CAUTION: Make sure access rights in the project are set accordingly.
 *
 * @example
 *
 *   #!/bin/bash
 *   #
 *   APP_LOG_DIR="$(bin/eval.php 'echo rosasurfer\config\Config::getDefault()["app.dir.log"];')"
 *   echo "APP_LOG_DIR: $APP_LOG_DIR"
 *
 */
use function rosasurfer\stderror;
use const    rosasurfer\CLI;


require(dirName(realPath(__FILE__)).'/../app/init.php');
!CLI && exit(1|stderror('error: This script must be executed in CLI mode.'));


$args = array_slice($_SERVER['argv'], 1);
!$args && exit(0);

eval($args[0]);
