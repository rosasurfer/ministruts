<?php
// configure and init the app
!defined('APPLICATION_ROOT') && define('APPLICATION_ROOT', dirName(__DIR__));
!defined('APPLICATION_ID'  ) && define('APPLICATION_ID',  'example');
require(APPLICATION_ROOT.'/app/init.php');

// run the web app
rosasurfer\ministruts\StrutsController::processRequest();
