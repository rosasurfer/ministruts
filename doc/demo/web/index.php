<?php
// configure and init the app
!defined('APPLICATION_ROOT') && define('APPLICATION_ROOT', dirName(__DIR__));
require(APPLICATION_ROOT.'/app/init.php');

// run the web app
rosasurfer\ministruts\StrutsController::processRequest();
