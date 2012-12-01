<?php
/**
 * Template for an index.php file
 * ------------------------------
 * Usually you don't need anything more in this file. This directory is visible by the web and it's
 * the application's root directory.  You can virtually link it to whatever web server directory
 * you like, you may even nest one project into another (see "WEB-INF/conf/httpd-include.conf").
 */
define('APPLICATION_NAME', 'myAppName');        // Pflicht: eindeutiger Bezeichner des Projekts für Namespaces etc.
define('APPLICATION_ROOT', dirName(__FILE__));  // Pflicht: Wurzelverzeichnis des Projekts (Verzeichnis, in dem "WEB-INF" liegt)

include(APPLICATION_ROOT.'/WEB-INF/classes/classes.php');

FrontController ::processRequest();
?>
